<?php
namespace App\Http\Controllers;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Models\Rfq;
use App\Support\Fa;
use App\Support\Procurement;
use Illuminate\Http\Request;

class OfferController extends Controller {
    private const NUM = ['delivery_days', 'advance_percent', 'warranty_months', 'inspection_days', 'valid_days', 'tax_percent', 'shipping_cost'];

    public function index(Request $r) {
        $me = $r->user();
        $tab = $r->query('tab') === 'issued' && $me->company ? 'issued' : 'received';
        $q = $tab === 'issued' ? Offer::where('company_id', $me->company->id) : Offer::where('buyer_user_id', $me->id)->where('status', '!=', 'draft');
        return view('offers.index', ['tab' => $tab, 'offers' => $q->with(['company:id,name', 'buyer:id,name'])->latest('id')->paginate(20)->withQueryString()]);
    }

    public function show(Request $r, Offer $offer) {
        $me = $r->user();
        $seller = $offer->isSeller($me);
        abort_unless($seller || $me->isAdmin() || ($offer->isBuyer($me) && !$offer->isDraft()), 404);
        return view('offers.show', ['offer' => $offer->load('items', 'company', 'buyer', 'listing', 'rfq', 'order'), 'isSeller' => $seller, 'isBuyer' => $offer->isBuyer($me)]);
    }

    /** خریدار و مبدأ پیش‌فاکتور را همیشه از سمت سرور و از روی RFQ یا گفتگوی استعلام تعیین می‌کنیم؛ هرگز از فرم. */
    private function context(Request $r): array {
        $me = $r->user();
        abort_unless($me->company?->status === 'active', 403, 'برای صدور پیش‌فاکتور شرکت شما باید توسط مدیر تأیید شده باشد.');
        if ($r->filled('rfq')) {
            $rfq = Rfq::where('status', 'open')->findOrFail($r->input('rfq'));
            abort_if($rfq->user_id === $me->id, 403);
            $thread = Inquiry::where(['rfq_id' => $rfq->id, 'from_user_id' => $me->id])->whereNull('parent_id')->first();
            return ['ids' => ['buyer_user_id' => $rfq->user_id, 'rfq_id' => $rfq->id, 'inquiry_id' => $thread?->id, 'listing_id' => null],
                'param' => ['rfq' => $rfq->id], 'buyer' => $rfq->user, 'title' => $rfq->title,
                'items' => [['title' => $rfq->title, 'unit' => 'عدد', 'quantity' => $rfq->quantity ?: 1, 'unit_price' => '', 'description' => mb_substr((string) $rfq->details, 0, 500)]]];
        }
        if ($r->filled('inquiry')) {
            $inq = Inquiry::with('listing', 'from')->whereNull('parent_id')->whereNotNull('listing_id')->findOrFail($r->input('inquiry'));
            abort_unless($inq->to_user_id === $me->id && $inq->listing->company_id === $me->company->id, 403);
            return ['ids' => ['buyer_user_id' => $inq->from_user_id, 'rfq_id' => null, 'inquiry_id' => $inq->id, 'listing_id' => $inq->listing_id],
                'param' => ['inquiry' => $inq->id], 'buyer' => $inq->from, 'title' => $inq->listing->title,
                'items' => [['title' => $inq->listing->title, 'unit' => 'عدد', 'quantity' => 1, 'unit_price' => $inq->listing->price ?: '', 'description' => '']]];
        }
        abort(404);
    }

    public function create(Request $r) {
        $ctx = $this->context($r);
        return view('offers.form', ['offer' => new Offer(['delivery_terms' => 'ex_works', 'inspection_days' => 3, 'advance_percent' => 30]), 'ctx' => $ctx, 'items' => old('items', $ctx['items']), 'validDays' => 7]);
    }
    public function edit(Request $r, Offer $offer) {
        abort_unless($offer->isSeller($r->user()) && $offer->isDraft(), 403);
        $ctx = ['param' => [], 'buyer' => $offer->buyer, 'title' => $offer->listing?->title ?? $offer->rfq?->title ?? 'پیش‌فاکتور'];
        $items = old('items', $offer->items->map(fn ($i) => $i->only(['title', 'description', 'unit', 'quantity', 'unit_price']))->all());
        return view('offers.form', ['offer' => $offer, 'ctx' => $ctx, 'items' => $items, 'validDays' => max(1, (int) now()->startOfDay()->diffInDays($offer->valid_until ?? now()->addDays(7), false))]);
    }

    /** اعتبارسنجی مشترک؛ ارقام فارسی به لاتین تبدیل می‌شوند. @return array{0: array, 1: array} [سربرگ، ردیف‌ها] */
    private function parse(Request $r): array {
        $r->merge(collect($r->only(self::NUM))->map(fn ($v) => Fa::latin($v))->all());
        $items = collect((array) $r->input('items', []))->map(fn ($i) => is_array($i) ? array_merge($i, [
            'quantity' => Fa::latin($i['quantity'] ?? null), 'unit_price' => Fa::latin(str_replace([',', '٬'], '', (string) ($i['unit_price'] ?? '')))]) : [])->values()->all();
        $r->merge(['items' => $items]);
        $d = $r->validate([
            'delivery_terms' => 'nullable|in:ex_works,delivered,carrier_depot', 'delivery_place' => 'nullable|string|max:200', 'delivery_days' => 'nullable|integer|between:0,365',
            'advance_percent' => 'required|integer|between:0,100', 'payment_terms' => 'nullable|string|max:1000', 'warranty_months' => 'nullable|integer|between:0,240',
            'inspection_days' => 'required|integer|between:1,30', 'valid_days' => 'required|integer|between:1,60', 'tax_percent' => 'required|integer|between:0,25',
            'shipping_cost' => 'nullable|integer|min:0|max:' . Procurement::MAX_LINE, 'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1|max:30', 'items.*.title' => 'required|string|max:200', 'items.*.description' => 'nullable|string|max:500',
            'items.*.unit' => 'required|string|max:20', 'items.*.quantity' => 'required|numeric|gt:0|max:1000000000',
            'items.*.unit_price' => 'required|integer|min:0|max:' . Procurement::MAX_LINE,
        ], ['items.required' => 'حداقل یک ردیف لازم است.', 'items.*.title.required' => 'عنوان همه ردیف‌ها لازم است.']);
        $rows = collect($d['items'])->map(function ($i) {
            $q = round((float) $i['quantity'], 3); $line = Procurement::lineTotal($q, (int) $i['unit_price']);
            if ($line > Procurement::MAX_LINE) throw new \DomainException('مبلغ یکی از ردیف‌ها بیش از حد مجاز است.');
            return ['title' => $i['title'], 'description' => $i['description'] ?? null, 'unit' => $i['unit'], 'quantity' => $q, 'unit_price' => (int) $i['unit_price'], 'line_total' => $line];
        })->all();
        $header = collect($d)->except(['items', 'valid_days'])->all() + ['valid_until' => today()->addDays((int) $d['valid_days'])];
        $header['shipping_cost'] = (int) ($header['shipping_cost'] ?? 0);
        return [$header, $rows];
    }

    public function store(Request $r) {
        $ctx = $this->context($r);
        try {
            [$header, $rows] = $this->parse($r);
            $offer = Procurement::saveOffer(new Offer(), $header + $ctx['ids'] + ['company_id' => $r->user()->company->id, 'status' => 'draft'], $rows);
            return $this->afterSave($r, $offer);
        } catch (\DomainException $e) { return back()->withInput()->withErrors($e->getMessage()); }
    }
    public function update(Request $r, Offer $offer) {
        abort_unless($offer->isSeller($r->user()) && $offer->isDraft(), 403);
        try {
            [$header, $rows] = $this->parse($r);
            return $this->afterSave($r, Procurement::saveOffer($offer, $header, $rows));
        } catch (\DomainException $e) { return back()->withInput()->withErrors($e->getMessage()); }
    }
    private function afterSave(Request $r, Offer $offer) {
        if ($r->input('action') === 'send') return $this->doSend($offer);
        return redirect()->route('offers.show', $offer)->with('msg', 'پیش‌نویس ذخیره شد. پس از بازبینی آن را ارسال کنید.');
    }
    private function doSend(Offer $offer) {
        Procurement::send($offer);
        Procurement::notify($offer->buyer->mobile, 'offer', ['number' => $offer->number]);
        return redirect()->route('offers.show', $offer)->with('msg', "پیش‌فاکتور {$offer->number} برای خریدار ارسال شد.");
    }
    public function send(Request $r, Offer $offer) {
        abort_unless($offer->isSeller($r->user()), 403);
        try { return $this->doSend($offer); } catch (\DomainException $e) { return back()->withErrors($e->getMessage()); }
    }
    public function withdraw(Request $r, Offer $offer) {
        abort_unless($offer->isSeller($r->user()), 403);
        if ($offer->status !== 'sent') return back()->withErrors('فقط پیش‌فاکتور ارسال‌شده و پاسخ‌داده‌نشده قابل پس‌گرفتن است.');
        $offer->update(['status' => 'withdrawn', 'responded_at' => now()]);
        return back()->with('msg', 'پیش‌فاکتور پس گرفته شد.');
    }

    public function accept(Request $r, Offer $offer) {
        abort_unless($offer->isBuyer($r->user()), 403);
        $d = $r->validate(['delivery_address' => 'required|string|min:10|max:1000'], ['delivery_address.min' => 'آدرس تحویل را کامل بنویسید.']);
        try {
            $order = Procurement::accept($offer, $r->user(), $d['delivery_address']);
        } catch (\DomainException $e) { return back()->withErrors($e->getMessage()); }
        Procurement::notify($order->company->user->mobile, 'order', ['number' => $order->number]);
        return redirect()->route('orders.show', $order)->with('msg', "سفارش {$order->number} ثبت شد.");
    }
    public function reject(Request $r, Offer $offer) {
        abort_unless($offer->isBuyer($r->user()), 403);
        $d = $r->validate(['reason' => 'nullable|string|max:300']);
        if ($offer->status !== 'sent') return back()->withErrors('این پیش‌فاکتور قابل رد نیست.');
        $offer->update(['status' => 'rejected', 'responded_at' => now(), 'reject_reason' => $d['reason'] ?? null]);
        return redirect()->route('offers.index')->with('msg', 'پیش‌فاکتور رد شد.');
    }
}

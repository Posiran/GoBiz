<?php
namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Support\Fa;
use App\Support\Procurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller {
    public function index(Request $r) {
        $me = $r->user();
        $tab = $r->query('tab') === 'selling' && $me->company ? 'selling' : 'buying';
        $q = $tab === 'selling' ? Order::where('company_id', $me->company->id) : Order::where('buyer_user_id', $me->id);
        $status = isset(Order::STATUS[$r->query('status')]) ? $r->query('status') : null;
        if ($status) $q->where('status', $status);
        return view('orders.index', ['tab' => $tab, 'status' => $status, 'orders' => $q->with(['company:id,name', 'buyer:id,name'])->latest('id')->paginate(20)->withQueryString()]);
    }

    public function show(Request $r, Order $order) {
        $role = $order->roleOf($r->user());
        abort_unless($role, 404);
        return view('orders.show', ['order' => $order->load(['items', 'payments.user', 'events.user', 'company', 'buyer', 'offer', 'buyerCompany']), 'role' => $role]);
    }

    private function role(Request $r, Order $order, array $allowed): string {
        $role = $order->roleOf($r->user());
        abort_unless(in_array($role, $allowed, true), 403);
        return $role;
    }
    private function go(Request $r, Order $order, string $to, array $allowed, ?string $note = null, array $extra = []) {
        $role = $this->role($r, $order, $allowed);
        try { Procurement::move($order, $to, $r->user(), $role, $note, $extra); }
        catch (\DomainException $e) { return back()->withErrors($e->getMessage()); }
        return back()->with('msg', 'وضعیت سفارش: ' . Order::STATUS[$to]);
    }
    public function ship(Request $r, Order $order) {
        $d = $r->validate(['carrier' => 'nullable|string|max:100', 'tracking_no' => 'nullable|string|max:100']);
        return $this->go($r, $order, 'shipped', ['seller'], null, $d);
    }
    public function startPreparing(Request $r, Order $order) { return $this->go($r, $order, 'preparing', ['seller'], 'شروع آماده‌سازی بدون انتظار برای پیش‌پرداخت'); }
    public function deliver(Request $r, Order $order) { return $this->go($r, $order, 'delivered', ['buyer', 'seller']); }
    public function complete(Request $r, Order $order) { return $this->go($r, $order, 'completed', ['buyer'], 'تأیید نهایی خریدار'); }
    public function dispute(Request $r, Order $order) {
        $d = $r->validate(['reason' => 'required|string|max:300'], ['reason.required' => 'دلیل اختلاف را بنویسید.']);
        return $this->go($r, $order, 'disputed', ['buyer'], $d['reason']);
    }
    public function cancel(Request $r, Order $order) {
        $d = $r->validate(['reason' => 'nullable|string|max:300']);
        return $this->go($r, $order, 'cancelled', ['buyer', 'seller'], $d['reason'] ?? null);
    }

    // ───────── اسناد پرداخت (سایت پول نگه نمی‌دارد؛ خریدار سند ثبت و فروشنده دریافت را تأیید می‌کند) ─────────
    public function pay(Request $r, Order $order) {
        $this->role($r, $order, ['buyer']);
        if ($order->status === 'cancelled') return back()->withErrors('سفارش لغو شده است.');
        $r->merge(['amount' => Fa::latin(str_replace([',', '٬'], '', (string) $r->input('amount'))), 'reference_no' => Fa::latin($r->input('reference_no'))]);
        $left = Procurement::payableLeft($order);
        if ($left <= 0) return back()->withErrors('مبلغ کل سفارش قبلاً با اسناد ثبت‌شده پوشش داده شده است.');
        $d = $r->validate([
            'kind' => 'required|in:advance,installment,balance', 'method' => 'required|in:bank_transfer,card_to_card,cheque,cash,other',
            'amount' => "required|integer|min:1|max:$left", 'paid_at' => 'required|date|before_or_equal:today',
            'reference_no' => 'nullable|string|max:60', 'bank_name' => 'nullable|string|max:80', 'payer_name' => 'nullable|string|max:120', 'note' => 'nullable|string|max:300',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ], ['amount.max' => 'مبلغ از باقی‌مانده قابل ثبت (' . Fa::digits(number_format($left)) . ' تومان) بیشتر است.']);
        try {
            DB::transaction(function () use ($r, $order, $d) {
                $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($d['amount'] > Procurement::payableLeft($o)) throw new \DomainException('مبلغ از باقی‌مانده سفارش بیشتر است.');
                $path = $r->file('receipt')?->store("receipts/{$o->id}", 'local');       // دیسک خصوصی؛ فقط با مسیر مجاز قابل دانلود
                OrderPayment::create(collect($d)->except('receipt')->all() + ['order_id' => $o->id, 'user_id' => $r->user()->id, 'receipt_path' => $path]);
                Procurement::log($o, 'payment_added', $r->user(), null, null, 'ثبت سند پرداخت ' . number_format($d['amount']) . ' تومان');
            });
        } catch (\DomainException $e) { return back()->withInput()->withErrors($e->getMessage()); }
        return back()->with('msg', 'سند پرداخت ثبت شد و منتظر تأیید فروشنده است.');
    }
    public function review(Request $r, Order $order, OrderPayment $payment) {
        $this->role($r, $order, ['seller']);
        abort_unless($payment->order_id === $order->id, 404);
        $d = $r->validate(['action' => 'required|in:confirm,reject', 'reason' => 'nullable|string|max:300']);
        try { Procurement::review($payment, $r->user(), $d['action'] === 'confirm', $d['reason'] ?? null); }
        catch (\DomainException $e) { return back()->withErrors($e->getMessage()); }
        return back()->with('msg', $d['action'] === 'confirm' ? 'پرداخت تأیید شد.' : 'سند پرداخت رد شد.');
    }
    public function receipt(Request $r, Order $order, OrderPayment $payment) {
        abort_unless($order->roleOf($r->user()), 404);
        abort_unless($payment->order_id === $order->id && $payment->receipt_path && Storage::disk('local')->exists($payment->receipt_path), 404);
        return Storage::disk('local')->response($payment->receipt_path);
    }
}

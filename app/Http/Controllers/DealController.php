<?php
namespace App\Http\Controllers;
use App\Models\BusinessAccess;
use App\Models\Deal;
use App\Models\DealMessage;
use App\Models\Listing;
use App\Models\ListingDocument;
use App\Support\Audit;
use App\Support\Fa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** معامله کسب‌وکار/استارتاپ: اتاق داده، نامه قصد (LOI) و مذاکره. پرداخت و امانی عمداً در این بخش نیست. */
class DealController extends Controller {
    private function canSeeDataRoom(Request $r, Listing $l): bool {
        $u = $r->user();
        if (!$u) return false;
        if ($u->company?->id === $l->company_id || $u->role === 'admin') return true;
        if (!$l->business?->nda_required) return true;
        return BusinessAccess::where(['listing_id' => $l->id, 'user_id' => $u->id, 'status' => 'approved'])->exists();
    }
    private function ownsListing(Request $r, Listing $l): bool { return $r->user()->company?->id === $l->company_id; }

    // ---------- اتاق داده ----------
    public function room(Request $r, Listing $listing) {
        abort_unless(in_array($listing->type, ['business', 'startup'], true), 404);
        abort_unless($this->canSeeDataRoom($r, $listing), 403);
        $myDeal = Deal::where(['listing_id' => $listing->id, 'buyer_id' => $r->user()->id])->first();
        return view('deals.room', ['listing' => $listing, 'docs' => ListingDocument::where('listing_id', $listing->id)->latest()->get(),
            'owner' => $this->ownsListing($r, $listing), 'myDeal' => $myDeal]);
    }
    public function upload(Request $r, Listing $listing) {
        abort_unless($this->ownsListing($r, $listing) && in_array($listing->type, ['business', 'startup'], true), 403);
        $d = $r->validate(['title' => 'required|string|max:150', 'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,zip']);
        $f = $d['file'];
        $path = $f->storeAs('data-room/'.$listing->id, Str::random(24).'.'.$f->getClientOriginalExtension(), 'local');
        $doc = ListingDocument::create(['listing_id' => $listing->id, 'title' => $d['title'], 'path' => $path,
            'original_name' => Str::limit($f->getClientOriginalName(), 200, ''), 'size' => $f->getSize()]);
        Audit::record('dataroom.upload', $doc, ['listing_id' => $listing->id]);
        return back()->with('msg', 'سند به اتاق داده اضافه شد.');
    }
    public function download(Request $r, ListingDocument $doc) {
        abort_unless($this->canSeeDataRoom($r, $doc->listing), 403);
        abort_unless(Storage::disk('local')->exists($doc->path), 404);
        Audit::record('dataroom.download', $doc, ['listing_id' => $doc->listing_id]);
        return Storage::disk('local')->download($doc->path, $doc->original_name);
    }
    public function destroyDoc(Request $r, ListingDocument $doc) {
        abort_unless($this->ownsListing($r, $doc->listing), 403);
        Storage::disk('local')->delete($doc->path);
        $doc->delete();
        return back()->with('msg', 'سند حذف شد.');
    }

    // ---------- LOI ----------
    public function store(Request $r, Listing $listing) {
        abort_unless($listing->status === 'published' && in_array($listing->type, ['business', 'startup'], true), 404);
        $me = $r->user();
        if ($this->ownsListing($r, $listing)) return back()->withErrors('این آگهی متعلق به شماست.');
        $r->merge(['offer_amount' => Fa::latin($r->input('offer_amount')), 'stake_percent' => Fa::latin($r->input('stake_percent'))]);
        $d = $r->validate(['offer_amount' => 'required|integer|min:1', 'stake_percent' => 'nullable|numeric|min:0.01|max:100',
            'terms' => 'nullable|string|max:3000', 'valid_until' => 'nullable|date|after:today']);
        if ($listing->business?->nda_required
            && !BusinessAccess::where(['listing_id' => $listing->id, 'user_id' => $me->id, 'status' => 'approved'])->exists())
            return back()->withErrors('برای ارسال نامه قصد، ابتدا باید دسترسی به اطلاعات محرمانه تأیید شود.');
        if (Deal::where(['listing_id' => $listing->id, 'buyer_id' => $me->id])->exists())
            return back()->withErrors('برای این آگهی قبلاً نامه قصد ارسال کرده‌اید.');
        $deal = Deal::create($d + ['listing_id' => $listing->id, 'buyer_id' => $me->id, 'seller_id' => $listing->company->user_id]);
        Audit::record('deal.loi', $deal, ['amount' => $deal->offer_amount]);
        return redirect()->route('deals.show', $deal)->with('msg', 'نامه قصد برای فروشنده ارسال شد.');
    }

    public function index(Request $r) {
        $me = $r->user()->id;
        $deals = Deal::where(fn ($q) => $q->where('buyer_id', $me)->orWhere('seller_id', $me))
            ->with('listing:id,title,slug', 'buyer:id,name', 'seller:id,name')->latest('updated_at')->paginate(20);
        return view('deals.index', compact('deals', 'me'));
    }
    public function show(Request $r, Deal $deal) {
        abort_unless($deal->hasParty($r->user()->id), 403);
        $deal->load('listing:id,title,slug', 'buyer:id,name', 'seller:id,name', 'messages.user:id,name');
        return view('deals.show', ['deal' => $deal, 'isSeller' => $deal->seller_id === $r->user()->id]);
    }
    public function message(Request $r, Deal $deal) {
        abort_unless($deal->hasParty($r->user()->id), 403);
        if (!$deal->isOpen()) return back()->withErrors('این معامله بسته شده است.');
        $d = $r->validate(['body' => 'required|string|min:2|max:2000']);
        DealMessage::create(['deal_id' => $deal->id, 'user_id' => $r->user()->id, 'body' => $d['body']]);
        if ($deal->status === 'loi_sent' && $deal->seller_id === $r->user()->id) $deal->status = 'negotiating';
        $deal->touch(); $deal->save();
        return back();
    }
    public function decide(Request $r, Deal $deal) {
        abort_unless($deal->hasParty($r->user()->id), 403);
        $d = $r->validate(['action' => 'required|in:accept,reject,withdraw']);
        if (!$deal->isOpen()) return back()->withErrors('این معامله بسته شده است.');
        $isSeller = $deal->seller_id === $r->user()->id;
        if ($d['action'] === 'withdraw' && $isSeller) abort(403);
        if ($d['action'] !== 'withdraw' && !$isSeller) abort(403);
        $deal->update(['status' => ['accept' => 'accepted', 'reject' => 'rejected', 'withdraw' => 'withdrawn'][$d['action']], 'decided_at' => now()]);
        Audit::record('deal.'.$deal->status, $deal);
        return back()->with('msg', 'وضعیت معامله: '.Deal::LABELS[$deal->status]);
    }
}

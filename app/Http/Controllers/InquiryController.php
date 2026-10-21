<?php
namespace App\Http\Controllers;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Support\Fa;
use App\Support\Ippanel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class InquiryController extends Controller {
    /** درخواست قیمت از روی یک آگهی */
    public function store(Request $r, Listing $listing) {
        $me = $r->user();
        abort_unless($listing->status === 'published', 404);
        if ($me->company?->id === $listing->company_id) return back()->withErrors('این آگهی متعلق به شماست.');
        $r->merge(['offered_price' => Fa::latin($r->input('offered_price'))]);
        $d = $r->validate(['message' => 'required|string|min:10|max:2000', 'offered_price' => 'nullable|integer|min:0']);
        Inquiry::create($d + [
            'listing_id' => $listing->id, 'from_user_id' => $me->id,
            'to_user_id' => $listing->company->user_id, 'to_company_id' => $listing->company_id,
        ]);
        if (config('ippanel.patterns.inquiry'))
            Ippanel::pattern($listing->company->user->mobile, config('ippanel.patterns.inquiry'), ['title' => Str::limit($listing->title, 30, '')]);
        return back()->with('msg', 'درخواست شما برای فروشنده ارسال شد. پاسخ را در بخش «پیام‌ها» ببینید.');
    }

    public function inbox(Request $r) {
        $me = $r->user()->id;
        $threads = Inquiry::whereNull('parent_id')->where(fn ($q) => $q->where('from_user_id', $me)->orWhere('to_user_id', $me))
            ->with(['listing:id,title,slug', 'rfq:id,title', 'from.company:id,user_id,name', 'to.company:id,user_id,name'])
            ->withCount(['replies as unread' => fn ($q) => $q->where('to_user_id', $me)->where('status', 'new')])
            ->latest('updated_at')->paginate(20);
        return view('inbox.index', compact('threads'));
    }

    public function show(Request $r, Inquiry $inquiry) {
        $root = $inquiry->parent_id ? Inquiry::findOrFail($inquiry->parent_id) : $inquiry;
        $me = $r->user()->id;
        abort_unless(in_array($me, [$root->from_user_id, $root->to_user_id]), 403);
        Inquiry::where(fn ($q) => $q->where('id', $root->id)->orWhere('parent_id', $root->id))
            ->where('to_user_id', $me)->where('status', 'new')->update(['status' => 'read']);
        $thread = Inquiry::where(fn ($q) => $q->where('id', $root->id)->orWhere('parent_id', $root->id))
            ->with('from.company:id,user_id,name')->oldest()->get();
        $root->load('listing:id,title,slug', 'rfq:id,title');
        return view('inbox.show', ['root' => $root, 'thread' => $thread, 'subject' => $root->listing?->title ?? $root->rfq?->title ?? 'پیام']);
    }

    public function reply(Request $r, Inquiry $inquiry) {
        $root = $inquiry->parent_id ? Inquiry::findOrFail($inquiry->parent_id) : $inquiry;
        $me = $r->user()->id;
        abort_unless(in_array($me, [$root->from_user_id, $root->to_user_id]), 403);
        $r->merge(['offered_price' => Fa::latin($r->input('offered_price'))]);
        $d = $r->validate(['message' => 'required|string|min:2|max:2000', 'offered_price' => 'nullable|integer|min:0']);
        Inquiry::create($d + [
            'parent_id' => $root->id, 'listing_id' => $root->listing_id, 'rfq_id' => $root->rfq_id,
            'from_user_id' => $me, 'to_user_id' => $me === $root->from_user_id ? $root->to_user_id : $root->from_user_id,
        ]);
        Inquiry::where(fn ($q) => $q->where('id', $root->id)->orWhere('parent_id', $root->id))
            ->where('to_user_id', $me)->whereIn('status', ['new', 'read'])->update(['status' => 'answered']);
        $root->touch();
        return redirect()->route('inbox.show', $root)->with('msg', 'پاسخ ارسال شد.');
    }
}

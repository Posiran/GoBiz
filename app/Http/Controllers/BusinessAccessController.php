<?php
namespace App\Http\Controllers;
use App\Models\BusinessAccess;
use App\Models\Listing;
use App\Support\Procurement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BusinessAccessController extends Controller {
    /** خریدار/سرمایه‌گذار: درخواست دسترسی به اطلاعات محرمانه با پذیرش تعهد محرمانگی */
    public function request(Request $r, Listing $listing) {
        $me = $r->user();
        abort_unless($listing->status === 'published' && $listing->business?->nda_required, 404);
        if ($me->company?->id === $listing->company_id) return back()->withErrors('این آگهی متعلق به شماست.');
        $d = $r->validate(['accept_nda' => 'accepted', 'message' => 'nullable|string|max:500'], ['accept_nda.accepted' => 'برای ادامه باید تعهد محرمانگی را بپذیرید.']);
        $existing = BusinessAccess::where(['listing_id' => $listing->id, 'user_id' => $me->id])->first();
        if ($existing) return back()->withErrors($existing->status === 'rejected' ? 'درخواست قبلی شما رد شده است.' : 'درخواست شما قبلاً ثبت شده است.');
        BusinessAccess::create(['listing_id' => $listing->id, 'user_id' => $me->id, 'message' => $d['message'] ?? null, 'nda_accepted_at' => now()]);
        Procurement::notify($listing->company->user->mobile, 'access', ['title' => Str::limit($listing->title, 30, '')]);
        return back()->with('msg', 'درخواست دسترسی ثبت شد. پس از تأیید فروشنده اطلاعات برای شما باز می‌شود.');
    }

    /** فروشنده: صف درخواست‌های دسترسی به آگهی‌های خودش */
    public function index(Request $r) {
        $rows = BusinessAccess::whereHas('listing', fn ($q) => $q->where('company_id', $r->user()->company->id))
            ->with(['listing:id,title,slug', 'user:id,name', 'user.company:id,user_id,name'])
            ->orderByRaw("status = 'pending' DESC")->latest('id')->paginate(20);
        return view('business.access', ['rows' => $rows]);
    }
    public function review(Request $r, BusinessAccess $access) {
        abort_unless($access->listing->company_id === $r->user()->company->id, 403);
        $d = $r->validate(['action' => 'required|in:approve,reject']);
        if ($access->status !== 'pending') return back()->withErrors('این درخواست قبلاً بررسی شده است.');
        $access->update(['status' => $d['action'] === 'approve' ? 'approved' : 'rejected', 'reviewed_at' => now()]);
        return back()->with('msg', $d['action'] === 'approve' ? 'دسترسی تأیید شد.' : 'درخواست رد شد.');
    }
}

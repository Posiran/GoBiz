<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Listing;
use App\Models\Payment;
use Illuminate\Http\Request;

class ModerationController extends Controller {
    public function index(Request $r) {
        $tab = in_array($r->query('tab'), ['companies', 'payments'], true) ? $r->query('tab') : 'listings';
        $status = $r->query('status', $tab === 'payments' ? 'paid' : 'pending');
        $q = match ($tab) {
            'companies' => Company::with('user:id,name,mobile'),
            'payments' => Payment::with('user:id,name,mobile', 'plan:id,name'),
            default => Listing::with('company:id,name,status'),
        };
        return view('admin.index', [
            'tab' => $tab, 'status' => $status,
            'counts' => ['companies' => Company::where('status', 'pending')->count(), 'listings' => Listing::where('status', 'pending')->count()],
            'rows' => $q->where('status', $status)->latest()->paginate(20)->withQueryString(),
        ]);
    }
    public function company(Request $r, Company $company) {
        $company->update($r->validate(['status' => 'required|in:pending,active,suspended', 'verified_level' => 'required|integer|between:0,2']));
        return back()->with('msg', 'وضعیت شرکت ذخیره شد.');
    }
    public function listing(Request $r, Listing $listing) {
        $a = $r->validate(['action' => 'required|in:approve,reject,feature,unfeature'])['action'];
        if ($a === 'approve' && $listing->company->status !== 'active')
            return back()->withErrors('ابتدا شرکت این آگهی را تأیید (فعال) کنید.');
        match ($a) {
            'approve' => $listing->update(['status' => 'published', 'published_at' => $listing->published_at ?? now(), 'expires_at' => now()->addDays(90)]),
            'reject' => $listing->update(['status' => 'rejected', 'is_featured' => false]),
            'feature' => $listing->update(['is_featured' => true, 'featured_until' => now()->addDays(30)]),
            'unfeature' => $listing->update(['is_featured' => false, 'featured_until' => null]),
        };
        return back()->with('msg', 'انجام شد.');
    }
}

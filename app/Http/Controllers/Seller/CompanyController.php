<?php
namespace App\Http\Controllers\Seller;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Listing;
use App\Models\Province;
use App\Support\Fa;
use Illuminate\Http\Request;

class CompanyController extends Controller {
    public function dashboard(Request $r) {
        $c = $r->user()->company;
        if (!$c) return redirect()->route('seller.company.edit');
        return view('seller.dashboard', ['company' => $c, 'counts' => $c->listings()->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status')]);
    }
    public function edit(Request $r) {
        $c = $r->user()->company ?? new \App\Models\Company(['business_type' => 'manufacturer']);
        $prov = old('province_id', $c->city?->province_id);
        return view('seller.company', [
            'company' => $c, 'provinces' => Province::orderBy('name')->get(),
            'cities' => $prov ? City::where('province_id', $prov)->orderBy('name')->get() : collect(),
            'towns' => collect(), 'sel' => ['province' => $prov, 'city' => old('city_id', $c->city_id), 'town' => null],
        ]);
    }
    public function save(Request $r) {
        $r->merge(collect($r->only(['national_id', 'registration_no', 'established_year', 'phone']))->map(fn ($v) => Fa::latin($v))->all());
        $d = $r->validate([
            'name' => 'required|string|max:200', 'business_type' => 'required|in:manufacturer,trader,agent,service',
            'national_id' => 'nullable|string|max:20', 'registration_no' => 'nullable|string|max:30',
            'established_year' => 'nullable|integer|between:1300,2100',
            'employees_range' => 'nullable|in:1-10,11-50,51-200,201-500,500+',
            'city_id' => 'required|exists:cities,id', 'address' => 'nullable|string|max:300',
            'phone' => 'nullable|string|max:30', 'website' => 'nullable|url|max:200', 'description' => 'nullable|string|max:5000',
        ]);
        $u = $r->user();
        if ($u->company) $u->company->update($d);
        else {
            $u->company()->create($d + ['slug' => Listing::makeSlug($d['name']), 'status' => 'pending']);
            if (!$u->isAdmin()) $u->forceFill(['role' => 'seller'])->save();
        }
        return redirect()->route('seller.dashboard')->with('msg', 'اطلاعات شرکت ذخیره شد. پس از تأیید مدیر، آگهی‌های شما منتشر می‌شود.');
    }
}

<?php
namespace App\Http\Controllers;
use App\Models\City;
use App\Models\Company;
use App\Models\Province;
use Illuminate\Http\Request;

class CompanyDirectoryController extends Controller {
    public function index(Request $r) {
        $f = $r->validate(['q' => 'nullable|string|max:100', 'business_type' => 'nullable|in:manufacturer,trader,agent,service',
            'province_id' => 'nullable|integer', 'city_id' => 'nullable|integer', 'verified' => 'nullable|boolean']);
        $q = Company::where('status', 'active')->with('city.province')
            ->withCount(['listings as live_listings' => fn ($l) => $l->published()]);
        if (!empty($f['q'])) $q->where('name', 'like', '%' . addcslashes($f['q'], '%_\\') . '%');
        if (!empty($f['business_type'])) $q->where('business_type', $f['business_type']);
        if (!empty($f['province_id'])) $q->whereHas('city', fn ($c) => $c->where('province_id', $f['province_id']));
        if (!empty($f['city_id'])) $q->where('city_id', $f['city_id']);
        if (!empty($f['verified'])) $q->where('verified_level', '>=', 1);
        return view('companies.index', [
            'companies' => $q->orderByDesc('verified_level')->orderByDesc('live_listings')->paginate(20)->withQueryString(),
            'provinces' => Province::orderBy('name')->get(),
            'cities' => empty($f['province_id']) ? collect() : City::where('province_id', $f['province_id'])->orderBy('name')->get(),
            'sel' => ['province' => $f['province_id'] ?? null, 'city' => $f['city_id'] ?? null, 'town' => null], 'filters' => $f,
        ]);
    }
    public function show(string $slug) {
        $c = Company::where('slug', $slug)->where('status', 'active')->with('city.province')->firstOrFail();
        return view('companies.show', [
            'company' => $c,
            'listings' => $c->listings()->published()->with(['city', 'industrialTown', 'media', 'category', 'company:id,name,verified_level'])->latest('published_at')->paginate(12),
        ]);
    }
}

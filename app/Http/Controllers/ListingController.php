<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\City;
use App\Models\IndustrialTown;
use App\Models\Province;
use App\Models\Listing;
use Illuminate\Http\Request;

class ListingController extends Controller {
    public function index(Request $r) {
        $f = $r->validate([
            'q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:140',
            'province_id' => 'nullable|integer', 'city_id' => 'nullable|integer', 'industrial_town_id' => 'nullable|integer', 'type' => 'nullable|in:machine,factory,part,service,business,startup', 'sale_type' => 'nullable|in:full_sale,stake_sale,investment',
            'condition' => 'nullable|in:new,used,refurbished', 'deal_type' => 'nullable|in:sale,rent,lease',
            'price_min' => 'nullable|integer|min:0', 'price_max' => 'nullable|integer|min:0',
            'year_min' => 'nullable|integer|min:1950', 'verified' => 'nullable|boolean',
            'sort' => 'nullable|in:newest,price_asc,price_desc', 'attr' => 'nullable|array',
        ]);
        if (!empty($f['attr'])) array_walk_recursive($f['attr'], function (&$v) { $v = \App\Support\Fa::latin((string) $v); });
        $q = Listing::published()->with(['company:id,name,verified_level', 'media', 'city', 'industrialTown', 'category'])->filter($f)->attrFilter($f['attr'] ?? []);
        match ($f['sort'] ?? 'newest') {
            'price_asc' => $q->orderBy('price'), 'price_desc' => $q->orderByDesc('price'),
            default => $q->featuredFirst()->orderByDesc('published_at'),
        };
        $cat = !empty($f['category']) ? Category::where('slug', $f['category'])->first() : null;
        return view('listings.index', [
            'filterAttrs' => $cat ? $cat->specs()->where('is_filterable', true)->get() : collect(),
            'listings' => $q->paginate(24)->withQueryString(),
            'categories' => Category::whereNull('parent_id')->with('children')->orderBy('sort_order')->get(),
            'provinces' => Province::orderBy('name')->get(),
            'cities' => empty($f['province_id']) ? collect() : City::where('province_id', $f['province_id'])->orderBy('name')->get(),
            'towns' => empty($f['city_id']) ? collect() : IndustrialTown::where('city_id', $f['city_id'])->orderBy('name')->get(),
            'filters' => $f,
        ]);
    }

    public function show(string $slug) {
        $l = Listing::where('slug', $slug)->with(['company', 'media', 'factory', 'attributeValues', 'category', 'city', 'industrialTown', 'business'])->firstOrFail();
        $live = $l->status === 'published' && (!$l->expires_at || $l->expires_at->isFuture());
        $u = auth()->user();
        abort_unless($live || ($u && ($u->isAdmin() || $u->company?->id === $l->company_id)), 404);
        if ($live) $l->increment('views');
        // اطلاعات محرمانه کسب‌وکار: open | owner | approved | pending | rejected | null
        $access = null;
        if ($l->business) {
            if (!$l->business->nda_required) $access = 'open';
            elseif ($u && ($u->isAdmin() || $u->company?->id === $l->company_id)) $access = 'owner';
            elseif ($u) $access = \App\Models\BusinessAccess::where(['listing_id' => $l->id, 'user_id' => $u->id])->value('status');
        }
        return view('listings.show', ['listing' => $l, 'access' => $access]);
    }
}

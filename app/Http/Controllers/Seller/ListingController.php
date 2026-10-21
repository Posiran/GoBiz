<?php
namespace App\Http\Controllers\Seller;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\City;
use App\Models\FactoryDetail;
use App\Models\IndustrialTown;
use App\Models\Listing;
use App\Models\ListingMedia;
use App\Models\Province;
use App\Support\Fa;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ListingController extends Controller {
    private const NUM = ['manufacture_year', 'price', 'min_order_qty', 'stock_qty', 'warranty_months', 'land_area', 'building_area', 'power_kw',
        'founded_year', 'team_size', 'stake_percent', 'valuation', 'annual_revenue', 'annual_profit', 'monthly_revenue', 'customers_count'];
    private const BUSINESS = ['sale_type', 'industry', 'business_model', 'founded_year', 'team_size', 'stage', 'stake_percent', 'valuation', 'annual_revenue', 'annual_profit', 'monthly_revenue', 'customers_count', 'reason_for_sale', 'assets_included', 'nda_required'];
    private const FACTORY = ['land_area', 'building_area', 'power_kw', 'zone_type', 'has_license'];

    private function mine(Listing $l): Listing { abort_unless($l->company_id === auth()->user()->company->id, 403); return $l; }

    public function index(Request $r) {
        return view('seller.listings.index', ['listings' => $r->user()->company->listings()->latest()->paginate(20)]);
    }
    public function create() { return $this->form(new Listing(['type' => 'machine', 'deal_type' => 'sale'])); }
    public function edit(Listing $listing) { return $this->form($this->mine($listing)->load('media', 'factory', 'city', 'business')); }

    private function form(Listing $l) {
        $prov = old('province_id', $l->city?->province_id);
        $city = old('city_id', $l->city_id);
        return view('seller.listings.form', [
            'listing' => $l, 'attrValues' => $l->exists ? $l->attributeValues()->pluck('listing_attribute_values.value', 'attributes.id')->all() : [], 'categories' => Category::whereNull('parent_id')->with('children')->orderBy('sort_order')->get(),
            'provinces' => Province::orderBy('name')->get(),
            'cities' => $prov ? City::where('province_id', $prov)->orderBy('name')->get() : collect(),
            'towns' => $city ? IndustrialTown::where('city_id', $city)->orderBy('name')->get() : collect(),
            'sel' => ['province' => $prov, 'city' => $city, 'town' => old('industrial_town_id', $l->industrial_town_id)],
        ]);
    }

    private function validated(Request $r): array {
        $r->merge(collect($r->only(self::NUM))->map(fn ($v) => Fa::number($v))->all());
        $d = $r->validate([
            'title' => 'required|string|max:200',
            'category_id' => ['required', Rule::exists('categories', 'id')->whereNotNull('parent_id')],
            'type' => 'required|in:machine,factory,part,service,business,startup',
            // کسب‌وکار / استارتاپ
            'sale_type' => [Rule::requiredIf(fn () => in_array($r->input('type'), ['business', 'startup'], true)), 'nullable', 'in:full_sale,stake_sale,investment'],
            'industry' => 'nullable|string|max:100', 'business_model' => 'nullable|string|max:100',
            'founded_year' => 'nullable|integer|between:1300,2100', 'team_size' => 'nullable|integer|min:0|max:1000000',
            'stage' => 'nullable|in:idea,mvp,early_revenue,growth,scale', 'stake_percent' => 'nullable|numeric|between:0.01,100',
            'valuation' => 'nullable|integer|min:0', 'annual_revenue' => 'nullable|integer|min:0', 'annual_profit' => 'nullable|integer',
            'monthly_revenue' => 'nullable|integer|min:0', 'customers_count' => 'nullable|integer|min:0',
            'reason_for_sale' => 'nullable|string|max:3000', 'assets_included' => 'nullable|string|max:3000',
            'province_id' => 'nullable|integer', 'city_id' => 'required|exists:cities,id',
            'industrial_town_id' => ['nullable', Rule::exists('industrial_towns', 'id')->where('city_id', $r->input('city_id'))],
            'description' => 'nullable|string|max:10000', 'condition_state' => 'nullable|in:new,used,refurbished',
            'brand' => 'nullable|string|max:100', 'model' => 'nullable|string|max:100',
            'manufacture_year' => 'nullable|integer|between:1300,2100', 'origin_country' => 'nullable|string|max:60',
            'deal_type' => 'required|in:sale,rent,lease', 'price' => 'nullable|integer|min:0',
            'min_order_qty' => 'nullable|integer|min:1', 'stock_qty' => 'nullable|integer|min:0', 'warranty_months' => 'nullable|integer|between:0,240',
            'land_area' => 'nullable|integer|min:0', 'building_area' => 'nullable|integer|min:0', 'power_kw' => 'nullable|integer|min:0',
            'zone_type' => 'nullable|in:industrial_town,free_zone,outside_town',
            'images' => 'nullable|array|max:8', 'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
        $d['price_negotiable'] = $r->boolean('price_negotiable');
        $d['has_installation'] = $r->boolean('has_installation');
        $d['has_license'] = $r->boolean('has_license');
        $d['nda_required'] = $r->boolean('nda_required');
        return $d;
    }

    /** مشخصات فنی دسته انتخاب‌شده را اعتبارسنجی می‌کند؛ خروجی برای sync() */
    private function attrs(Request $r, int $categoryId): array {
        $defs = Attribute::where('category_id', $categoryId)->get()->keyBy('id');
        $out = [];
        foreach ((array) $r->input('attrs', []) as $id => $val) {
            $a = $defs[$id] ?? null;
            if (!$a || is_array($val)) continue;
            $val = Fa::latin(trim((string) $val));
            if ($val === '') continue;
            if ($a->type === 'number' && !is_numeric($val)) throw ValidationException::withMessages(["attrs.$id" => "«{$a->name}» باید عدد باشد."]);
            if ($a->type === 'select' && !in_array($val, $a->options ?? [], true)) throw ValidationException::withMessages(["attrs.$id" => "مقدار «{$a->name}» معتبر نیست."]);
            if ($a->type === 'bool') $val = '1';
            $out[$id] = ['value' => mb_substr($val, 0, 255)];
        }
        return $out;
    }

    private function extras(Request $r, Listing $l, array $d): void {
        $l->attributeValues()->sync($this->attrs($r, (int) $d['category_id']));
        if (in_array($d['type'], ['business', 'startup'], true)) \App\Models\BusinessDetail::updateOrCreate(['listing_id' => $l->id], Arr::only($d, self::BUSINESS));
        else \App\Models\BusinessDetail::where('listing_id', $l->id)->delete();
        if ($d['type'] === 'factory') FactoryDetail::updateOrCreate(['listing_id' => $l->id], Arr::only($d, self::FACTORY));
        else FactoryDetail::where('listing_id', $l->id)->delete();
        if ($ids = $r->input('remove_images')) {
            $l->media()->whereIn('id', $ids)->get()->each(function ($m) { Storage::disk('public')->delete($m->path); $m->delete(); });
        }
        $room = max(0, 8 - $l->media()->count());
        foreach (array_slice($r->file('images', []), 0, $room) as $i => $f)
            ListingMedia::create(['listing_id' => $l->id, 'kind' => 'image', 'path' => $f->store("listings/{$l->id}", 'public'), 'sort_order' => $i]);
    }

    public function store(Request $r) {
        $c = $r->user()->company;
        if ($c->listings()->whereIn('status', ['pending', 'published'])->count() >= $c->listingLimit())
            return redirect()->route('billing.plans')->withErrors('سقف آگهی‌های پلن شما پر شده است. برای افزایش سقف، پلن تهیه کنید.');
        $d = $this->validated($r);
        $l = $r->user()->company->listings()->create(Arr::except($d, [...self::FACTORY, ...self::BUSINESS, 'province_id', 'images']) + ['slug' => Listing::makeSlug($d['title']), 'status' => 'pending']);
        $this->extras($r, $l, $d);
        return redirect()->route('seller.listings.index')->with('msg', 'آگهی ثبت شد و پس از تأیید مدیر منتشر می‌شود.');
    }
    public function update(Request $r, Listing $listing) {
        $this->mine($listing);
        $d = $this->validated($r);
        // ویرایش آگهی، آن را دوباره به صف بررسی می‌برد
        $listing->update(Arr::except($d, [...self::FACTORY, ...self::BUSINESS, 'province_id', 'images']) + ['status' => 'pending']);
        $this->extras($r, $listing, $d);
        return redirect()->route('seller.listings.index')->with('msg', 'تغییرات ذخیره شد و آگهی دوباره در صف تأیید قرار گرفت.');
    }
    /** ویژه کردن ۳۰ روزه با سهمیه‌ی پلن */
    public function feature(Request $r, Listing $listing) {
        $this->mine($listing); $c = $r->user()->company;
        if ($listing->status !== 'published') return back()->withErrors('فقط آگهی منتشرشده را می‌توان ویژه کرد.');
        if ($listing->is_featured && $listing->featured_until?->isFuture()) return back()->withErrors('این آگهی هم‌اکنون ویژه است.');
        $used = $c->listings()->where('is_featured', true)->where('featured_until', '>', now())->count();
        if ($used >= $c->featuredSlots()) return back()->withErrors('سهمیه‌ی آگهی ویژه‌ی پلن شما پر است یا پلن شما آگهی ویژه ندارد.');
        $listing->update(['is_featured' => true, 'featured_until' => now()->addDays(30)]);
        return back()->with('msg', 'آگهی به مدت ۳۰ روز ویژه شد.');
    }
    public function destroy(Listing $listing) {
        $this->mine($listing);
        Storage::disk('public')->deleteDirectory("listings/{$listing->id}");
        $listing->delete();
        return back()->with('msg', 'آگهی حذف شد.');
    }
}

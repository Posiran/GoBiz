<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Listing extends Model {
    protected $guarded = [];
    protected $casts = ['price_negotiable' => 'boolean', 'is_featured' => 'boolean',
        'has_installation' => 'boolean', 'published_at' => 'datetime', 'featured_until' => 'datetime', 'expires_at' => 'datetime'];

    public static function makeSlug(string $title): string {
        $base = trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($title)), '-');
        return mb_substr($base, 0, 150) . '-' . Str::lower(Str::random(5));
    }
    public function company() { return $this->belongsTo(Company::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function industrialTown() { return $this->belongsTo(IndustrialTown::class); }
    public function media() { return $this->hasMany(ListingMedia::class)->orderBy('sort_order'); }
    public function business() { return $this->hasOne(BusinessDetail::class); }
    public function factory() { return $this->hasOne(FactoryDetail::class); }
    public function attributeValues() { return $this->belongsToMany(Attribute::class, 'listing_attribute_values')->withPivot('value'); }

    /** آگهی ویژه‌ی هنوز معتبر اول */
    public function scopeFeaturedFirst(Builder $q): Builder {
        return $q->orderByRaw('(is_featured = 1 AND (featured_until IS NULL OR featured_until > NOW())) DESC');
    }
    public function scopePublished(Builder $q): Builder {
        return $q->where('status', 'published')->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** فیلترهای صفحه لیست: q, category, city_id, type, condition, deal_type, price_min, price_max, year_min, verified */
    /** attr[ID]=مقدار | attr[ID][min|max]=عدد | attr[ID]=1 (بولی) */
    public function scopeAttrFilter(Builder $q, array $attrs): Builder {
        foreach ($attrs as $id => $v) {
            $id = (int) $id;
            if (!$id) continue;
            if (is_array($v)) {
                $min = $v['min'] ?? null; $max = $v['max'] ?? null;
                if (!is_numeric($min) && !is_numeric($max)) continue;
                $q->whereHas('attributeValues', function ($w) use ($id, $min, $max) {
                    $w->where('attributes.id', $id);
                    if (is_numeric($min)) $w->whereRaw('CAST(listing_attribute_values.value AS DECIMAL(20,4)) >= ?', [$min]);
                    if (is_numeric($max)) $w->whereRaw('CAST(listing_attribute_values.value AS DECIMAL(20,4)) <= ?', [$max]);
                });
            } elseif ($v !== '' && $v !== null) {
                $q->whereHas('attributeValues', fn ($w) => $w->where('attributes.id', $id)->where('listing_attribute_values.value', (string) $v));
            }
        }
        return $q;
    }

    public function scopeFilter(Builder $q, array $f): Builder {
        if (!empty($f['q'])) $q->whereRaw('MATCH(title, description, brand, model) AGAINST (? IN BOOLEAN MODE)', [$f['q'] . '*']);
        if (!empty($f['category']) && $cat = Category::where('slug', $f['category'])->first())
            $q->whereIn('category_id', $cat->selfAndDescendantIds());
        if (!empty($f['province_id'])) $q->whereHas('city', fn ($c) => $c->where('province_id', $f['province_id']));
        foreach (['city_id', 'industrial_town_id', 'type', 'deal_type'] as $k) if (!empty($f[$k])) $q->where($k, $f[$k]);
        if (!empty($f['sale_type'])) $q->whereHas('business', fn ($b) => $b->where('sale_type', $f['sale_type']));
        if (!empty($f['condition'])) $q->where('condition_state', $f['condition']);
        if (!empty($f['price_min'])) $q->where('price', '>=', (int) $f['price_min']);
        if (!empty($f['price_max'])) $q->where('price', '<=', (int) $f['price_max']);
        if (!empty($f['year_min'])) $q->where('manufacture_year', '>=', (int) $f['year_min']);
        if (!empty($f['verified'])) $q->whereHas('company', fn ($c) => $c->where('verified_level', '>=', 1));
        return $q;
    }
}

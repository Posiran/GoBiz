<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BusinessDetail extends Model {
    public const SALE_TYPE = ['full_sale' => 'فروش کامل کسب‌وکار', 'stake_sale' => 'فروش بخشی از سهام', 'investment' => 'جذب سرمایه / شریک'];
    public const STAGE = ['idea' => 'ایده', 'mvp' => 'نمونه اولیه (MVP)', 'early_revenue' => 'درآمد اولیه', 'growth' => 'رشد', 'scale' => 'مقیاس‌پذیری'];
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'listing_id';
    protected $guarded = [];
    protected $casts = ['nda_required' => 'boolean', 'stake_percent' => 'float'];
    public function listing() { return $this->belongsTo(Listing::class); }

    /** ارزش کل کسب‌وکار: فروش کامل ← قیمت درخواستی؛ در غیر این‌صورت ارزش‌گذاری اعلام‌شده یا برآورد از «قیمت ÷ سهم». */
    public function fullValuation(?int $asking): ?int {
        if ($this->sale_type === 'full_sale') return $asking ?: null;
        if ($this->valuation) return (int) $this->valuation;
        return $asking && $this->stake_percent > 0 ? (int) round($asking / ($this->stake_percent / 100)) : null;
    }
    /** @return array{valuation: ?int, margin: ?float, profit_multiple: ?float, revenue_multiple: ?float} */
    public function metrics(?int $asking): array {
        $v = $this->fullValuation($asking);
        return [
            'valuation' => $v,
            'margin' => $this->annual_revenue > 0 && $this->annual_profit !== null ? round($this->annual_profit / $this->annual_revenue * 100, 1) : null,
            'profit_multiple' => $v && $this->annual_profit > 0 ? round($v / $this->annual_profit, 1) : null,
            'revenue_multiple' => $v && $this->annual_revenue > 0 ? round($v / $this->annual_revenue, 1) : null,
        ];
    }
}

<?php
namespace App\Support;
use App\Models\Listing;

/** قیمت‌گذاری قابل تنظیم (config/pricing.php). همه‌چیز با مقدار صفر غیرفعال است. */
class Pricing {
    public static function businessListingFee(): int {
        return max(0, (int) config('pricing.business_listing_fee'));
    }

    /** آیا برای این آگهی هنوز هزینه ثبت پرداخت‌نشده است؟ */
    public static function listingFeeDue(Listing $l): bool {
        return in_array($l->type, ['business', 'startup'], true) && self::businessListingFee() > 0 && !$l->fee_paid_at;
    }

    /** درصد کمیسیون، محدود به 0..100 */
    public static function commissionPercent(): float {
        return min(100.0, max(0.0, (float) config('pricing.order_commission_percent')));
    }

    /** کمیسیون (تومان، گردشده) از مبلغ کل سفارش */
    public static function commission(int $total): int {
        return (int) round($total * self::commissionPercent() / 100);
    }
}

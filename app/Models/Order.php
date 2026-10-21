<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/** سفارش قطعی؛ مبالغ و شرایط، کپی ثابت پیش‌فاکتور پذیرفته‌شده‌اند */
class Order extends Model {
    public const STATUS = ['awaiting_advance' => 'در انتظار پیش‌پرداخت', 'preparing' => 'در حال آماده‌سازی', 'shipped' => 'ارسال‌شده', 'delivered' => 'تحویل‌شده (مهلت بازرسی)',
        'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده', 'disputed' => 'در حال رسیدگی به اختلاف'];
    protected $guarded = [];
    protected $casts = ['delivery_due_date' => 'date', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function offer() { return $this->belongsTo(Offer::class); }
    public function company() { return $this->belongsTo(Company::class); }           // فروشنده
    public function buyer() { return $this->belongsTo(User::class, 'buyer_user_id'); }
    public function buyerCompany() { return $this->belongsTo(Company::class, 'buyer_company_id'); }
    public function listing() { return $this->belongsTo(Listing::class); }
    public function rfq() { return $this->belongsTo(Rfq::class); }
    public function items() { return $this->hasMany(OrderItem::class)->orderBy('sort')->orderBy('id'); }
    public function payments() { return $this->hasMany(OrderPayment::class)->latest('id'); }
    public function events() { return $this->hasMany(OrderEvent::class)->orderBy('id'); }

    /** نقش کاربر نسبت به این سفارش: buyer | seller | admin | null */
    public function roleOf(?User $u): ?string {
        if (!$u) return null;
        if ($u->id === $this->buyer_user_id) return 'buyer';
        if ($u->id === $this->company->user_id) return 'seller';
        return $u->isAdmin() ? 'admin' : null;
    }
    public function remaining(): int { return max(0, $this->total - $this->paid_amount); }
    /** پایان مهلت بازرسی (فقط وقتی تحویل ثبت شده) */
    public function inspectionEndsAt() { return $this->delivered_at?->copy()->addDays($this->inspection_days); }
}

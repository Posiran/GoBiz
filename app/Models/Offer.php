<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/** پیش‌فاکتور رسمی فروشنده برای خریدار */
class Offer extends Model {
    public const STATUS = ['draft' => 'پیش‌نویس', 'sent' => 'ارسال‌شده', 'accepted' => 'پذیرفته‌شده', 'rejected' => 'ردشده', 'expired' => 'منقضی', 'withdrawn' => 'پس‌گرفته‌شده'];
    public const DELIVERY = ['ex_works' => 'تحویل در محل فروشنده', 'delivered' => 'تحویل در مقصد خریدار', 'carrier_depot' => 'تحویل به باربری / پایانه'];
    protected $guarded = [];
    protected $casts = ['valid_until' => 'date', 'sent_at' => 'datetime', 'responded_at' => 'datetime'];

    public function company() { return $this->belongsTo(Company::class); }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_user_id'); }
    public function listing() { return $this->belongsTo(Listing::class); }
    public function rfq() { return $this->belongsTo(Rfq::class); }
    public function inquiry() { return $this->belongsTo(Inquiry::class); }
    public function items() { return $this->hasMany(OfferItem::class)->orderBy('sort')->orderBy('id'); }
    public function order() { return $this->hasOne(Order::class); }

    public function isDraft(): bool { return $this->status === 'draft'; }
    /** ارسال‌شده و هنوز معتبر */
    public function isOpen(): bool { return $this->status === 'sent' && (!$this->valid_until || $this->valid_until->endOfDay()->isFuture()); }
    public function isSeller(User $u): bool { return $this->company->user_id === $u->id; }
    public function isBuyer(User $u): bool { return $this->buyer_user_id === $u->id; }
}

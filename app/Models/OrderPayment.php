<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/** سند پرداخت خریدار به فروشنده (جدا از payments که شارژ پلن سایت است) */
class OrderPayment extends Model {
    public const KIND = ['advance' => 'پیش‌پرداخت', 'installment' => 'قسط', 'balance' => 'تسویه مانده'];
    public const METHOD = ['bank_transfer' => 'حواله بانکی', 'card_to_card' => 'کارت به کارت', 'cheque' => 'چک', 'cash' => 'نقد', 'other' => 'سایر'];
    public const STATUS = ['pending' => 'در انتظار تأیید فروشنده', 'confirmed' => 'تأییدشده', 'rejected' => 'ردشده'];
    protected $guarded = [];
    protected $casts = ['paid_at' => 'date', 'reviewed_at' => 'datetime'];
    public function order() { return $this->belongsTo(Order::class); }
    public function user() { return $this->belongsTo(User::class); }
}

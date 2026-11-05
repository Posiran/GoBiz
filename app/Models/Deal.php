<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model {
    protected $guarded = [];
    protected $casts = ['valid_until' => 'date', 'decided_at' => 'datetime'];
    public const LABELS = ['loi_sent' => 'نامه قصد ارسال شد', 'negotiating' => 'در حال مذاکره', 'accepted' => 'پذیرفته شد', 'rejected' => 'رد شد', 'withdrawn' => 'خریدار انصراف داد'];
    public function listing() { return $this->belongsTo(Listing::class); }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
    public function messages() { return $this->hasMany(DealMessage::class)->oldest(); }
    public function isOpen(): bool { return in_array($this->status, ['loi_sent', 'negotiating'], true); }
    public function hasParty(int $userId): bool { return in_array($userId, [$this->buyer_id, $this->seller_id], true); }
}

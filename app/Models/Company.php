<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Company extends Model {
    protected $guarded = [];

    public function user() { return $this->belongsTo(User::class); }
    public function listings() { return $this->hasMany(Listing::class); }
    public function offers() { return $this->hasMany(Offer::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function verifications() { return $this->hasMany(CompanyVerification::class); }
    public function trustScore() { return $this->hasOne(TrustScore::class); }

    public function activeSubscription(): ?Subscription {
        return $this->subscriptions()->with('plan')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->latest('ends_at')->first();
    }

    public function listingLimit(): int {
        return $this->activeSubscription()?->plan->max_listings
            ?? (int) config('payment.free_listings', 3);
    }

    public function featuredSlots(): int {
        return $this->activeSubscription()?->plan->featured_slots ?? 0;
    }

    public function isVerified(): bool {
        return $this->verified_level >= 1;
    }
}

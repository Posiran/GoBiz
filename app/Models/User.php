<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    use HasFactory, Notifiable;
    protected $fillable = ['name', 'mobile', 'email', 'password'];   // role عمداً mass-assignable نیست
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['email_verified_at' => 'datetime', 'mobile_verified_at' => 'datetime', 'password' => 'hashed']; }
    public function company() { return $this->hasOne(Company::class); }
    public function receivedOffers() { return $this->hasMany(Offer::class, 'buyer_user_id'); }
    public function ordersAsBuyer() { return $this->hasMany(Order::class, 'buyer_user_id'); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestorProfile extends Model
{
    protected $guarded = [];
    protected $casts = ['verified' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function city() { return $this->belongsTo(City::class, 'preferred_city_id'); }
}

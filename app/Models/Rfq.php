<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Rfq extends Model {
    protected $guarded = [];
    public function user() { return $this->belongsTo(User::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function offers() { return $this->hasMany(Offer::class); }
    public function inquiries() { return $this->hasMany(Inquiry::class); }
}

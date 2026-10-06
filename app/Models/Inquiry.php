<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model {
    protected $guarded = [];
    public function from() { return $this->belongsTo(User::class, 'from_user_id'); }
    public function to() { return $this->belongsTo(User::class, 'to_user_id'); }
    public function listing() { return $this->belongsTo(Listing::class); }
    public function rfq() { return $this->belongsTo(Rfq::class); }
    public function replies() { return $this->hasMany(self::class, 'parent_id')->oldest(); }
}

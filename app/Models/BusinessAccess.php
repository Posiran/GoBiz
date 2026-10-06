<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BusinessAccess extends Model {
    protected $table = 'business_access_requests';
    protected $guarded = [];
    protected $casts = ['nda_accepted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    public function listing() { return $this->belongsTo(Listing::class); }
    public function user() { return $this->belongsTo(User::class); }
}

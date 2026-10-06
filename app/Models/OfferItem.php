<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OfferItem extends Model {
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['quantity' => 'float'];
    public function offer() { return $this->belongsTo(Offer::class); }
    public function listing() { return $this->belongsTo(Listing::class); }
}

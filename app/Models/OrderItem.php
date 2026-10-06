<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model {
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['quantity' => 'float'];
    public function order() { return $this->belongsTo(Order::class); }
}

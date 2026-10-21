<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model {
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    public function plan() { return $this->belongsTo(Plan::class); }
    public function company() { return $this->belongsTo(Company::class); }
}

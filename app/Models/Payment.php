<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    protected $guarded = [];
    protected $casts = ['paid_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function plan() { return $this->belongsTo(Plan::class); }
}

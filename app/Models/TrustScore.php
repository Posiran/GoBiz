<?php
namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class TrustScore extends Model
{
    protected $guarded = [];
    protected $casts = ['calculated_at' => 'datetime'];

    public function company() { return $this->belongsTo(Company::class); }
}

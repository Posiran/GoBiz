<?php
namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class CompanyVerification extends Model
{
    protected $guarded = [];
    protected $casts = ['reviewed_at' => 'datetime'];

    public function company() { return $this->belongsTo(Company::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}

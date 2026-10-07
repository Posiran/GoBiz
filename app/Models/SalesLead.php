<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesLead extends Model
{
    protected $guarded = [];
    protected $casts = [
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function listing() { return $this->belongsTo(Listing::class); }
    public function rfq() { return $this->belongsTo(Rfq::class); }
}

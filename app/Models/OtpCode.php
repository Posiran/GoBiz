<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model {
    protected $table = 'otps';
    protected $guarded = [];
    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];
}

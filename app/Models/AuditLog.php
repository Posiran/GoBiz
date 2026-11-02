<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model {
    public const UPDATED_AT = null;
    protected $guarded = [];
    protected $casts = ['meta' => 'array'];
    public function user() { return $this->belongsTo(User::class); }
}

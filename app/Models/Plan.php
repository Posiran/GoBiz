<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model {
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}

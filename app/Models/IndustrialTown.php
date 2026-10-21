<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class IndustrialTown extends Model {
    public $timestamps = false;
    protected $guarded = [];
    public function city() { return $this->belongsTo(City::class); }
    public function listings() { return $this->hasMany(Listing::class); }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Province extends Model {
    public $timestamps = false;
    protected $guarded = [];
    public function cities() { return $this->hasMany(City::class)->orderBy('name'); }
}

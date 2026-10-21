<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model {
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['options' => 'array', 'is_filterable' => 'boolean'];
    public function category() { return $this->belongsTo(Category::class); }
}

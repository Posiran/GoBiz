<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Category extends Model {
    public $timestamps = false;
    protected $guarded = [];
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order'); }
    public function specs() { return $this->hasMany(Attribute::class); }
    /** شناسه این دسته و تمام زیرمجموعه‌هایش */
    public function selfAndDescendantIds(): array {
        $ids = [$this->id];
        foreach ($this->children as $c) $ids = array_merge($ids, $c->selfAndDescendantIds());
        return $ids;
    }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FactoryDetail extends Model {
    public $timestamps = false;
    protected $primaryKey = 'listing_id';
    protected $guarded = [];
}

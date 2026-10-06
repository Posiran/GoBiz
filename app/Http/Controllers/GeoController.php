<?php
namespace App\Http\Controllers;
use App\Models\City;
use App\Models\Province;

class GeoController extends Controller {
    public function cities(Province $province) { return $province->cities()->get(['id', 'name']); }
    public function towns(City $city) { return $city->industrialTowns()->get(['id', 'name']); }
}

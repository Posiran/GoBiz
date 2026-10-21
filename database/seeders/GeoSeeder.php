<?php
namespace Database\Seeders;
use App\Support\IranGeo;
use Illuminate\Database\Seeder;

class GeoSeeder extends Seeder {
    public function run(): void {
        IranGeo::importCities(database_path('data/iran_cities.txt'));
        IranGeo::importTowns(database_path('data/industrial_towns.txt'));
    }
}

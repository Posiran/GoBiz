<?php
namespace App\Console\Commands;
use App\Support\IranGeo;
use Illuminate\Console\Command;

class ImportIranGeo extends Command {
    protected $signature = 'iran:import {type : cities یا towns} {file? : مسیر فایل (پیش‌فرض: database/data)}';
    protected $description = 'ورود استان/شهر یا شهرک‌های صنعتی از فایل متنی';

    public function handle(): int {
        $type = $this->argument('type');
        if (!in_array($type, ['cities', 'towns'], true)) { $this->error('type باید cities یا towns باشد'); return 1; }
        $file = $this->argument('file') ?: database_path($type === 'cities' ? 'data/iran_cities.txt' : 'data/industrial_towns.txt');
        if (!is_file($file)) { $this->error("فایل پیدا نشد: $file"); return 1; }
        $n = $type === 'cities' ? IranGeo::importCities($file) : IranGeo::importTowns($file);
        $this->info("$n رکورد پردازش شد."); return 0;
    }
}

<?php
namespace Database\Seeders;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder {
    /** مبالغ نمونه به «تومان»؛ پیش از انتشار با قیمت واقعی خودتان جایگزین کنید */
    public function run(): void {
        foreach ([
            ['برنزی', 900000, 10, 0, 90], ['نقره‌ای', 2400000, 30, 3, 180], ['طلایی', 4500000, 100, 10, 365],
        ] as [$name, $price, $max, $feat, $days])
            Plan::updateOrCreate(['name' => $name], ['price' => $price, 'max_listings' => $max, 'featured_slots' => $feat, 'duration_days' => $days, 'is_active' => true]);
    }
}

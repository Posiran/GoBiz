<?php
namespace App\Support;
use App\Models\City;
use App\Models\IndustrialTown;
use App\Models\Province;

/** ورود داده جغرافیایی از فایل متنی (idempotent: اجرای دوباره رکورد تکراری نمی‌سازد) */
class IranGeo {
    private static function lines(string $path): array {
        return array_values(array_filter(array_map('trim', file($path, FILE_IGNORE_NEW_LINES)),
            fn ($l) => $l !== '' && !str_starts_with($l, '#')));
    }
    private static function split(string $s): array {
        return array_values(array_filter(array_map('trim', preg_split('/[،,]/u', $s))));
    }
    public static function importCities(string $path): int {
        $n = 0;
        foreach (self::lines($path) as $line) {
            [$prov, $list] = array_map('trim', explode(':', $line, 2));
            $p = Province::firstOrCreate(['name' => $prov]);
            foreach (self::split($list) as $c) { City::firstOrCreate(['province_id' => $p->id, 'name' => $c]); $n++; }
        }
        return $n;
    }
    public static function importTowns(string $path): int {
        $n = 0;
        foreach (self::lines($path) as $line) {
            [$prov, $city, $list] = array_pad(array_map('trim', explode('|', $line, 3)), 3, '');
            $c = City::where('name', $city)->whereHas('province', fn ($q) => $q->where('name', $prov))->first();
            if (!$c) { fwrite(STDERR, "ردشد (شهر پیدا نشد): $prov | $city\n"); continue; }
            foreach (self::split($list) as $t) {
                $name = str_starts_with($t, 'شهرک') ? $t : "شهرک صنعتی $t";
                IndustrialTown::firstOrCreate(['city_id' => $c->id, 'name' => $name]); $n++;
            }
        }
        return $n;
    }
}

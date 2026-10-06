<?php
namespace App\Payment;

class Gateways {
    public static function make(string $key): Gateway {
        return match ($key) {
            'zarinpal' => new ZarinPal, 'behpardakht' => new Behpardakht, 'sadad' => new Sadad,
            default => throw new \InvalidArgumentException("unknown gateway $key"),
        };
    }
    /** @return array<string,Gateway> فعال در تنظیمات و دارای اطلاعات کامل */
    public static function available(): array {
        $out = [];
        foreach (config('payment.enabled') as $k) {
            try { $g = self::make($k); } catch (\InvalidArgumentException) { continue; }
            if ($g->configured()) $out[$k] = $g;
        }
        return $out;
    }
}

<?php
namespace App\Support;

class Fa {
    private const FA = '۰۱۲۳۴۵۶۷۸۹';
    private const AR = '٠١٢٣٤٥٦٧٨٩';

    public static function digits(string|int|null $s): string {
        return strtr((string) $s, array_combine(str_split('0123456789'), mb_str_split(self::FA)));
    }
    /** ارقام فارسی/عربی ← لاتین (برای ورودی فرم‌ها) */
    public static function latin(?string $s): ?string {
        if ($s === null) return null;
        $d = str_split('0123456789');
        return strtr($s, array_combine(mb_str_split(self::FA), $d) + array_combine(mb_str_split(self::AR), $d));
    }
    /** ورودی عددی فرم: «۴٬۸۰۰٬۰۰۰» ← 4800000 ، «۱۲٫۵» ← 12.5 */
    public static function number(?string $s): ?string {
        if ($s === null) return null;
        return str_replace([',', '٬', '،', ' '], '', strtr((string) self::latin($s), ['٫' => '.']));
    }
    public static function price(?int $v): string {
        return $v ? self::digits(number_format($v)) . ' تومان' : 'توافقی';
    }

    /** تاریخ شمسی yyyy/mm/dd با ارقام فارسی */
    public static function jdate(\DateTimeInterface|string|null $d): string {
        if (!$d) return '';
        // همیشه به وقت تهران (UTC+3:30) تبدیل می‌شود، حتی اگر timezone برنامه UTC باشد؛ از نسخه immutable استفاده می‌شود تا ویژگی مدل تغییر نکند
        $c = \DateTimeImmutable::createFromInterface($d instanceof \DateTimeInterface ? $d : new \DateTime($d))->setTimezone(new \DateTimeZone('Asia/Tehran'));
        $gy = (int) $c->format('Y'); $gm = (int) $c->format('n'); $gd = (int) $c->format('j');
        $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + 365 * $gy + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1];
        $jy = -1595 + 33 * intdiv($days, 12053); $days %= 12053;
        $jy += 4 * intdiv($days, 1461); $days %= 1461;
        if ($days > 365) { $jy += intdiv($days - 1, 365); $days = ($days - 1) % 365; }
        if ($days < 186) { $jm = 1 + intdiv($days, 31); $jd = 1 + $days % 31; }
        else { $jm = 7 + intdiv($days - 186, 30); $jd = 1 + ($days - 186) % 30; }
        return self::digits(sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
    }
}

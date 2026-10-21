<?php
namespace App\Support;
use App\Models\OtpCode;

/** OTP پنج‌رقمی با اعتبار ۵ دقیقه و حداکثر ۵ تلاش؛ فقط هش کد ذخیره می‌شود. purpose: verify|login|reset */
class Otp {
    /** @return string|null پیام خطا، یا null در صورت موفقیت */
    public static function send(string $mobile, string $purpose): ?string {
        $last = OtpCode::where(['mobile' => $mobile, 'purpose' => $purpose])->latest()->first();
        if ($last && $last->created_at->gt(now()->subSeconds(60))) return 'لطفاً یک دقیقه صبر کنید و دوباره تلاش کنید.';
        if (OtpCode::where('mobile', $mobile)->where('created_at', '>', now()->subHour())->count() >= 5) return 'تعداد درخواست‌ها زیاد است؛ بعداً تلاش کنید.';
        $code = (string) random_int(10000, 99999);
        $otp = OtpCode::create(['mobile' => $mobile, 'purpose' => $purpose, 'code_hash' => self::hash($mobile, $code), 'expires_at' => now()->addMinutes(5)]);
        if (!Ippanel::pattern($mobile, config('ippanel.patterns.otp'), ['code' => $code])) {
            $otp->delete();
            return 'ارسال پیامک ناموفق بود؛ چند لحظه بعد دوباره تلاش کنید.';
        }
        return null;
    }
    public static function check(string $mobile, string $purpose, string $code): bool {
        $otp = OtpCode::where(['mobile' => $mobile, 'purpose' => $purpose])->whereNull('used_at')->where('expires_at', '>', now())->latest()->first();
        if (!$otp || $otp->attempts >= 5) return false;
        $otp->increment('attempts');
        if (!hash_equals($otp->code_hash, self::hash($mobile, (string) Fa::latin(trim($code))))) return false;
        $otp->update(['used_at' => now()]);
        return true;
    }
    private static function hash(string $mobile, string $code): string {
        return hash_hmac('sha256', "$mobile|$code", (string) config('app.key'));
    }
}

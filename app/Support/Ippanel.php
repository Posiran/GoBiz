<?php
namespace App\Support;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** ارسال پیامک پترنی با IPPanel Edge API */
class Ippanel {
    /** 09123456789 ← +989123456789 */
    public static function e164(string $mobile): string {
        return '+98' . ltrim(preg_replace('/\D/', '', $mobile), '0');
    }

    public static function pattern(string $mobile, ?string $patternCode, array $params): bool {
        $c = config('ippanel');
        if (!$c['token'] || !$patternCode || !$c['from']) {
            if (app()->isLocal()) { Log::info("[ippanel dev] $mobile", $params); return true; }   // فقط توسعه: به‌جای ارسال، لاگ می‌شود
            Log::warning('ippanel is not configured');
            return false;
        }
        try {
            $res = Http::withHeaders(['Authorization' => $c['token']])->acceptJson()->timeout(10)
                ->post(rtrim($c['base_url'], '/') . '/api/send', [
                    'sending_type' => 'pattern', 'from_number' => $c['from'], 'code' => $patternCode,
                    'recipients' => [self::e164($mobile)], 'params' => array_map('strval', $params),
                ]);
            $ok = $res->successful() && $res->json('meta.status') === true;
            if (!$ok) Log::error('ippanel send failed', ['http' => $res->status(), 'message' => $res->json('meta.message'), 'code' => $res->json('meta.message_code')]);
            return $ok;
        } catch (\Throwable $e) {
            Log::error('ippanel exception: ' . $e->getMessage());
            return false;
        }
    }
}

<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * محافظ نسخه تست (test.gobiz.ir): رمز ساده HTTP و جلوگیری از ایندکس موتورهای جستجو.
 * فقط وقتی STAGING_PASSWORD تنظیم شده باشد فعال است؛ در سایت اصلی خالی بماند.
 */
class StagingGuard {
    public function handle(Request $request, Closure $next): Response {
        $password = (string) config('gobiz.staging.password');
        if ($password === '') return $next($request);

        // بازگشت از درگاه پرداخت (بانک رمز HTTP ندارد) و بررسی سلامت بدون رمز
        $open = $request->is('payment/callback/*') || $request->is('up');

        $ok = hash_equals((string) config('gobiz.staging.user'), (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword());

        if (! $open && ! $ok) {
            return response('نسخه تست گوبیز — نام کاربری و رمز لازم است.', 401, [
                'WWW-Authenticate' => 'Basic realm="Gobiz staging", charset="UTF-8"',
                'Content-Type' => 'text/plain; charset=UTF-8',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}

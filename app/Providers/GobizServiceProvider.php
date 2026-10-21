<?php
namespace App\Providers;

use App\Http\Middleware\StagingGuard;
use Illuminate\Routing\Events\Routing;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/**
 * ریپو فقط فایل‌های اختصاصی است و bootstrap/app.php مال اسکلت لاراول است؛ برای همین تنظیمات سراسری
 * اینجا ثبت می‌شود. scripts/bootstrap-laravel.sh این Provider را در bootstrap/providers.php ثبت می‌کند.
 */
class GobizServiceProvider extends ServiceProvider {
    public function boot(): void {
        // محافظ نسخه تست روی همه صفحه‌های وب (بدون STAGING_PASSWORD هیچ کاری نمی‌کند). /up بیرون گروه web است و باز می‌ماند.
        // گروه web را Kernel لاراول ۱۱+ پس از boot دوباره می‌سازد؛ پس درست قبل از مسیریابی اضافه می‌کنیم.
        Event::listen(Routing::class, function () {
            $this->app->make(Router::class)->prependMiddlewareToGroup('web', StagingGuard::class);
        });

        // آدرس بازگشت از درگاه پرداخت باید https باشد
        if (! $this->app->environment(['local', 'testing']) && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}

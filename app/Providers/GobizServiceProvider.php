<?php
namespace App\Providers;

use App\Http\Middleware\StagingGuard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/**
 * ریپو فقط فایل‌های اختصاصی است و bootstrap/app.php مال اسکلت لاراول است؛ برای همین تنظیمات سراسری
 * اینجا ثبت می‌شود. scripts/bootstrap-laravel.sh این Provider را در bootstrap/providers.php ثبت می‌کند.
 */
class GobizServiceProvider extends ServiceProvider {
    public function boot(): void {
        // محافظ نسخه تست روی همه درخواست‌ها (بدون STAGING_PASSWORD هیچ کاری نمی‌کند)
        $this->app->make(Kernel::class)->prependMiddleware(StagingGuard::class);

        // آدرس بازگشت از درگاه پرداخت باید https باشد
        if (! $this->app->environment(['local', 'testing']) && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}

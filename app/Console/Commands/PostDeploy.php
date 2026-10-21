<?php
namespace App\Console\Commands;

use App\Models\Listing;
use App\Models\Province;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * کارهای پس از هر استقرار؛ deploy/deploy.php آن را روی نسخه جدید اجرا می‌کند و فقط اگر موفق باشد سایت را سوییچ می‌کند.
 * اجرای چندباره بی‌خطر است. migrationهای پروژه نباید داده یا ستون را حذف کنند (برگشت نسخه، دیتابیس را برنمی‌گرداند).
 */
class PostDeploy extends Command {
    protected $signature = 'gobiz:post-deploy {--skip-cache : بدون ساخت کش (برای تست)}';
    protected $description = 'مهاجرت، داده پایه و کش پس از هر استقرار';

    public function handle(): int {
        $this->call('migrate', ['--force' => true]);

        // بار اول: استان/شهر، دسته‌ها و پلن‌ها. بعدها فقط مشخصات فنی دسته‌های تازه (پلن‌هایی که مدیر ویرایش کرده دست‌نخورده می‌مانند)
        $this->call('db:seed', Province::query()->exists()
            ? ['--class' => 'CategorySeeder', '--force' => true]
            : ['--force' => true]);

        // مدیر اولیه از .env (ADMIN_MOBILE)؛ فقط اگر هنوز مدیری وجود ندارد
        $admin = (string) config('gobiz.deploy.admin_mobile');
        if ($admin !== '' && ! User::where('role', 'admin')->exists()) {
            $this->call('app:make-admin', ['mobile' => $admin]);
        }

        // نسخه تست: داده نمایشی فقط وقتی هیچ آگهی‌ای نیست
        if (! app()->isProduction() && config('gobiz.deploy.demo_data') && ! Listing::query()->exists()) {
            $this->call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
        }

        if (! $this->option('skip-cache')) {
            foreach (['config:cache', 'route:cache', 'view:cache', 'event:cache'] as $cmd) {
                $this->call($cmd);
            }
        }

        $this->info('پس از استقرار: انجام شد.');
        return self::SUCCESS;
    }
}

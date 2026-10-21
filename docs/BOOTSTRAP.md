# راه‌اندازی و CI

این ریپو فقط فایل‌های اختصاصی Gobiz است و کل لاراول را ندارد.

## راه‌اندازی محلی
```bash
bash scripts/bootstrap-laravel.sh   # اسکلت لاراول ۱۲ را (پیش‌فرض؛ با `LARAVEL_VERSION="^13.0"` نسخه دیگر) بدون بازنویسی فایل‌های ریپو کنارش می‌گذارد
# در .env: DB_CONNECTION=mysql و اطلاعات دیتابیس
php artisan migrate --seed
php artisan test
```
اسکریپت نیاز به Composer، PHP 8.3+ و افزونه‌های `mbstring`، `intl`، `pdo_mysql` و (برای به‌پرداخت ملت) `soap` دارد.

## CI و استقرار (GitHub Actions)
فایل `.github/workflows/ci.yml` (نام: *Test & Build*) روی هر push به `develop`، `main` و شاخه‌های `phase-*` و هر pull request به `develop`/`main` اجرا می‌شود، با MySQL 8 و PHP 8.3، هم روی لاراول ۱۲ و هم ۱۳ (ماتریس):

1. بررسی نحو همه فایل‌های PHP
2. ادغام با اسکلت لاراول (`scripts/bootstrap-laravel.sh`)
3. `migrate:fresh` ← `migrate:rollback` ← `migrate:fresh` (تأیید اجرا و rollback همه migrationها)
4. `db:seed` و `DemoSeeder`
5. `php artisan test` (تست‌های Unit و Feature روی MySQL)
6. شبیه‌سازی پروداکشن: `gobiz:post-deploy` با کش route/config/view/event

فقط روی `develop` و `main` یک مرحله دوم هم اجرا می‌شود: همان درخت تست‌شده با `composer install --no-dev` به نسخه آماده تبدیل و در `release/staging` (از `develop`) یا `release/production` (از `main`) قرار می‌گیرد. هاست با `deploy/deploy.php` آن را می‌گیرد؛ راهنما: [deploy-cpanel.md](deploy-cpanel.md).

## محدودیت‌ها
- تست Feature فعلی: دودآزمایی صفحه‌ها و دسترسی نقش‌ها، سرویس‌های امتیاز/تطبیق و محافظ نسخه تست. جریان کامل ثبت‌نام با OTP، ثبت آگهی، پیش‌فاکتور و سفارش هنوز تست Feature ندارد.
- ارتباط واقعی با زرین‌پال، ملت، سداد و IPPanel در CI آزمایش نمی‌شود.

## چرا لاراول ۱۲ و نه ۱۱
اولین اجرای CI نشان داد Composer نصب هر نسخه ۱۱.x را به‌خاطر هشدارهای امنیتی رد می‌کند و پشتیبانی امنیتی لاراول ۱۱ از ۱۲ مارس ۲۰۲۶ تمام شده است. ([Laravel Support Policy](https://laravel.com/docs/12.x/releases))

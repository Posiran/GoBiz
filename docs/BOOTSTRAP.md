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

## CI (GitHub Actions)
فایل `.github/workflows/ci.yml` روی هر push به `main` و شاخه‌های `phase-*` و هر pull request این مراحل را اجرا می‌کند، با MySQL 8 و PHP 8.3، هم روی لاراول ۱۲ و هم ۱۳ (ماتریس):

1. بررسی نحو همه فایل‌های PHP
2. ادغام با اسکلت لاراول (`scripts/bootstrap-laravel.sh`)
3. `migrate:fresh` ← `migrate:rollback` ← `migrate` (تأیید اجرا و rollback همه migrationها)
4. `db:seed`
5. `php artisan test`

این اولین اجرای واقعی پروژه روی MySQL است. نتیجه هر مرحله جدا در تب Actions دیده می‌شود.

## محدودیت‌ها
- تست‌های فعلی همه Unit و بدون دیتابیس‌اند؛ تست Feature با MySQL هنوز نوشته نشده است.
- ارتباط واقعی با زرین‌پال، ملت، سداد و IPPanel در CI آزمایش نمی‌شود.

## چرا لاراول ۱۲ و نه ۱۱
اولین اجرای CI نشان داد Composer نصب هر نسخه ۱۱.x را به‌خاطر هشدارهای امنیتی رد می‌کند و پشتیبانی امنیتی لاراول ۱۱ از ۱۲ مارس ۲۰۲۶ تمام شده است. ([Laravel Support Policy](https://laravel.com/docs/12.x/releases))

# استقرار خودکار گوبیز روی هاست اشتراکی cPanel

این راهنما یک بار انجام می‌شود. بعد از آن هر تغییری که در GitHub ثبت شود، خودکار روی هاست می‌نشیند:

| شاخه | نشانی | چه زمانی به‌روز می‌شود |
|---|---|---|
| `develop` | `test.gobiz.ir` (نسخه تست) | چند دقیقه پس از هر تغییر و قبولی تست‌ها |
| `main` | `gobiz.ir` (سایت اصلی) | پس از Merge کردن develop در main (با تأیید شما) |

```
GitHub (develop/main) ──► GitHub Actions: تست روی MySQL ──► شبیه‌سازی پروداکشن (migrate، کش route/config/view)
                                                              │
                                              ساخت نسخه آماده (اسکلت لاراول + کد + vendor)
                                                              │
                                       شاخه release/staging یا release/production
                                                              │
هاست cPanel ◄── Cron هر ۵ دقیقه: deploy.php نسخه تازه را می‌گیرد، مهاجرت و کش را اجرا
                و فقط اگر همه چیز موفق بود سایت را به نسخه جدید سوییچ می‌کند
```

روی هاست به SSH، ترمینال، Git یا Composer نیازی نیست. اگر وسط کار خطایی رخ دهد (تست، ساخت، مهاجرت)، سایت روی نسخه قبلی می‌ماند.

> ریپوی گوبیز فقط فایل‌های اختصاصی پروژه است و کل لاراول را ندارد. مرحله ساخت در GitHub اسکلت لاراول را با کد پروژه ترکیب می‌کند؛ بنابراین نسخه‌ای که روی هاست می‌نشیند همان است که تست شده (همان `composer.lock`).

---

## ۱. آماده‌سازی هاست در cPanel

### نسخه PHP
**Select PHP Version** (یا MultiPHP Manager):
- نسخه **8.3** یا بالاتر
- افزونه‌ها: `curl`، `zip`، `mbstring`، `pdo_mysql`، `fileinfo`، `openssl`، `intl`
- برای **به‌پرداخت ملت** افزونه `soap` هم لازم است.

### پایگاه داده
**MySQL Databases** — دو پایگاه داده (MySQL 8) و دو کاربر جدا بسازید و هر کاربر را با دسترسی کامل (ALL PRIVILEGES) به پایگاه خودش وصل کنید:
- `USER_gobiz` برای سایت اصلی
- `USER_gobiztest` برای نسخه تست

### زیردامنه تست
**Domains** (یا Subdomains) — زیردامنه `test.gobiz.ir` را بسازید. پوشه پیش‌فرض آن (`test.gobiz.ir`) را تغییر ندهید؛ اسکریپت خودش آن را به نسخه فعال وصل می‌کند.

### SSL
**SSL/TLS Status** — AutoSSL را برای `gobiz.ir`، `www.gobiz.ir` و `test.gobiz.ir` فعال کنید. نشانی بازگشت از بانک باید HTTPS باشد.

## ۲. شاخه‌ها در GitHub

شاخه `develop` را (یک بار) از `main` بسازید. روال کار:
1. تغییرات در `develop` (یا Pull Request به آن) ثبت می‌شود ← نسخه تست به‌روز می‌شود.
2. پس از بررسی روی `test.gobiz.ir`، از `develop` به `main` یک Pull Request بسازید و Merge کنید ← سایت اصلی به‌روز می‌شود.

پیشنهاد: در **Settings ← Branches** برای `main` قانون «Require status checks to pass» را با چک `test` فعال کنید.

> مخزن گوبیز عمومی است. شاخه‌های `release/*` هم عمومی‌اند؛ بنابراین **هیچ رمز یا کلیدی** در مخزن نگذارید. رمزها فقط در فایل `.env` روی هاست هستند.

## ۳. گذاشتن اسکریپت روی هاست

با **File Manager** در پوشه خانه (کنار `public_html`، نه داخل آن) پوشه `gobiz` را بسازید و این دو فایل را در آن بگذارید:

```
/home/USER/gobiz/deploy.php            ← از deploy/deploy.php مخزن
/home/USER/gobiz/deploy-config.php     ← از deploy/deploy-config.example.php
```

مخزن عمومی است و توکن لازم نیست. مسیرهای `public_link` را فقط اگر نام پوشه‌های دامنه در هاست شما فرق دارد تغییر دهید.

> `public_link` باید **پوشه وب همین دامنه** باشد (cPanel ← Domains ← Document Root؛ برای دامنه افزوده معمولاً `~/gobiz.ir`). اسکریپت فقط پوشه خالی یا دارای فایل‌های پیش‌فرض cPanel را جایگزین می‌کند و به پوشه‌ای که فایل دارد یا `public_html` (پوشه دامنه اصلی هاست) دست نمی‌زند؛ در آن صورت فقط هشدار در گزارش می‌نویسد.

## ۴. Cron Jobs

**Cron Jobs** در cPanel — مسیر PHP را از همان صفحه یا از پشتیبانی هاست بپرسید؛ معمولاً `/usr/local/bin/php` است. اگر چند نسخه PHP دارید، مسیری را بدهید که نسخه 8.3 را اجرا می‌کند (مثلاً `/opt/alt/php83/usr/bin/php`).

| زمان | فرمان |
|---|---|
| `*/5 * * * *` | `/usr/local/bin/php /home/USER/gobiz/deploy.php staging` |
| `*/5 * * * *` | `/usr/local/bin/php /home/USER/gobiz/deploy.php production` |
| `* * * * *` | `/usr/local/bin/php /home/USER/gobiz/production/current/artisan schedule:run` |
| `* * * * *` | `/usr/local/bin/php /home/USER/gobiz/staging/current/artisan schedule:run` |

در فرمان‌ها از ‎`>>`‎ یا ‎`2>&1`‎ استفاده نکنید (برخی هاست‌ها اجازه نمی‌دهند)؛ اسکریپت گزارش را خودش در `gobiz/cron.log` می‌نویسد. Cron Email را خالی بگذارید.

دو فرمان آخر زمان‌بند لاراول است (منقضی‌کردن پیش‌فاکتورها و تکمیل خودکار سفارش‌های تحویل‌شده: `procurement:housekeeping`).

## ۵. اولین اجرا و فایل ‎.env

حداکثر ۵ دقیقه پس از تنظیم Cron، اسکریپت فایل `gobiz/production/shared/.env` و `gobiz/staging/shared/.env` را می‌سازد و متوقف می‌شود (در `gobiz/cron.log` می‌بینید). این فایل‌ها را با File Manager ویرایش کنید.

**سایت اصلی** (`production/shared/.env`):
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gobiz.ir
APP_TIMEZONE=Asia/Tehran
APP_LOCALE=fa
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=USER_gobiz
DB_USERNAME=USER_gobiz
DB_PASSWORD=...
IPPANEL_TOKEN=...
IPPANEL_FROM=+98...
IPPANEL_PATTERN_OTP=...
PAYMENT_ENABLED=zarinpal,behpardakht
ZARINPAL_MERCHANT_ID=...
BEHPARDAKHT_TERMINAL_ID=...
BEHPARDAKHT_USERNAME=...
BEHPARDAKHT_PASSWORD=...
ADMIN_MOBILE=09xxxxxxxxx
```

**نسخه تست** (`staging/shared/.env`):
```
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://test.gobiz.ir
APP_TIMEZONE=Asia/Tehran
APP_LOCALE=fa
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=USER_gobiztest
DB_USERNAME=USER_gobiztest
DB_PASSWORD=...
STAGING_PASSWORD=یک-رمز-دلخواه
SMS_ONLY_TO=09xxxxxxxxx           # فقط این شماره‌ها پیامک واقعی می‌گیرند؛ برای بقیه فقط لاگ می‌شود
IPPANEL_TOKEN=...                 # اختیاری؛ بدون توکن کد ورود در storage/logs/laravel.log نوشته می‌شود
PAYMENT_ENABLED=zarinpal
ZARINPAL_SANDBOX=true             # درگاه آزمایشی؛ برای ملت و سداد بدون اطلاعات واقعی پرداخت غیرفعال می‌ماند
DEMO_DATA=true                    # آگهی و شرکت نمایشی (فقط وقتی هیچ آگهی‌ای نیست)
ADMIN_MOBILE=09xxxxxxxxx
```

- `APP_KEY` خالی بماند؛ خودکار ساخته می‌شود. **بعد از آن هرگز عوضش نکنید** و از فایل ‎.env نسخه پشتیبان بگیرید.
- `ADMIN_MOBILE`: در اولین استقرار، این شماره مدیر سایت می‌شود (فقط اگر هنوز مدیری نباشد). با همین شماره از `/login` (ورود با کد پیامکی) وارد شوید و پنل در `/admin` است.
- **به‌پرداخت ملت:** بانک معمولاً IP سرور را ثبت می‌کند؛ آدرس بازگشت `https://gobiz.ir/payment/callback/behpardakht` است (برای زرین‌پال `.../zarinpal` و برای سداد `.../sadad`).
- پایگاه داده‌ها و جدول‌های پایه (استان/شهر، دسته‌ها، پلن‌ها) در اولین استقرار خودکار ساخته و پر می‌شوند.

حداکثر ۳۰ دقیقه بعد، استقرار دوباره امتحان می‌شود. برای اجرای فوری، فایلی خالی با نام `FORCE` در پوشه `gobiz/staging` یا `gobiz/production` بسازید.

## ۶. بررسی

- گزارش استقرار: `gobiz/production/deploy.log` و `gobiz/staging/deploy.log`
- گزارش کامل وضعیت: فایلی خالی با نام `STATUS` در پوشه محیط بسازید؛ در اجرای بعدی Cron گزارش در `cron.log` نوشته می‌شود.
- نسخه تست با نام کاربری `gobiz` و رمز `STAGING_PASSWORD` باز می‌شود و موتورهای جستجو آن را نمی‌بینند.
- نسخه ساخته‌شده در GitHub: `release.json` در ریشه نسخه (شناسه commit و زمان ساخت).

## روال روزانه

1. تغییرات در شاخه `develop` ثبت می‌شود ← GitHub Actions تست می‌کند ← حداکثر حدود ۱۰ دقیقه بعد `test.gobiz.ir` به‌روز است.
2. پس از بررسی، **Pull Request** از `develop` به `main` بسازید و **Merge** کنید ← سایت اصلی به‌روز می‌شود.
3. وضعیت تست‌ها و ساخت در زبانه **Actions** مخزن دیده می‌شود. اگر تستی رد شود، چیزی روی هاست نمی‌رود.

## برگشت به نسخه قبلی

در File Manager فایلی خالی با نام `ROLLBACK` در `gobiz/production` بسازید؛ در اجرای بعدی Cron (حداکثر ۵ دقیقه) سایت به نسخه قبلی برمی‌گردد. سه نسخه آخر نگه داشته می‌شوند.

**تغییرات پایگاه داده برگردانده نمی‌شود.** برای همین migrationهای گوبیز نباید ستون یا جدول یا داده را حذف کنند؛ فقط اضافه کنند. مهاجرتی که چیزی را حذف می‌کند باید جدا و با برنامه‌ریزی انجام شود.

## بازیابی ‎.env

اگر فایل ‎.env به‌اشتباه خراب شد: فایلی خالی با نام `RESTORE_ENV` در پوشه محیط بسازید. کلید برنامه و اطلاعات پایگاه داده از تنظیمات کش‌شده نسخه فعال (که سالم کار می‌کند) به ‎.env برگردانده می‌شود (نسخه قبلی با پسوند `.bak-...` می‌ماند).

## اگر مشکلی پیش آمد

| نشانه در cron.log یا deploy.log | راه‌حل |
|---|---|
| `درخواست به GitHub ناموفق بود (0)` | هاست به api.github.com دسترسی ندارد؛ از پشتیبانی هاست باز کردن دسترسی خروجی به `api.github.com` و `codeload.github.com` را بخواهید |
| `(401)` یا `(404)` | شاخه release هنوز ساخته نشده (زبانه Actions را ببینید)؛ یا اگر توکن گذاشته‌اید، اشتباه یا منقضی است |
| `افزونه PHP «...» فعال نیست` | از Select PHP Version فعالش کنید؛ مسیر PHP در Cron هم باید همان نسخه 8.3 باشد |
| `ساخت لینک ... ممکن نشد` | هاست اجازه symlink نمی‌دهد؛ از پشتیبانی بخواهید یا Document Root دامنه را دستی روی `gobiz/production/current/public` بگذارند |
| `gobiz:post-deploy ... متوقف شد` | معمولاً اطلاعات پایگاه داده در ‎.env اشتباه است؛ متن خطای migrate در `deploy.log` آمده است |
| سایت خطای ۵۰۰ می‌دهد | `gobiz/production/shared/storage/logs/laravel.log` را ببینید |
| پیامک ارسال نمی‌شود | `IPPANEL_*` و پترن‌ها را در ‎.env بررسی کنید؛ در نسخه تست شماره باید در `SMS_ONLY_TO` باشد |

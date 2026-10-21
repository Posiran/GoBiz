#!/usr/bin/env bash
# این ریپو فقط فایل‌های اختصاصی Gobiz است. این اسکریپت آن‌ها را روی یک اسکلت تازه Laravel می‌گذارد.
# هیچ فایل موجودی در ریپو بازنویسی نمی‌شود (cp -n). اجرای دوباره بی‌خطر است.
#
# استفاده:  bash scripts/bootstrap-laravel.sh
# نسخه لاراول: LARAVEL_VERSION="^12.0" (پیش‌فرض). لاراول ۱۱ دیگر پشتیبانی امنیتی ندارد و Composer نصبش را رد می‌کند.
set -euo pipefail
cd "$(dirname "$0")/.."

if [ -f artisan ]; then
  echo "artisan already present: skipping skeleton merge"
else
  tmp="$(mktemp -d)"
  trap 'rm -rf "$tmp"' EXIT
  composer create-project "laravel/laravel:${LARAVEL_VERSION:-^12.0}" "$tmp/app" --no-interaction --prefer-dist
  cp -rn "$tmp/app/." .
fi

# ثبت Provider پروژه (محافظ نسخه تست و ...) در bootstrap/providers.php اسکلت
if ! grep -q 'GobizServiceProvider' bootstrap/providers.php; then
  sed -i 's|AppServiceProvider::class,|&\n    \\App\\Providers\\GobizServiceProvider::class,|' bootstrap/providers.php
  grep -q 'GobizServiceProvider' bootstrap/providers.php || { echo "ERROR: could not register GobizServiceProvider in bootstrap/providers.php" >&2; exit 1; }
fi

# تست‌ها باید روی MySQL اجرا شوند (ENUM و fullText)، نه sqlite درون‌حافظه‌ای که phpunit.xml اسکلت پیش‌فرض می‌گذارد
if [ -f phpunit.xml ]; then
  sed -i '/name="DB_CONNECTION"/d;/name="DB_DATABASE"/d' phpunit.xml
fi

# .env.example اسکلت با کلیدهای پروژه (APP_NAME، APP_URL، DB_CONNECTION=mysql، ...) هماهنگ می‌شود؛
# نسخه‌ی release هم از همین فایل .env اولیه هاست را می‌سازد
if [ -f docs/env.example ]; then
  php scripts/merge-env.php .env.example docs/env.example
fi

[ -f .env ] || cp .env.example .env
# در .env موجود فقط کلیدهای تازه اضافه می‌شود (مقدارهای شما دست‌نخورده می‌مانند)
[ -f docs/env.example ] && php scripts/merge-env.php .env docs/env.example --only-missing

php artisan key:generate --force --no-interaction
echo "bootstrap done. Next: set DB_* in .env, then: php artisan migrate --seed"

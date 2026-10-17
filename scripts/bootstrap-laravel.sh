#!/usr/bin/env bash
# این ریپو فقط فایل‌های اختصاصی Gobiz است. این اسکریپت آن‌ها را روی یک اسکلت تازه Laravel 11 می‌گذارد.
# هیچ فایل موجودی در ریپو بازنویسی نمی‌شود (cp -n). اجرای دوباره بی‌خطر است.
#
# استفاده:  bash scripts/bootstrap-laravel.sh
set -euo pipefail
cd "$(dirname "$0")/.."

if [ -f artisan ]; then
  echo "artisan already present: skipping skeleton merge"
else
  tmp="$(mktemp -d)"
  trap 'rm -rf "$tmp"' EXIT
  composer create-project "laravel/laravel:^11.0" "$tmp/app" --no-interaction --prefer-dist
  cp -rn "$tmp/app/." .
fi

[ -f .env ] || cp .env.example .env

# کلیدهای اختصاصی پروژه را فقط اگر در .env نیستند اضافه کن (مقدارهای موجود دست‌نخورده می‌مانند)
if [ -f docs/env.example ]; then
  while IFS= read -r line; do
    case "$line" in ''|\#*) continue ;; esac
    key="${line%%=*}"
    grep -q "^${key}=" .env || echo "$line" >> .env
  done < docs/env.example
fi

php artisan key:generate --force --no-interaction
echo "bootstrap done. Next: set DB_* in .env, then: php artisan migrate --seed"

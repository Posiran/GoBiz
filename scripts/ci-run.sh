#!/usr/bin/env bash
# اجرای یک دستور در CI؛ اگر شکست خورد، آخرین خط‌های خروجی را به‌صورت annotation در GitHub می‌گذارد
# تا علت خطا بدون باز کردن لاگ کامل دیده شود.
# استفاده:  bash scripts/ci-run.sh "<عنوان>" <دستور> [آرگومان‌ها...]
title="$1"; shift
out="$(mktemp)"
"$@" >"$out" 2>&1
code=$?
cat "$out"
if [ "$code" -ne 0 ]; then
  msg="$(tail -n 60 "$out" | tr -d '\r' | sed 's/%/%25/g' | awk 'BEGIN{ORS="%0A"}1')"
  echo "::error title=${title} (exit ${code})::${msg}"
fi
exit "$code"

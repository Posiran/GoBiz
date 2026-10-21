<?php
// کلیدهای یک فایل env را در فایل دیگر ادغام می‌کند.
//   php scripts/merge-env.php <مقصد> <منبع> [--only-missing]
// پیش‌فرض: کلید موجود جایگزین می‌شود و کلید تازه اضافه می‌شود. با --only-missing مقدارهای موجود دست‌نخورده می‌مانند.
[, $target, $source] = $argv + [null, null, null];
$onlyMissing = in_array('--only-missing', $argv, true);
if (! $target || ! $source || ! is_file($source)) {
    fwrite(STDERR, "usage: php merge-env.php <target> <source> [--only-missing]\n");
    exit(1);
}
$content = is_file($target) ? (string) file_get_contents($target) : '';
// جداکردن خطوط با \R نه: بدون /u روی بایت 0x85 (داخل حروف فارسی مثل «م») هم خط را می‌شکند
foreach (preg_split('/\r\n|\r|\n/', (string) file_get_contents($source)) as $line) {
    if (! preg_match('/^([A-Z][A-Z0-9_]*)=/', $line, $m)) {
        continue;   // خط خالی یا توضیح
    }
    $key = $m[1];
    if (! preg_match('/^[A-Z0-9_]+=\s*["\']/', $line)) {
        $line = rtrim(preg_replace('/\s+#.*$/u', '', $line));   // توضیح انتهای خط (بدون کوتیشن) کنار مقدار نماند
    }
    if (preg_match('/^'.$key.'=/m', $content)) {
        if (! $onlyMissing) {
            $content = preg_replace_callback('/^'.$key.'=.*$/m', fn () => $line, $content);
        }
    } else {
        $content = rtrim($content, "\n")."\n".$line."\n";
    }
}
file_put_contents($target, $content);

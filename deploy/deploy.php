<?php

/**
 * Gobiz — استقرار خودکار روی هاست اشتراکی cPanel (بدون نیاز به SSH، Git یا Composer روی هاست)
 *
 * GitHub Actions پس از قبولی تست‌ها، نسخه آماده (همراه vendor) را در شاخه‌های
 * release/staging و release/production قرار می‌دهد. این اسکریپت با Cron هر چند دقیقه
 * اجرا می‌شود، اگر نسخه تازه‌ای باشد آن را دانلود و در پوشه جدا آماده می‌کند، مهاجرت و
 * کش را اجرا می‌کند و فقط در صورت موفقیت، سایت را به نسخه جدید سوییچ می‌کند.
 *
 *   php deploy.php production            استقرار اگر نسخه تازه باشد
 *   php deploy.php staging --force       استقرار دوباره همین نسخه
 *   php deploy.php production status     وضعیت
 *   php deploy.php production rollback   برگشت به نسخه قبلی (بدون برگرداندن پایگاه داده)
 *
 * ساختار روی هاست (پیش‌فرض ‎~/gobiz):
 *   deploy.php, deploy-config.php
 *   production/{releases/, shared/.env, shared/storage/, current -> releases/..., state.json, deploy.log}
 *   staging/   (همین ساختار)
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const KEEP_RELEASES = 3;
const RETRY_FAILED_AFTER_MINUTES = 30;

date_default_timezone_set('Asia/Tehran');

/** خطای راه‌اندازی (مثل نبودن ‎.env) که شکست نسخه حساب نمی‌شود */
final class SetupRequired extends RuntimeException {}

$base = __DIR__;
$configFile = $base.'/deploy-config.php';
if (! is_file($configFile)) {
    fail("deploy-config.php پیدا نشد. از deploy-config.example.php یک نسخه بسازید.");
}
$config = require $configFile;

// جلوگیری از بزرگ شدن بی‌پایان cron.log
if (is_file($base.'/cron.log') && filesize($base.'/cron.log') > 1_000_000) {
    $tail = substr((string) file_get_contents($base.'/cron.log'), -200_000);
    file_put_contents($base.'/cron.log', $tail);
}

$args = array_slice($argv, 1);
$envName = $args[0] ?? '';
$action = 'deploy';
$force = false;
foreach (array_slice($args, 1) as $a) {
    if ($a === '--force') {
        $force = true;
    } elseif (in_array($a, ['status', 'rollback', 'deploy'], true)) {
        $action = $a;
    }
}

if (! isset($config['environments'][$envName])) {
    fail('محیط نامعتبر. یکی از: '.implode(', ', array_keys($config['environments'] ?? [])));
}

$env = $config['environments'][$envName];
$root = $base.'/'.$envName;
foreach (['releases', 'shared', 'shared/storage'] as $dir) {
    if (! is_dir("$root/$dir") && ! mkdir("$root/$dir", 0755, true)) {
        fail("ساخت پوشه $root/$dir ممکن نشد.");
    }
}

$logFile = "$root/deploy.log";
$stateFile = "$root/state.json";
$state = is_file($stateFile) ? (json_decode((string) file_get_contents($stateFile), true) ?: []) : [];

// فقط یک استقرار هم‌زمان برای هر محیط
$lock = fopen("$root/deploy.lock", 'c');
if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
    exit(0);
}

// بازیابی ‎.env: ساختن فایل RESTORE_ENV در پوشه محیط ← کلید برنامه و اطلاعات پایگاه داده
// از تنظیمات کش‌شده نسخه فعال (که سالم کار می‌کند) به shared/.env برگردانده می‌شود.
if (is_file("$root/RESTORE_ENV")) {
    @unlink("$root/RESTORE_ENV");
    try {
        logLine($root, restoreEnv($root));
    } catch (Throwable $e) {
        logLine($root, '⚠ بازیابی .env ممکن نشد: '.$e->getMessage());
    }
}

// عیب‌یابی: ساختن فایل STATUS در پوشه محیط ← گزارش کامل وضعیت در cron.log
if (is_file("$root/STATUS")) {
    @unlink("$root/STATUS");
    diagnose($config, $env, $envName, $root, $state);
}

// فرمان با فایل (بدون ترمینال): ساختن فایل ROLLBACK یا FORCE در پوشه محیط از File Manager
if ($action === 'deploy' && is_file("$root/ROLLBACK")) {
    @unlink("$root/ROLLBACK");
    $action = 'rollback';
} elseif ($action === 'deploy' && is_file("$root/FORCE")) {
    @unlink("$root/FORCE");
    $force = true;
}

match ($action) {
    'status' => status($state, $root),
    'rollback' => rollback($root, $stateFile, $state),
    default => deploy($config, $env, $envName, $root, $stateFile, $state, $force),
};

// ---------------------------------------------------------------------------

function deploy(array $config, array $env, string $envName, string $root, string $stateFile, array $state, bool $force): void
{
    requireExtensions();

    $sha = remoteSha($config['repository'], $env['branch'], $config['token'] ?? '');
    if (! $force && ($state['sha'] ?? null) === $sha) {
        exit(0); // تغییری نیست
    }
    // همین نسخه قبلاً شکست خورده: هر ۳۰ دقیقه یک بار دوباره امتحان می‌شود (مثلاً پس از اصلاح ‎.env)
    if (! $force && ($state['failed_sha'] ?? null) === $sha
        && strtotime((string) ($state['failed_at'] ?? 'now')) > time() - RETRY_FAILED_AFTER_MINUTES * 60) {
        exit(0);
    }

    logLine($root, "شروع استقرار {$envName} — نسخه ".substr($sha, 0, 7));

    $name = gmdate('Ymd-His').'-'.substr($sha, 0, 7); // UTC تا ترتیب نسخه‌ها به منطقه زمانی وابسته نباشد
    $release = "$root/releases/$name";

    try {
        $zip = download($config['repository'], $sha, $config['token'] ?? '', "$root/releases/$name.zip");
        extractZip($zip, $release);
        @unlink($zip);

        if (! is_file("$release/vendor/autoload.php") || ! is_file("$release/artisan")) {
            throw new RuntimeException('بسته نسخه ناقص است (vendor یا artisan ندارد). آیا GitHub Actions موفق بوده است؟');
        }

        linkShared($root, $release);

        [$code, $out] = runArtisan($release, 'gobiz:post-deploy');
        logLine($root, trim($out));
        if ($code !== 0) {
            throw new RuntimeException("gobiz:post-deploy با کد $code متوقف شد.");
        }

        // سوییچ اتمیک: ساخت لینک موقت و جایگزینی
        $tmp = "$root/current.tmp";
        @unlink($tmp);
        if (! symlink($release, $tmp) || ! rename($tmp, "$root/current")) {
            throw new RuntimeException('سوییچ به نسخه جدید ممکن نشد (symlink).');
        }
        linkPublic($env, $root);

        $state = ['sha' => $sha, 'release' => $name, 'deployed_at' => date('c'), 'previous' => $state['release'] ?? null];
        file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        cleanup($root, $name);

        // به‌روزرسانی خودکار همین اسکریپت از نسخه جدید
        $fresh = "$release/deploy/deploy.php";
        if (is_file($fresh) && md5_file($fresh) !== md5_file(__FILE__)) {
            @copy($fresh, __FILE__);
        }
        logLine($root, "✔ استقرار {$envName} موفق بود — {$name}");
    } catch (SetupRequired $e) {
        logLine($root, '⚠ '.$e->getMessage());
        removeDir($release);
        exit(1);
    } catch (Throwable $e) {
        logLine($root, '✘ استقرار ناموفق: '.$e->getMessage().' — سایت روی نسخه قبلی ماند.');
        $state['failed_sha'] = $sha;
        $state['failed_at'] = date('c');
        $state['error'] = $e->getMessage();
        file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        removeDir($release);
        exit(1);
    }
}

function status(array $state, string $root): void
{
    echo json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    echo 'current -> '.(is_link("$root/current") ? readlink("$root/current") : '(ندارد)').PHP_EOL;
}

/**
 * کلید برنامه، نشانی سایت و اطلاعات پایگاه داده را از config کش‌شده نسخه فعال
 * در shared/.env می‌نویسد (نسخه پشتیبان از .env فعلی گرفته می‌شود). هیچ مقداری در گزارش چاپ نمی‌شود.
 */
function restoreEnv(string $root): string
{
    $current = is_link("$root/current") ? (string) realpath("$root/current") : '';
    $cache = $current !== '' ? "$current/bootstrap/cache/config.php" : '';
    if ($cache === '' || ! is_file($cache)) {
        throw new RuntimeException('نسخه فعال تنظیمات کش‌شده ندارد.');
    }
    $config = require $cache;
    $db = $config['database']['connections'][$config['database']['default'] ?? 'mysql'] ?? [];
    $values = array_filter([
        'APP_KEY' => $config['app']['key'] ?? null,
        'APP_URL' => $config['app']['url'] ?? null,
        'DB_CONNECTION' => $config['database']['default'] ?? null,
        'DB_HOST' => $db['host'] ?? null,
        'DB_PORT' => isset($db['port']) ? (string) $db['port'] : null,
        'DB_DATABASE' => $db['database'] ?? null,
        'DB_USERNAME' => $db['username'] ?? null,
        'DB_PASSWORD' => $db['password'] ?? null,
    ], fn ($v) => is_string($v) && $v !== '');
    if (empty($values['DB_PASSWORD']) || empty($values['APP_KEY'])) {
        throw new RuntimeException('در تنظیمات نسخه فعال رمز پایگاه داده یا کلید برنامه پیدا نشد.');
    }

    $envFile = "$root/shared/.env";
    $content = is_file($envFile) ? (string) file_get_contents($envFile) : '';
    if ($content !== '') {
        copy($envFile, $envFile.'.bak-'.date('Ymd-His'));
    }
    foreach ($values as $key => $value) {
        $line = $key.'='.match (true) {
            (bool) preg_match('~^[A-Za-z0-9_.:/+=@%-]*$~', $value) => $value,
            ! str_contains($value, "'") => "'".$value."'",
            default => '"'.addcslashes($value, '"\\').'"',
        };
        $content = preg_match('/^'.$key.'=.*$/m', $content)
            ? preg_replace_callback('/^'.$key.'=.*$/m', fn () => $line, $content)
            : rtrim($content, "\n")."\n".$line."\n";
    }
    file_put_contents($envFile, $content);
    @chmod($envFile, 0600);

    return '✔ shared/.env از تنظیمات نسخه فعال بازیابی شد ('.implode('، ', array_keys($values)).') — پایگاه داده: '.$values['DB_DATABASE'];
}

/** گزارش عیب‌یابی بدون هیچ رمز یا توکن */
function diagnose(array $config, array $env, string $envName, string $root, array $state): void
{
    $home = (string) (getenv('HOME') ?: dirname(__DIR__));
    $web = (string) ($env['public_link'] ?? '');
    $webPath = $web === '' ? '' : rtrim(str_replace('~', $home, $web), '/');
    try {
        $remote = substr(remoteSha($config['repository'], $env['branch'], $config['token'] ?? ''), 0, 7);
    } catch (Throwable $e) {
        $remote = 'خطا: '.$e->getMessage();
    }
    $lines = [
        "— وضعیت {$envName} —",
        'شاخه: '.($env['branch'] ?? '?').' · آخرین نسخه گیت‌هاب: '.$remote,
        'نسخه نصب‌شده: '.substr((string) ($state['sha'] ?? '-'), 0, 7).' ('.($state['release'] ?? '-').', '.($state['deployed_at'] ?? '-').')',
        'current → '.(is_link("$root/current") ? readlink("$root/current") : '(ندارد)'),
        'پوشه وب: '.($webPath ?: '(تنظیم نشده)').' → '.($webPath === '' ? '-' : (is_link($webPath) ? readlink($webPath) : (is_dir($webPath) ? 'پوشه معمولی است، لینک نیست!' : 'وجود ندارد'))),
        'آخرین خطا: '.(($state['error'] ?? null) ? $state['error'].' ('.($state['failed_at'] ?? '').')' : 'ندارد'),
        'PHP: '.PHP_VERSION.' · deploy.php: '.date('Y-m-d H:i', (int) filemtime(__FILE__)),
    ];
    foreach ($lines as $line) {
        logLine($root, $line);
    }
}

function rollback(string $root, string $stateFile, array $state): void
{
    $releases = releases($root);
    $current = is_link("$root/current") ? basename((string) readlink("$root/current")) : null;
    $idx = array_search($current, $releases, true);
    if ($idx === false || $idx === 0) {
        fail('نسخه قبلی برای برگشت وجود ندارد.');
    }
    $previous = $releases[$idx - 1];
    $tmp = "$root/current.tmp";
    @unlink($tmp);
    symlink("$root/releases/$previous", $tmp);
    rename($tmp, "$root/current");
    runArtisan("$root/releases/$previous", 'config:cache');
    $state['release'] = $previous;
    $state['rolled_back_at'] = date('c');
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    logLine($root, "↩ برگشت به {$previous} (پایگاه داده برگردانده نشد)");
    global $env;
    linkPublic($env, $root);
    echo "برگشت به {$previous}".PHP_EOL;
}

// ---------------------------------------------------------------------------

/**
 * پوشه وب دامنه (مثل ‎~/gobiz.ir یا ‎~/test.gobiz.ir) به public نسخه فعال لینک می‌شود.
 *
 * ایمنی: فقط پوشه خالی یا پوشه‌ای که فقط فایل‌های پیش‌فرض cPanel دارد جایگزین می‌شود.
 * اگر پوشه فایل دیگری داشته باشد (احتمالاً سایت دیگری است) به آن دست زده نمی‌شود.
 * خطای این مرحله نسخه نصب‌شده را خراب نمی‌کند؛ فقط در گزارش هشدار نوشته می‌شود.
 */
function linkPublic(array $env, string $root): void
{
    $path = (string) ($env['public_link'] ?? '');
    if ($path === '') {
        return;
    }
    $home = (string) (getenv('HOME') ?: dirname(__DIR__));
    $path = rtrim(str_replace('~', $home, $path), '/');
    $target = "$root/current/public";

    // هرگز پوشه اصلی هاست (public_html) مگر وقتی صریحاً اجازه داده شده باشد
    if (basename($path) === 'public_html' && empty($env['allow_public_html'])) {
        logLine($root, "⚠ لینک {$path} ساخته نشد: public_html معمولاً متعلق به دامنه اصلی هاست است. در deploy-config.php مسیر پوشه وب همین دامنه (مثل ~/gobiz.ir) را بنویسید.");

        return;
    }

    // پوشه وب نباید خود پوشه استقرار (یا یکی از والدهای آن) باشد
    $self = realpath(__DIR__) ?: __DIR__;
    if ($self === $path || str_starts_with($self.'/', $path.'/')) {
        logLine($root, "⚠ لینک {$path} ساخته نشد: deploy.php داخل همین پوشه است. فایل‌های استقرار را به پوشه‌ای بیرون از پوشه وب (مثل ~/gobiz) منتقل کنید.");

        return;
    }

    try {
        if (is_link($path)) {
            if (readlink($path) !== $target) {
                $tmp = $path.'.tmp-link';
                @unlink($tmp);
                symlink($target, $tmp);
                rename($tmp, $path);
            }

            return;
        }

        if (is_dir($path)) {
            $defaults = ['cgi-bin', '.well-known', 'index.html', 'index.php', 'default.html', '.htaccess', 'favicon.ico', 'error_log', '.user.ini', 'php.ini'];
            $extra = array_diff(array_diff(scandir($path) ?: [], ['.', '..']), $defaults);
            if ($extra) {
                logLine($root, "⚠ لینک {$path} ساخته نشد: این پوشه فایل دارد (".implode('، ', array_slice($extra, 0, 5)).'). اگر مطمئنید متعلق به همین سایت است، محتوایش را جابه‌جا کنید و پوشه را خالی بگذارید.');

                return;
            }
            $backup = $path.'.backup-'.date('Ymd-His');
            if (! rename($path, $backup)) {
                throw new RuntimeException("کنار گذاشتن پوشه {$path} ممکن نشد.");
            }
            logLine($root, "پوشه پیش‌فرض {$path} به {$backup} منتقل شد.");
        }

        if (! symlink($target, $path)) {
            throw new RuntimeException("ساخت لینک {$path} ممکن نشد.");
        }
        logLine($root, "پوشه وب {$path} به نسخه فعال لینک شد.");
    } catch (Throwable $e) {
        logLine($root, '⚠ '.$e->getMessage());
    }
}

function requireExtensions(): void
{
    foreach (['curl', 'zip', 'mbstring', 'pdo_mysql', 'fileinfo', 'openssl'] as $ext) {
        if (! extension_loaded($ext)) {
            fail("افزونه PHP «{$ext}» فعال نیست؛ از Select PHP Version در cPanel فعالش کنید.");
        }
    }
    if (PHP_VERSION_ID < 80300) {
        fail('PHP 8.3 یا بالاتر لازم است؛ نسخه فعلی: '.PHP_VERSION);
    }
}

function http(string $url, string $token, array $headers = [], ?string $saveTo = null): string
{
    $ch = curl_init($url);
    $h = array_merge(['User-Agent: GoBiz-Deploy', 'X-GitHub-Api-Version: 2022-11-28'], $headers);
    if ($token !== '') {
        $h[] = 'Authorization: Bearer '.$token;
    }
    $fp = null;
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => $h,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 600,
        CURLOPT_FAILONERROR => false,
    ]);
    if ($saveTo) {
        $fp = fopen($saveTo, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
    } else {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($fp) {
        fclose($fp);
    }
    if ($body === false || $status >= 400) {
        throw new RuntimeException("درخواست به GitHub ناموفق بود ({$status}) {$error} — {$url}");
    }

    return $saveTo ? $saveTo : (string) $body;
}

function api(): string
{
    global $config;

    return rtrim((string) ($config['api'] ?? 'https://api.github.com'), '/');
}

function remoteSha(string $repo, string $branch, string $token): string
{
    try {
        $sha = trim(http(api()."/repos/{$repo}/commits/".rawurlencode($branch), $token, ['Accept: application/vnd.github.sha']));
    } catch (Throwable $e) {
        fail($e->getMessage());
    }
    if (! preg_match('/^[0-9a-f]{40}$/', $sha)) {
        fail("شاخه {$branch} پیدا نشد یا پاسخ نامعتبر است.");
    }

    return $sha;
}

function download(string $repo, string $sha, string $token, string $target): string
{
    return http(api()."/repos/{$repo}/zipball/{$sha}", $token, ['Accept: application/vnd.github+json'], $target);
}

function extractZip(string $zipFile, string $target): void
{
    $zip = new ZipArchive;
    if ($zip->open($zipFile) !== true) {
        throw new RuntimeException('فایل zip دریافتی خراب است.');
    }
    $tmp = $target.'.extract';
    removeDir($tmp);
    mkdir($tmp, 0755, true);
    $zip->extractTo($tmp);
    $zip->close();

    // zipball یک پوشه بیرونی (Owner-Repo-sha) دارد
    $inner = glob($tmp.'/*', GLOB_ONLYDIR) ?: [];
    if (count($inner) !== 1 || ! rename($inner[0], $target)) {
        throw new RuntimeException('ساختار فایل zip غیرمنتظره است.');
    }
    removeDir($tmp);
}

/** storage و ‎.env بین نسخه‌ها مشترک است */
function linkShared(string $root, string $release): void
{
    $shared = "$root/shared";

    // بار اول: ساختار storage از خود نسخه کپی می‌شود
    foreach (['app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $dir) {
        if (! is_dir("$shared/storage/$dir")) {
            mkdir("$shared/storage/$dir", 0755, true);
        }
    }

    if (! is_file("$shared/.env")) {
        copy("$release/.env.example", "$shared/.env");
        // اگر نسخه‌ای از قبل کار می‌کند، اطلاعات اصلی از تنظیمات کش‌شده آن برگردانده می‌شود
        try {
            logLine($root, restoreEnv($root));
        } catch (Throwable) {
            throw new SetupRequired("فایل {$shared}/.env ساخته شد؛ اطلاعات پایگاه داده و سرویس‌ها را در آن وارد کنید و دوباره اجرا کنید.");
        }
    }
    ensureAppKey("$shared/.env");

    removeDir("$release/storage");
    symlink("$shared/storage", "$release/storage");
    @unlink("$release/.env");
    symlink("$shared/.env", "$release/.env");
    @unlink("$release/public/storage");
    symlink("$shared/storage/app/public", "$release/public/storage");

    // کش ساخته‌شده در مرحله build (اگر باشد) پاک می‌شود تا با ‎.env همین محیط ساخته شود
    foreach (glob("$release/bootstrap/cache/*.php") ?: [] as $f) {
        @unlink($f);
    }
}

function ensureAppKey(string $envFile): void
{
    $content = (string) file_get_contents($envFile);
    if (preg_match('/^APP_KEY=\s*$/m', $content) || ! preg_match('/^APP_KEY=/m', $content)) {
        $key = 'base64:'.base64_encode(random_bytes(32));
        $content = preg_match('/^APP_KEY=/m', $content)
            ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $content)
            : "APP_KEY={$key}\n".$content;
        file_put_contents($envFile, $content);
    }
}

/**
 * اجرای دستور artisan نسخه جدید. اگر exec روی هاست بسته باشد (رایج در هاست اشتراکی)،
 * لاراول همان نسخه داخل همین فرایند بارگذاری و دستور اجرا می‌شود.
 *
 * @return array{0:int,1:string}
 */
function runArtisan(string $release, string $command): array
{
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (function_exists('exec') && ! in_array('exec', $disabled, true)) {
        $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg("$release/artisan").' '.$command.' --no-interaction 2>&1';
        exec($cmd, $lines, $code);

        return [$code, implode("\n", $lines)];
    }

    $cwd = getcwd();
    chdir($release);
    require_once "$release/vendor/autoload.php";
    $app = require "$release/bootstrap/app.php";
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $output = new Symfony\Component\Console\Output\BufferedOutput;
    $code = $kernel->call($command, ['--no-interaction' => true], $output);
    chdir((string) $cwd);

    return [$code, $output->fetch()];
}

/** @return list<string> */
function releases(string $root): array
{
    $list = array_values(array_filter(array_map('basename', glob("$root/releases/*", GLOB_ONLYDIR) ?: []), fn ($d) => ! str_ends_with($d, '.extract')));
    sort($list);

    return $list;
}

function cleanup(string $root, string $keep): void
{
    $all = releases($root);
    foreach (array_slice($all, 0, max(0, count($all) - KEEP_RELEASES)) as $old) {
        if ($old !== $keep) {
            removeDir("$root/releases/$old");
        }
    }
}

function removeDir(string $dir): void
{
    if (is_link($dir) || is_file($dir)) {
        @unlink($dir);

        return;
    }
    if (! is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() && ! $f->isLink() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($dir);
}

function logLine(string $root, string $text): void
{
    $line = '['.date('Y-m-d H:i:s').'] '.$text.PHP_EOL;
    file_put_contents("$root/deploy.log", $line, FILE_APPEND);
    echoLine($line);
}

/**
 * همه خروجی‌ها در ‎cron.log کنار همین اسکریپت هم نوشته می‌شوند؛ چون برخی هاست‌ها
 * (مثل پارس‌پک) در فرمان Cron اجازه ‎>>‎ و ‎2>&1 نمی‌دهند.
 */
function echoLine(string $line): void
{
    echo $line;
    @file_put_contents(__DIR__.'/cron.log', $line, FILE_APPEND);
}

function fail(string $message): never
{
    $line = '['.date('Y-m-d H:i:s').'] ✘ '.$message.PHP_EOL;
    fwrite(STDERR, $line);
    @file_put_contents(__DIR__.'/cron.log', $line, FILE_APPEND);
    exit(1);
}

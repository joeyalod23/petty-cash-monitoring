<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'stderr');

function vlog(string $msg): void
{
    error_log('[vercel] '.$msg);
    @file_put_contents('C:/Users/Admin/AppData/Local/Temp/opencode/debug.log', $msg.PHP_EOL, FILE_APPEND);
}

function vercel_env_load(string $file): void
{
    if (! is_file($file)) {
        vlog('vercel.env NOT found at '.$file);

        return;
    }

    vlog('vercel.env found at '.$file);

    foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\"'");

        if (getenv($key) !== false) {
            continue;
        }

        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

vercel_env_load(__DIR__.'/../vercel.env');

if (getenv('APP_URL') === false && isset($_SERVER['HTTP_HOST'])) {
    $url = 'https://'.$_SERVER['HTTP_HOST'];
    putenv('APP_URL='.$url);
    $_ENV['APP_URL'] = $url;
    $_SERVER['APP_URL'] = $url;
}

if (getenv('APP_KEY') === false && is_file(__DIR__.'/../bootstrap/vercel-key.php')) {
    require __DIR__.'/../bootstrap/vercel-key.php';

    if (isset($GLOBALS['vercel_app_key'])) {
        putenv('APP_KEY='.$GLOBALS['vercel_app_key']);
        $_ENV['APP_KEY'] = $GLOBALS['vercel_app_key'];
        $_SERVER['APP_KEY'] = $GLOBALS['vercel_app_key'];
    }
}

vlog('APP_KEY='.(getenv('APP_KEY') !== false ? 'SET' : 'MISSING'));
vlog('APP_DEBUG='.(getenv('APP_DEBUG') !== false ? getenv('APP_DEBUG') : 'MISSING'));
vlog('DB_CONNECTION='.(getenv('DB_CONNECTION') !== false ? getenv('DB_CONNECTION') : 'MISSING'));
vlog('SESSION_DRIVER='.(getenv('SESSION_DRIVER') !== false ? getenv('SESSION_DRIVER') : 'MISSING'));

if (getenv('VERCEL_PROBE') !== false) {
    try {
        require_once __DIR__.'/../vendor/autoload.php';
        $probeApp = new Illuminate\Foundation\Application(dirname(__DIR__));
        (new Illuminate\Foundation\Bootstrap\LoadConfiguration())->bootstrap($probeApp);
        vlog('probe: default channel = '.var_export($probeApp['config']['logging.default'], true));
        vlog('probe: storage_path = '.$probeApp->storagePath());

        try {
            $probeApp['log']->channel('stderr')->info('stderr probe');
            vlog('probe: stderr channel OK');
        } catch (Throwable $e2) {
            vlog('probe: stderr channel FAILED: '.$e2::class.': '.$e2->getMessage());
            vlog('probe: '.str_replace("\n", ' | ', $e2->getTraceAsString()));
        }
    } catch (Throwable $e) {
        vlog('probe: boot FAILED: '.$e::class.': '.$e->getMessage());
        vlog('probe: '.str_replace("\n", ' | ', $e->getTraceAsString()));
    }
}

try {
    require __DIR__.'/../public/index.php';
} catch (Throwable $e) {
    vlog('FATAL '.$e::class.': '.$e->getMessage());
    vlog(str_replace("\n", ' | ', $e->getTraceAsString()));
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Error';
}

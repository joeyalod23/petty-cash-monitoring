<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'stderr');

function vercel_env_load(string $file): void
{
    if (! is_file($file)) {
        fwrite(STDERR, '[vercel] vercel.env NOT found at '.$file."\n");

        return;
    }

    fwrite(STDERR, '[vercel] vercel.env found at '.$file."\n");

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

fwrite(STDERR, '[vercel] APP_KEY='.(getenv('APP_KEY') !== false ? 'SET' : 'MISSING')."\n");
fwrite(STDERR, '[vercel] APP_DEBUG='.(getenv('APP_DEBUG') !== false ? getenv('APP_DEBUG') : 'MISSING')."\n");
fwrite(STDERR, '[vercel] DB_CONNECTION='.(getenv('DB_CONNECTION') !== false ? getenv('DB_CONNECTION') : 'MISSING')."\n");
fwrite(STDERR, '[vercel] SESSION_DRIVER='.(getenv('SESSION_DRIVER') !== false ? getenv('SESSION_DRIVER') : 'MISSING')."\n");

try {
    require __DIR__.'/../public/index.php';
} catch (Throwable $e) {
    fwrite(STDERR, '[vercel] FATAL '.$e::class.': '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Error';
}

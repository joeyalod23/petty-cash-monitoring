<?php

function vercel_env_load(string $file): void
{
    if (! is_file($file)) {
        return;
    }

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

require __DIR__.'/../public/index.php';

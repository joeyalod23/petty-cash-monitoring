<?php

$target = dirname(__DIR__).'/bootstrap/vercel-key.php';

if (getenv('APP_KEY') !== false || is_file($target)) {
    exit(0);
}

$key = base64_encode(random_bytes(32));

file_put_contents(
    $target,
    "<?php\n\n\$GLOBALS['vercel_app_key'] = '".$key."';\n"
);

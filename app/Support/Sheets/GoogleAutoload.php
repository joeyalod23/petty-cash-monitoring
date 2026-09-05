<?php

namespace App\Support\Sheets;

/**
 * Self-contained PSR-4 autoloader for the Google API client packages.
 *
 * Registered manually so the Google Sheets integration does not depend on a
 * freshly-regenerated Composer classmap (Composer's autoload dump can be slow
 * or unavailable on some Windows setups). Longest prefixes are matched first.
 */
class GoogleAutoload
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        $vendor = dirname(__DIR__, 3) . '/vendor/';

        $prefixes = [
            'Google\\Service\\' => $vendor . 'google/apiclient-services/src',
            'Google\\Auth\\' => $vendor . 'google/auth/src',
            'Google\\' => $vendor . 'google/apiclient/src',
            'Firebase\\JWT\\' => $vendor . 'firebase/php-jwt/src',
        ];

        spl_autoload_register(static function (string $class) use ($prefixes): void {
            foreach ($prefixes as $prefix => $baseDir) {
                if (str_starts_with($class, $prefix)) {
                    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
                    $file = $baseDir . DIRECTORY_SEPARATOR . $relative . '.php';

                    if (is_file($file)) {
                        require $file;

                        return;
                    }
                }
            }
        });

        $aliases = $vendor . 'google/apiclient/src/aliases.php';
        if (is_file($aliases)) {
            require_once $aliases;
        }

        self::$registered = true;
    }
}
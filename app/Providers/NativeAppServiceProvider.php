<?php

namespace App\Providers;

use Native\Desktop\Facades\Window;
use Native\Desktop\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        Window::open()
            ->title('Petty Cash Monitor')
            ->width(1280)
            ->height(800)
            ->resizable(true);
    }

    public function phpIni(): array
    {
        return [
            'memory_limit' => '256M',
            'display_errors' => '0',
            'error_reporting' => 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
        ];
    }
}

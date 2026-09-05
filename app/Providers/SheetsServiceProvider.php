<?php

namespace App\Providers;

use App\Support\Sheets\SheetsUserProvider;
use App\Support\Sheets\SheetDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class SheetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SheetDatabase::class, fn () => new SheetDatabase);
    }

    public function boot(): void
    {
        Auth::provider('sheets', fn ($app, array $config) => new SheetsUserProvider($app['hash']));
    }
}
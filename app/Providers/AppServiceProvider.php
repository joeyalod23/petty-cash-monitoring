<?php

namespace App\Providers;

use App\Support\Sheets\SheetDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        if (! $this->app->runningInConsole()) {
            DB::reconnect();
            DB::purge();
        }

        $this->initializeDatabaseIfNeeded();
    }

    private function initializeDatabaseIfNeeded(): void
    {
        if (config('gsheet.driver') === 'google') {
            return;
        }

        try {
            if (!Schema::hasTable('users')) {
                Artisan::call('migrate', ['--force' => true]);
            }

            if (config('gsheet.driver') !== 'google' && resolve(SheetDatabase::class)->count('users') === 0) {
                Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
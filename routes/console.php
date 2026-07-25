<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

function freshConnection(): void
{
    DB::purge();
    DB::reconnect();
    try { DB::connection()->getPdo()->exec('ROLLBACK'); } catch (\Exception $e) {}
}

function dropIfExistsSafe(string $table): bool
{
    try {
        return Schema::dropIfExists($table);
    } catch (\Exception $e) {
        freshConnection();
        try { return Schema::dropIfExists($table); } catch (\Exception $e2) { return false; }
    }
}

Artisan::command('db:reset-and-seed', function () {
    $this->info('Establishing fresh DB connection...');
    freshConnection();

    $tables = [
        'replenishment_items', 'replenishment_reports', 'expenses',
        'replenishment_requests', 'petty_cash_funds',
        'sessions', 'cache_locks', 'cache',
        'failed_jobs', 'job_batches', 'jobs',
        'password_reset_tokens', 'users', 'migrations',
    ];

    $this->info('Dropping all tables...');
    foreach ($tables as $table) {
        dropIfExistsSafe($table);
    }

    $this->info('Waiting for connection pool to refresh...');
    freshConnection();
    sleep(5);

    $this->info('Reconnecting with clean connection...');
    freshConnection();

    $this->info('Running migrations...');
    Artisan::call('migrate', ['--force' => true]);
    $this->info(Artisan::output());

    $this->info('Seeding database...');
    Artisan::call('db:seed', ['--force' => true]);
    $this->info(Artisan::output());

    $this->info('Done!');
})->purpose('Drop all tables manually, wait, then migrate and seed');

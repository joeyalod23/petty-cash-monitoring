<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:reset-and-seed', function () {
    $this->info('Purging DB connections...');
    DB::purge();
    DB::reconnect();

    $this->info('Dropping all tables...');
    Schema::dropIfExists('replenishment_items');
    Schema::dropIfExists('replenishment_reports');
    Schema::dropIfExists('expenses');
    Schema::dropIfExists('replenishment_requests');
    Schema::dropIfExists('petty_cash_funds');
    Schema::dropIfExists('sessions');
    Schema::dropIfExists('cache_locks');
    Schema::dropIfExists('cache');
    Schema::dropIfExists('failed_jobs');
    Schema::dropIfExists('job_batches');
    Schema::dropIfExists('jobs');
    Schema::dropIfExists('password_reset_tokens');
    Schema::dropIfExists('users');
    Schema::dropIfExists('migrations');

    $this->info('Waiting for connection pool to refresh...');
    DB::purge();
    sleep(3);
    DB::reconnect();

    $this->info('Running migrations...');
    Artisan::call('migrate', ['--force' => true]);
    $this->info(Artisan::output());

    $this->info('Seeding database...');
    Artisan::call('db:seed', ['--force' => true]);
    $this->info(Artisan::output());

    $this->info('Done!');
})->purpose('Drop all tables manually, wait, then migrate and seed');

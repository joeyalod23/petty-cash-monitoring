<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:reset-and-seed', function () {
    DB::purge();
    DB::reconnect();

    $this->info('Purged and reconnected to database.');

    Artisan::call('migrate:fresh', ['--force' => true]);
    $this->info(Artisan::output());

    $this->info('Seeding database...');
    Artisan::call('db:seed', ['--force' => true]);
    $this->info(Artisan::output());

    $this->info('Done! All tables recreated and seeded.');
})->purpose('Purge connections, reset database, run all migrations, and seed');

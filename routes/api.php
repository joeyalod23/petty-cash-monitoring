<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Route::get('/_nuke/{token}', function ($token) {
    if ($token !== 'pettycash2026nuke') {
        abort(404);
    }

    DB::purge();

    try {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $migrateOutput = Artisan::output();
    } catch (\Throwable $e) {
        $migrateOutput = "MIGRATION ERROR: " . $e->getMessage();
    }

    try {
        Artisan::call('db:seed', ['--force' => true]);
        $seedOutput = Artisan::output();
    } catch (\Throwable $e) {
        $seedOutput = "SEED ERROR: " . $e->getMessage();
    }

    return "NUKE COMPLETE\n\nMigrations:\n{$migrateOutput}\n\nSeeding:\n{$seedOutput}";
});

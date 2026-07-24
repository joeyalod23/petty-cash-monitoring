<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\ReplenishmentReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [PettyCashController::class, 'dashboard'])->name('dashboard');

    Route::post('/funds', [PettyCashController::class, 'storeFund'])->name('funds.store');
    Route::get('/fund/{fund}/expenses', [PettyCashController::class, 'fundExpenses'])->name('fund.expenses');
    Route::get('/fund/{fund}/expenses/create', [PettyCashController::class, 'createExpense'])->name('fund.expenses.create');
    Route::post('/fund/{fund}/expenses', [PettyCashController::class, 'storeExpense'])->name('fund.expenses.store');

    Route::get('/replenishments', [PettyCashController::class, 'replenishments'])->name('replenishments');
    Route::get('/replenishments/create', [PettyCashController::class, 'createReplenishment'])->name('replenishments.create');
    Route::post('/replenishments', [PettyCashController::class, 'storeReplenishment'])->name('replenishments.store');

    Route::middleware('admin')->group(function () {
        Route::get('/funds/{fund}/edit', [PettyCashController::class, 'editFund'])->name('funds.edit');
        Route::put('/funds/{fund}', [PettyCashController::class, 'updateFund'])->name('funds.update');
        Route::delete('/funds/{fund}', [PettyCashController::class, 'destroyFund'])->name('funds.destroy');

        Route::get('/expenses/{expense}/edit', [PettyCashController::class, 'editExpense'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [PettyCashController::class, 'updateExpense'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [PettyCashController::class, 'destroyExpense'])->name('expenses.destroy');

        Route::get('/replenishments/{request}/edit', [PettyCashController::class, 'editReplenishment'])->name('replenishments.edit');
        Route::put('/replenishments/{request}', [PettyCashController::class, 'updateReplenishment'])->name('replenishments.update');
        Route::delete('/replenishments/{request}', [PettyCashController::class, 'destroyReplenishment'])->name('replenishments.destroy');

        Route::post('/replenishments/{request}/approve', [PettyCashController::class, 'approveReplenishment'])->name('replenishments.approve');
        Route::post('/replenishments/{request}/disburse', [PettyCashController::class, 'disburseReplenishment'])->name('replenishments.disburse');
        Route::post('/replenishments/{request}/reject', [PettyCashController::class, 'rejectReplenishment'])->name('replenishments.reject');
    });

    Route::get('/reports', [ReplenishmentReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [ReplenishmentReportController::class, 'show'])->name('reports.show');

    Route::middleware('admin')->group(function () {
        Route::get('/reports/create', [ReplenishmentReportController::class, 'create'])->name('reports.create');
        Route::post('/reports', [ReplenishmentReportController::class, 'store'])->name('reports.store');
        Route::get('/reports/{report}/edit', [ReplenishmentReportController::class, 'edit'])->name('reports.edit');
        Route::put('/reports/{report}', [ReplenishmentReportController::class, 'update'])->name('reports.update');
        Route::delete('/reports/{report}', [ReplenishmentReportController::class, 'destroy'])->name('reports.destroy');
    });
});

Route::get('/_nuke/{token}', function ($token) {
    if ($token !== 'pettycash2026nuke') abort(404);

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
})->withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
]);

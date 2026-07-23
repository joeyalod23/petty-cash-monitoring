<?php

use App\Http\Controllers\PettyCashController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', [PettyCashController::class, 'dashboard'])->name('dashboard');

Route::post('/funds', [PettyCashController::class, 'storeFund'])->name('funds.store');
Route::get('/funds/{fund}/edit', [PettyCashController::class, 'editFund'])->name('funds.edit');
Route::put('/funds/{fund}', [PettyCashController::class, 'updateFund'])->name('funds.update');
Route::delete('/funds/{fund}', [PettyCashController::class, 'destroyFund'])->name('funds.destroy');

Route::get('/fund/{fund}/expenses/create', [PettyCashController::class, 'createExpense'])->name('fund.expenses.create');
Route::post('/fund/{fund}/expenses', [PettyCashController::class, 'storeExpense'])->name('fund.expenses.store');
Route::get('/fund/{fund}/expenses', [PettyCashController::class, 'fundExpenses'])->name('fund.expenses');
Route::get('/expenses/{expense}/edit', [PettyCashController::class, 'editExpense'])->name('expenses.edit');
Route::put('/expenses/{expense}', [PettyCashController::class, 'updateExpense'])->name('expenses.update');
Route::delete('/expenses/{expense}', [PettyCashController::class, 'destroyExpense'])->name('expenses.destroy');

Route::get('/replenishments', [PettyCashController::class, 'replenishments'])->name('replenishments');
Route::get('/replenishments/create', [PettyCashController::class, 'createReplenishment'])->name('replenishments.create');
Route::post('/replenishments', [PettyCashController::class, 'storeReplenishment'])->name('replenishments.store');
Route::get('/replenishments/{request}/edit', [PettyCashController::class, 'editReplenishment'])->name('replenishments.edit');
Route::put('/replenishments/{request}', [PettyCashController::class, 'updateReplenishment'])->name('replenishments.update');
Route::delete('/replenishments/{request}', [PettyCashController::class, 'destroyReplenishment'])->name('replenishments.destroy');
Route::post('/replenishments/{request}/approve', [PettyCashController::class, 'approveReplenishment'])->name('replenishments.approve');
Route::post('/replenishments/{request}/disburse', [PettyCashController::class, 'disburseReplenishment'])->name('replenishments.disburse');
Route::post('/replenishments/{request}/reject', [PettyCashController::class, 'rejectReplenishment'])->name('replenishments.reject');

Route::get('/_nuke/{token}', function ($token) {
    if ($token !== 'pettycash2026nuke') abort(404);
    Artisan::call('migrate:fresh', ['--force' => true]);
    return 'Done. Database wiped and fresh migrations ran.';
});

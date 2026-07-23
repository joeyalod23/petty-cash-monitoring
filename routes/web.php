<?php

use App\Http\Controllers\PettyCashController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PettyCashController::class, 'dashboard'])->name('dashboard');

Route::post('/funds', [PettyCashController::class, 'storeFund'])->name('funds.store');

Route::get('/fund/{fund}/expenses/create', [PettyCashController::class, 'createExpense'])->name('fund.expenses.create');
Route::post('/fund/{fund}/expenses', [PettyCashController::class, 'storeExpense'])->name('fund.expenses.store');
Route::get('/fund/{fund}/expenses', [PettyCashController::class, 'fundExpenses'])->name('fund.expenses');

Route::get('/replenishments', [PettyCashController::class, 'replenishments'])->name('replenishments');
Route::post('/replenishments/{request}/approve', [PettyCashController::class, 'approveReplenishment'])->name('replenishments.approve');
Route::post('/replenishments/{request}/disburse', [PettyCashController::class, 'disburseReplenishment'])->name('replenishments.disburse');
Route::post('/replenishments/{request}/reject', [PettyCashController::class, 'rejectReplenishment'])->name('replenishments.reject');

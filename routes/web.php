<?php

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankTransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryLimitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('accounts', [BankAccountController::class, 'index'])->name('accounts.index');
    Route::get('accounts/{account}/edit', [BankAccountController::class, 'edit'])
        ->middleware('owns.account')
        ->name('accounts.edit');
    Route::put('accounts/{account}', [BankAccountController::class, 'update'])
        ->middleware('owns.account')
        ->name('accounts.update');
    Route::post('accounts/import-ofx', [BankAccountController::class, 'importOfx'])->name('accounts.import-ofx');

    Route::get('transactions/{transaction}/edit', [BankTransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('transactions/{transaction}', [BankTransactionController::class, 'update'])->name('transactions.update');
    Route::put('transactions/{transaction}/category', [BankTransactionController::class, 'updateCategory'])->name('transactions.category.update');

    Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
    Route::resource('category-limits', CategoryLimitController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('users', UserController::class);
    Route::get('users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
    Route::resource('roles', RoleController::class);
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::resource('permissions', PermissionController::class);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

<?php

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankTransactionTagController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('accounts', [BankAccountController::class, 'index'])->name('accounts.index');
    Route::get('transactions/{transaction}/tags', [BankTransactionTagController::class, 'edit'])->name('transactions.tags.edit');
    Route::put('transactions/{transaction}/tags', [BankTransactionTagController::class, 'update'])->name('transactions.tags.update');

    Route::resource('users', UserController::class);
    Route::get('users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
    Route::resource('roles', RoleController::class);
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::resource('permissions', PermissionController::class);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

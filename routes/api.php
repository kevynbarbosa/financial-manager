<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/users/{user}/permissions', [UserController::class, 'apiPermissions']);
});

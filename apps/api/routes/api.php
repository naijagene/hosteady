<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\OrganizationsController;
use App\Http\Controllers\Api\V1\Tenant\TenantContextController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', LoginController::class)
        ->middleware('throttle:6,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', LogoutController::class);
        Route::get('auth/me', MeController::class);
        Route::get('auth/organizations', OrganizationsController::class);

        Route::middleware('tenant.context')->group(function () {
            Route::get('tenant/context', TenantContextController::class);
        });
    });
});

<?php

use App\Http\Controllers\Api\V1\ApplicationCatalogController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\OrganizationsController;
use App\Http\Controllers\Api\V1\Tenant\ApplicationSettingDefinitionController;
use App\Http\Controllers\Api\V1\Tenant\AuditEventController;
use App\Http\Controllers\Api\V1\Tenant\ApplicationCatalogController as TenantApplicationCatalogController;
use App\Http\Controllers\Api\V1\Tenant\OrganizationApplicationController;
use App\Http\Controllers\Api\V1\Tenant\TenantContextController;
use App\Http\Controllers\Api\V1\Tenant\WorkspaceApplicationController;
use App\Http\Controllers\Api\V1\Tenant\WorkspaceApplicationSettingsController;
use App\Http\Controllers\Api\V1\Tenant\WorkspaceRuntimeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', LoginController::class)
        ->middleware('throttle:6,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', LogoutController::class);
        Route::get('auth/me', MeController::class);
        Route::get('auth/organizations', OrganizationsController::class);
        Route::get('applications/catalog', ApplicationCatalogController::class);

        Route::middleware('tenant.context')->group(function () {
            Route::get('tenant/context', TenantContextController::class);
            Route::get('tenant/applications/catalog', TenantApplicationCatalogController::class);
            Route::get('tenant/applications', [OrganizationApplicationController::class, 'index']);
            Route::post('tenant/applications', [OrganizationApplicationController::class, 'store']);
            Route::patch('tenant/applications/{installationPublicId}/enable', [OrganizationApplicationController::class, 'enable']);
            Route::patch('tenant/applications/{installationPublicId}/disable', [OrganizationApplicationController::class, 'disable']);
            Route::delete('tenant/applications/{installationPublicId}', [OrganizationApplicationController::class, 'destroy']);
            Route::get('tenant/applications/{applicationPublicId}/settings/definitions', ApplicationSettingDefinitionController::class);
            Route::get('tenant/workspace/applications', [WorkspaceApplicationController::class, 'index']);
            Route::get('tenant/workspace/applications/available', [WorkspaceApplicationController::class, 'available']);
            Route::post('tenant/workspace/applications', [WorkspaceApplicationController::class, 'store']);
            Route::patch('tenant/workspace/applications/{workspaceApplicationPublicId}/enable', [WorkspaceApplicationController::class, 'enable']);
            Route::patch('tenant/workspace/applications/{workspaceApplicationPublicId}/disable', [WorkspaceApplicationController::class, 'disable']);
            Route::patch('tenant/workspace/applications/{workspaceApplicationPublicId}/archive', [WorkspaceApplicationController::class, 'archive']);
            Route::delete('tenant/workspace/applications/{workspaceApplicationPublicId}', [WorkspaceApplicationController::class, 'destroy']);
            Route::get('tenant/workspace/settings', [WorkspaceApplicationSettingsController::class, 'index']);
            Route::put('tenant/workspace/settings', [WorkspaceApplicationSettingsController::class, 'update']);
            Route::post('tenant/workspace/settings/reset', [WorkspaceApplicationSettingsController::class, 'reset']);
            Route::get('tenant/workspace/settings/history', [WorkspaceApplicationSettingsController::class, 'history']);
            Route::get('tenant/workspace/runtime', WorkspaceRuntimeController::class);
            Route::get('tenant/audit/events', [AuditEventController::class, 'index'])
                ->name('tenant.audit.events.index');
            Route::get('tenant/audit/summary', [AuditEventController::class, 'summary'])
                ->name('tenant.audit.summary');
            Route::get('tenant/audit/events/{eventPublicId}', [AuditEventController::class, 'show'])
                ->name('tenant.audit.events.show');
        });
    });
});

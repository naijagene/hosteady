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
use App\Http\Controllers\Api\V1\Tenant\PlatformFileController;
use App\Http\Controllers\Api\V1\Tenant\PlatformJobController;
use App\Http\Controllers\Api\V1\Tenant\ScheduledTaskController;
use App\Http\Controllers\Api\V1\Tenant\PlatformNotificationController;
use App\Http\Controllers\Api\V1\Tenant\ReferenceDataController;
use App\Http\Controllers\Api\V1\Tenant\SearchController;
use App\Http\Controllers\Api\V1\Tenant\TenantContextController;
use App\Http\Controllers\Api\V1\Tenant\WorkflowCategoryController;
use App\Http\Controllers\Api\V1\Tenant\WorkflowDefinitionController;
use App\Http\Controllers\Api\V1\Tenant\WorkflowInstanceController;
use App\Http\Controllers\Api\V1\Tenant\WorkspaceApplicationController;
use App\Http\Controllers\Api\V1\Tenant\WorkspaceApplicationSettingsController;
use App\Http\Controllers\Api\V1\Tenant\WorkspaceRuntimeController;
use App\Http\Controllers\Api\V1\Tenant\WorkspaceRuntimeHealthController;
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
            Route::get('tenant/workspace/runtime/health', WorkspaceRuntimeHealthController::class);
            Route::get('tenant/audit/events', [AuditEventController::class, 'index'])
                ->name('tenant.audit.events.index');
            Route::get('tenant/audit/summary', [AuditEventController::class, 'summary'])
                ->name('tenant.audit.summary');
            Route::get('tenant/audit/events/{eventPublicId}', [AuditEventController::class, 'show'])
                ->name('tenant.audit.events.show');
            Route::get('tenant/notifications', [PlatformNotificationController::class, 'index']);
            Route::patch('tenant/notifications/{notificationPublicId}/read', [PlatformNotificationController::class, 'markRead']);
            Route::get('tenant/reference/{catalogKey}', [ReferenceDataController::class, 'catalog']);
            Route::get('tenant/reference/{catalogKey}/{code}', [ReferenceDataController::class, 'item']);
            Route::get('tenant/files/entity', [PlatformFileController::class, 'entity']);
            Route::get('tenant/files/download/{filePublicId}', [PlatformFileController::class, 'download']);
            Route::get('tenant/files', [PlatformFileController::class, 'index']);
            Route::post('tenant/files', [PlatformFileController::class, 'store']);
            Route::get('tenant/files/{filePublicId}', [PlatformFileController::class, 'show']);
            Route::patch('tenant/files/{filePublicId}', [PlatformFileController::class, 'update']);
            Route::delete('tenant/files/{filePublicId}', [PlatformFileController::class, 'destroy']);
            Route::get('tenant/jobs', [PlatformJobController::class, 'index']);
            Route::post('tenant/jobs', [PlatformJobController::class, 'store']);
            Route::get('tenant/jobs/{jobPublicId}', [PlatformJobController::class, 'show']);
            Route::patch('tenant/jobs/{jobPublicId}/cancel', [PlatformJobController::class, 'cancel']);
            Route::get('tenant/scheduled-tasks', [ScheduledTaskController::class, 'index']);
            Route::post('tenant/scheduled-tasks', [ScheduledTaskController::class, 'store']);
            Route::get('tenant/scheduled-tasks/{taskPublicId}', [ScheduledTaskController::class, 'show']);
            Route::patch('tenant/scheduled-tasks/{taskPublicId}/pause', [ScheduledTaskController::class, 'pause']);
            Route::patch('tenant/scheduled-tasks/{taskPublicId}/resume', [ScheduledTaskController::class, 'resume']);
            Route::delete('tenant/scheduled-tasks/{taskPublicId}', [ScheduledTaskController::class, 'destroy']);
            Route::get('tenant/scheduled-tasks/{taskPublicId}/runs', [ScheduledTaskController::class, 'runs']);
            Route::get('tenant/search/suggestions', [SearchController::class, 'suggestions']);
            Route::get('tenant/search/recent', [SearchController::class, 'recent']);
            Route::post('tenant/search/saved', [SearchController::class, 'storeSaved']);
            Route::get('tenant/search/saved', [SearchController::class, 'listSaved']);
            Route::delete('tenant/search/saved/{savedSearchPublicId}', [SearchController::class, 'destroySaved']);
            Route::get('tenant/search', [SearchController::class, 'index']);
            Route::get('tenant/workflows/categories', [WorkflowCategoryController::class, 'index']);
            Route::post('tenant/workflows/categories', [WorkflowCategoryController::class, 'store']);
            Route::get('tenant/workflows/definitions/{publicId}/versions', [WorkflowDefinitionController::class, 'versions']);
            Route::post('tenant/workflows/definitions/{publicId}/publish', [WorkflowDefinitionController::class, 'publish']);
            Route::post('tenant/workflows/definitions/{publicId}/execute', [WorkflowDefinitionController::class, 'execute']);
            Route::post('tenant/workflows/definitions/{publicId}/archive', [WorkflowDefinitionController::class, 'archive']);
            Route::get('tenant/workflows/definitions/{publicId}', [WorkflowDefinitionController::class, 'show']);
            Route::patch('tenant/workflows/definitions/{publicId}', [WorkflowDefinitionController::class, 'update']);
            Route::get('tenant/workflows/definitions', [WorkflowDefinitionController::class, 'index']);
            Route::post('tenant/workflows/definitions', [WorkflowDefinitionController::class, 'store']);
            Route::get('tenant/workflow-instances/{publicId}/history', [WorkflowInstanceController::class, 'history']);
            Route::post('tenant/workflow-instances/{publicId}/cancel', [WorkflowInstanceController::class, 'cancel']);
            Route::post('tenant/workflow-instances/{publicId}/resume', [WorkflowInstanceController::class, 'resume']);
            Route::get('tenant/workflow-instances/{publicId}', [WorkflowInstanceController::class, 'show']);
            Route::get('tenant/workflow-instances', [WorkflowInstanceController::class, 'index']);
        });
    });
});

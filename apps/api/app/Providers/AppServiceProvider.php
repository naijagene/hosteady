<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\ApplicationSettingDefinition;
use App\Models\WorkflowAutomationRule;
use App\Models\WorkflowCanvasSnapshot;
use App\Models\BusinessModule;
use App\Models\EnterpriseDocument;
use App\Models\EnterpriseNotification;
use App\Models\EnterpriseEntityRecord;
use App\Models\EntityDefinition;
use App\Models\FormDefinition;
use App\Models\TableDefinition;
use App\Models\DashboardDefinition;
use App\Models\ReportDefinition;
use App\Models\WorkflowPackage;
use App\Models\WorkflowHumanTask;
use App\Observers\ApplicationRuntimeCacheObserver;
use App\Observers\ApplicationSettingDefinitionRuntimeCacheObserver;
use App\Policies\HumanTaskPolicy;
use App\Policies\WorkflowAutomationPolicy;
use App\Policies\WorkflowDesignerPolicy;
use App\Policies\BusinessModulePolicy;
use App\Policies\EnterpriseDocumentPolicy;
use App\Policies\EnterpriseNotificationPolicy;
use App\Policies\EnterpriseBusinessRulePolicy;
use App\Models\RuleDefinition;
use App\Policies\EnterpriseEntityRecordPolicy;
use App\Policies\EntityDefinitionPolicy;
use App\Policies\FormDefinitionPolicy;
use App\Policies\TableDefinitionPolicy;
use App\Policies\DashboardDefinitionPolicy;
use App\Policies\ReportDefinitionPolicy;
use App\Policies\WorkflowMarketplacePolicy;
use App\Services\Runtime\AuditedWorkspaceRuntimeProvider;
use App\Services\Runtime\CachedWorkspaceRuntimeProvider;
use App\Services\Runtime\LaravelRuntimeCacheStore;
use App\Services\Runtime\RuntimeCacheStore;
use App\Services\Runtime\RuntimeMetricsCollector;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Services\WorkspaceApplication\WorkspaceRuntimeResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RuntimeMetricsCollector::class);

        $this->app->singleton(RuntimeCacheStore::class, function ($app) {
            $configuredStore = config('heos.runtime_cache.store');

            if (is_string($configuredStore) && $configuredStore !== '') {
                return new LaravelRuntimeCacheStore(Cache::store($configuredStore));
            }

            return new LaravelRuntimeCacheStore($app->make('cache.store'));
        });

        $this->app->bind(WorkspaceRuntimeProvider::class, function ($app) {
            $core = $app->make(WorkspaceRuntimeResolver::class);

            if (config('heos.runtime_cache.enabled', true)) {
                $core = new CachedWorkspaceRuntimeProvider(
                    $core,
                    $app->make(RuntimeCacheStore::class),
                    $app->make(\App\Services\Runtime\RuntimeCacheKeyBuilder::class),
                    $app->make(\App\Services\Runtime\RuntimeSnapshotSerializer::class),
                    $app->make(RuntimeMetricsCollector::class),
                );
            }

            return new AuditedWorkspaceRuntimeProvider(
                $core,
                $app->make(\App\Services\Audit\DomainAuditRecorder::class),
                $app->make(RuntimeMetricsCollector::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(WorkflowHumanTask::class, HumanTaskPolicy::class);
        Gate::policy(WorkflowAutomationRule::class, WorkflowAutomationPolicy::class);
        Gate::policy(WorkflowCanvasSnapshot::class, WorkflowDesignerPolicy::class);
        Gate::policy(WorkflowPackage::class, WorkflowMarketplacePolicy::class);
        Gate::policy(BusinessModule::class, BusinessModulePolicy::class);
        Gate::policy(EntityDefinition::class, EntityDefinitionPolicy::class);
        Gate::policy(EnterpriseDocument::class, EnterpriseDocumentPolicy::class);
        Gate::policy(EnterpriseNotification::class, EnterpriseNotificationPolicy::class);
        Gate::policy(RuleDefinition::class, EnterpriseBusinessRulePolicy::class);
        Gate::policy(EnterpriseEntityRecord::class, EnterpriseEntityRecordPolicy::class);
        Gate::policy(FormDefinition::class, FormDefinitionPolicy::class);
        Gate::policy(TableDefinition::class, TableDefinitionPolicy::class);
        Gate::policy(DashboardDefinition::class, DashboardDefinitionPolicy::class);
        Gate::policy(ReportDefinition::class, ReportDefinitionPolicy::class);

        Application::observe(ApplicationRuntimeCacheObserver::class);
        ApplicationSettingDefinition::observe(ApplicationSettingDefinitionRuntimeCacheObserver::class);
    }
}

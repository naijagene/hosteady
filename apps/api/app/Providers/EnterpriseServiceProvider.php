<?php

namespace App\Providers;

use App\Modules\Sdk\Enterprise\Contracts\EventBusPort;
use App\Modules\Sdk\Enterprise\Contracts\FileServicePort;
use App\Modules\Sdk\Enterprise\Contracts\IndexPort;
use App\Modules\Sdk\Enterprise\Contracts\NotificationPort;
use App\Modules\Sdk\Enterprise\Contracts\PlatformJobPort;
use App\Modules\Sdk\Enterprise\Contracts\ReferenceDataPort;
use App\Modules\Sdk\Enterprise\Contracts\SchedulerPort;
use App\Modules\Sdk\Enterprise\Contracts\SearchPort;
use App\Modules\Sdk\Enterprise\Contracts\StoragePort;
use App\Modules\Sdk\Workflow\Contracts\WorkflowPort;
use App\Modules\Sdk\Workflow\Contracts\WorkflowValidator;
use App\Services\Enterprise\Audit\EnterprisePlatformJobAuditRecorder;
use App\Services\Enterprise\Audit\EnterpriseSchedulerAuditRecorder;
use App\Services\Enterprise\Audit\EnterpriseSearchAuditRecorder;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Services\Enterprise\EventBus\LaravelEventBusAdapter;
use App\Services\Enterprise\EventBus\PlatformEventMapper;
use App\Services\Enterprise\EventBus\PlatformEventProcessor;
use App\Services\Enterprise\FileMedia\EnterpriseStorageHealthService;
use App\Services\Enterprise\FileMedia\FileCategoryClassifier;
use App\Services\Enterprise\FileMedia\FileQueryService;
use App\Services\Enterprise\FileMedia\FileService;
use App\Services\Enterprise\FileMedia\FileVisibilityResolver;
use App\Services\Enterprise\FileMedia\LaravelFileServiceAdapter;
use App\Services\Enterprise\FileMedia\LaravelStorageAdapter;
use App\Services\Enterprise\Jobs\LaravelPlatformJobAdapter;
use App\Services\Enterprise\Jobs\PlatformJobHandlerRegistry;
use App\Services\Enterprise\Jobs\PlatformJobHealthService;
use App\Services\Enterprise\Jobs\PlatformJobQueryService;
use App\Services\Enterprise\Jobs\PlatformJobService;
use App\Services\Enterprise\Jobs\PlatformJobTracker;
use App\Services\Enterprise\Notification\InAppNotificationChannel;
use App\Services\Enterprise\Notification\LaravelNotificationAdapter;
use App\Services\Enterprise\Notification\LogEmailNotificationChannel;
use App\Services\Enterprise\Notification\NotificationQueryService;
use App\Services\Enterprise\Notification\NotificationService;
use App\Services\Enterprise\ReferenceData\LaravelReferenceDataAdapter;
use App\Services\Enterprise\ReferenceData\ReferenceCatalogRegistry;
use App\Services\Enterprise\ReferenceData\ReferenceDataService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeContextFactory;
use App\Services\Enterprise\Scheduler\LaravelSchedulerAdapter;
use App\Services\Enterprise\Scheduler\ScheduleExpressionHelper;
use App\Services\Enterprise\Scheduler\ScheduledTaskRunner;
use App\Services\Enterprise\Scheduler\SchedulerHealthService;
use App\Services\Enterprise\Scheduler\SchedulerService;
use App\Services\Enterprise\Search\LaravelSearchAdapter;
use App\Services\Enterprise\Search\SearchHealthService;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Services\Enterprise\Search\SearchModuleRegistry;
use App\Services\Enterprise\Search\SearchService;
use App\Services\Enterprise\Search\SearchVisibilityResolver;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Services\Enterprise\Workflow\Automation\LaravelWorkflowAutomationAdapter;
use App\Services\Enterprise\Workflow\Automation\WorkflowAutomationAuditRecorder;
use App\Services\Enterprise\Workflow\Automation\WorkflowAutomationHealthService;
use App\Services\Enterprise\Workflow\Automation\WorkflowAutomationIntegrations;
use App\Services\Enterprise\Workflow\Automation\WorkflowAutomationService;
use App\Services\Enterprise\Workflow\Automation\WorkflowAutomationStatisticsService;
use App\Services\Enterprise\Workflow\Automation\WorkflowEventTriggerService;
use App\Services\Enterprise\Workflow\Automation\WorkflowScheduledTriggerService;
use App\Services\Enterprise\Workflow\Automation\WorkflowTimerRunner;
use App\Services\Enterprise\Workflow\Automation\WorkflowTimerService;
use App\Services\Enterprise\Workflow\Automation\WorkflowTriggerService;
use App\Services\Enterprise\Workflow\Human\ApprovalService;
use App\Services\Enterprise\Workflow\Human\DefaultAssignmentStrategy;
use App\Services\Enterprise\Workflow\Human\HumanTaskAuditRecorder;
use App\Services\Enterprise\Workflow\Human\HumanTaskHealthService;
use App\Services\Enterprise\Workflow\Human\HumanTaskIntegrations;
use App\Services\Enterprise\Workflow\Human\HumanTaskRuntimeBridge;
use App\Services\Enterprise\Workflow\Human\HumanTaskService;
use App\Services\Enterprise\Workflow\Human\HumanTaskStatisticsService;
use App\Services\Enterprise\Workflow\Human\LaravelApprovalPortAdapter;
use App\Services\Enterprise\Workflow\Human\LaravelHumanTaskAdapter;
use App\Services\Enterprise\Workflow\Human\TaskAssignmentService;
use App\Services\Enterprise\Workflow\Human\TaskEscalationService;
use App\Services\Enterprise\Workflow\Human\TaskHistoryService;
use App\Services\Enterprise\Workflow\Human\TaskInboxService;
use App\Services\Enterprise\Workflow\Human\WorkflowHumanTaskCompletionHandler;
use App\Services\Enterprise\Workflow\LaravelWorkflowAdapter;
use App\Services\Enterprise\Workflow\WorkflowAuditRecorder;
use App\Services\Enterprise\Workflow\WorkflowCategoryService;
use App\Services\Enterprise\Workflow\WorkflowDefinitionService;
use App\Services\Enterprise\Workflow\WorkflowHealthService;
use App\Services\Enterprise\Workflow\WorkflowSearchIndexer;
use App\Services\Enterprise\Workflow\WorkflowStatisticsService;
use App\Services\Enterprise\Workflow\WorkflowValidationService;
use App\Services\Enterprise\Workflow\WorkflowVersionService;
use App\Services\Enterprise\Workflow\Runtime\DefaultWorkflowExecutionHandler;
use App\Services\Enterprise\Workflow\Runtime\LaravelWorkflowRuntimeAdapter;
use App\Services\Enterprise\Workflow\Runtime\WorkflowExecutionAuditRecorder;
use App\Services\Enterprise\Workflow\Runtime\WorkflowExecutionContextBuilder;
use App\Services\Enterprise\Workflow\Runtime\WorkflowExecutionEngine;
use App\Services\Enterprise\Workflow\Runtime\WorkflowExecutionLogger;
use App\Services\Enterprise\Workflow\Runtime\WorkflowExecutionStatisticsService;
use App\Services\Enterprise\Workflow\Runtime\WorkflowExecutionTracker;
use App\Services\Enterprise\Workflow\Runtime\WorkflowExecutionVariableResolver;
use App\Services\Enterprise\Workflow\Runtime\WorkflowRuntimeHealthService;
use App\Services\Enterprise\Workflow\Runtime\WorkflowRuntimeIntegrations;
use App\Services\Enterprise\Workflow\Runtime\WorkflowRuntimeService;
use Illuminate\Support\ServiceProvider;

class EnterpriseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReferenceCatalogRegistry::class);
        $this->app->singleton(EnterpriseRuntimeContextFactory::class);
        $this->app->singleton(EnterpriseRuntimeBridge::class);
        $this->app->singleton(PlatformEventMapper::class);
        $this->app->singleton(NotificationQueryService::class);

        $this->app->singleton(PlatformEventProcessor::class, function ($app) {
            return new PlatformEventProcessor(
                subscribers: [
                    $app->make(WorkflowEventTriggerService::class),
                ],
                auditRecorder: $app->make(\App\Services\Enterprise\Audit\EnterpriseEventAuditRecorder::class),
                mapper: $app->make(PlatformEventMapper::class),
            );
        });

        $this->app->singleton(LaravelEventBusAdapter::class);
        $this->app->singleton(LaravelNotificationAdapter::class, function ($app) {
            return new LaravelNotificationAdapter(
                channels: [
                    'in_app' => $app->make(InAppNotificationChannel::class),
                    'log_email' => $app->make(LogEmailNotificationChannel::class),
                ],
                auditRecorder: $app->make(\App\Services\Enterprise\Audit\EnterpriseNotificationAuditRecorder::class),
            );
        });
        $this->app->singleton(LaravelReferenceDataAdapter::class);

        $this->app->singleton(FileCategoryClassifier::class);
        $this->app->singleton(FileQueryService::class);
        $this->app->singleton(FileVisibilityResolver::class);
        $this->app->singleton(EnterpriseTableHealthGuard::class);
        $this->app->singleton(EnterpriseStorageHealthService::class);
        $this->app->singleton(LaravelStorageAdapter::class);
        $this->app->singleton(LaravelFileServiceAdapter::class);

        $this->app->singleton(StoragePort::class, LaravelStorageAdapter::class);
        $this->app->singleton(FileServicePort::class, LaravelFileServiceAdapter::class);
        $this->app->singleton(FileService::class);

        $this->app->singleton(PlatformJobHandlerRegistry::class);
        $this->app->singleton(PlatformJobTracker::class);
        $this->app->singleton(PlatformJobQueryService::class);
        $this->app->singleton(PlatformJobHealthService::class);
        $this->app->singleton(LaravelPlatformJobAdapter::class);
        $this->app->singleton(PlatformJobPort::class, LaravelPlatformJobAdapter::class);
        $this->app->singleton(PlatformJobService::class);

        $this->app->singleton(ScheduleExpressionHelper::class);
        $this->app->singleton(ScheduledTaskRunner::class);
        $this->app->singleton(SchedulerHealthService::class);
        $this->app->singleton(LaravelSchedulerAdapter::class);
        $this->app->singleton(SchedulerPort::class, LaravelSchedulerAdapter::class);
        $this->app->singleton(SchedulerService::class);

        $this->app->singleton(SearchModuleRegistry::class);
        $this->app->singleton(SearchVisibilityResolver::class);
        $this->app->singleton(SearchHealthService::class);
        $this->app->singleton(LaravelSearchAdapter::class);
        $this->app->singleton(SearchPort::class, LaravelSearchAdapter::class);
        $this->app->singleton(IndexPort::class, LaravelSearchAdapter::class);
        $this->app->singleton(SearchService::class);
        $this->app->singleton(SearchIndexService::class);

        $this->app->singleton(WorkflowValidationService::class);
        $this->app->singleton(WorkflowValidator::class, WorkflowValidationService::class);
        $this->app->singleton(WorkflowVersionService::class);
        $this->app->singleton(WorkflowStatisticsService::class);
        $this->app->singleton(WorkflowHealthService::class);
        $this->app->singleton(WorkflowAuditRecorder::class);
        $this->app->singleton(WorkflowSearchIndexer::class);
        $this->app->singleton(LaravelWorkflowAdapter::class);
        $this->app->singleton(WorkflowPort::class, LaravelWorkflowAdapter::class);
        $this->app->singleton(WorkflowDefinitionService::class);
        $this->app->singleton(WorkflowCategoryService::class);

        $this->app->singleton(DefaultWorkflowExecutionHandler::class);
        $this->app->singleton(\App\Modules\Sdk\Workflow\Runtime\Contracts\WorkflowExecutionHandler::class, DefaultWorkflowExecutionHandler::class);
        $this->app->singleton(WorkflowExecutionTracker::class);
        $this->app->singleton(WorkflowExecutionLogger::class);
        $this->app->singleton(WorkflowExecutionVariableResolver::class);
        $this->app->singleton(WorkflowExecutionContextBuilder::class);
        $this->app->singleton(WorkflowExecutionAuditRecorder::class);
        $this->app->singleton(WorkflowExecutionEngine::class);
        $this->app->singleton(WorkflowExecutionStatisticsService::class);
        $this->app->singleton(WorkflowRuntimeHealthService::class);
        $this->app->singleton(WorkflowRuntimeIntegrations::class);
        $this->app->singleton(LaravelWorkflowRuntimeAdapter::class);
        $this->app->singleton(\App\Modules\Sdk\Workflow\Runtime\Contracts\WorkflowRuntimePort::class, LaravelWorkflowRuntimeAdapter::class);
        $this->app->singleton(WorkflowRuntimeService::class);

        $this->app->singleton(DefaultAssignmentStrategy::class);
        $this->app->singleton(TaskAssignmentService::class);
        $this->app->singleton(TaskHistoryService::class);
        $this->app->singleton(TaskEscalationService::class);
        $this->app->singleton(HumanTaskStatisticsService::class);
        $this->app->singleton(HumanTaskAuditRecorder::class);
        $this->app->singleton(LaravelHumanTaskAdapter::class);
        $this->app->singleton(LaravelApprovalPortAdapter::class);
        $this->app->singleton(\App\Modules\Sdk\Workflow\Human\Contracts\HumanTaskPort::class, LaravelHumanTaskAdapter::class);
        $this->app->singleton(\App\Modules\Sdk\Workflow\Human\Contracts\ApprovalPort::class, LaravelApprovalPortAdapter::class);
        $this->app->singleton(HumanTaskIntegrations::class);
        $this->app->singleton(HumanTaskHealthService::class);
        $this->app->singleton(WorkflowHumanTaskCompletionHandler::class);
        $this->app->singleton(\App\Modules\Sdk\Workflow\Human\Contracts\ApprovalDecisionHandler::class, WorkflowHumanTaskCompletionHandler::class);
        $this->app->singleton(HumanTaskRuntimeBridge::class);
        $this->app->singleton(HumanTaskService::class);
        $this->app->singleton(ApprovalService::class);
        $this->app->singleton(TaskInboxService::class);

        $this->app->singleton(WorkflowAutomationStatisticsService::class);
        $this->app->singleton(WorkflowAutomationAuditRecorder::class);
        $this->app->singleton(WorkflowAutomationIntegrations::class);
        $this->app->singleton(WorkflowTriggerService::class);
        $this->app->singleton(WorkflowScheduledTriggerService::class);
        $this->app->singleton(LaravelWorkflowAutomationAdapter::class);
        $this->app->singleton(\App\Modules\Sdk\Workflow\Automation\Contracts\WorkflowAutomationPort::class, LaravelWorkflowAutomationAdapter::class);
        $this->app->singleton(\App\Modules\Sdk\Workflow\Automation\Contracts\WorkflowTriggerHandler::class, WorkflowTriggerService::class);
        $this->app->singleton(\App\Modules\Sdk\Workflow\Automation\Contracts\WorkflowTimerHandler::class, WorkflowTimerService::class);
        $this->app->singleton(\App\Modules\Sdk\Workflow\Automation\Contracts\WorkflowEventTriggerProvider::class, WorkflowEventTriggerService::class);
        $this->app->singleton(WorkflowEventTriggerService::class);
        $this->app->singleton(WorkflowTimerService::class);
        $this->app->singleton(WorkflowAutomationHealthService::class);
        $this->app->singleton(WorkflowAutomationService::class);
        $this->app->singleton(WorkflowTimerRunner::class);

        $this->app->singleton(\App\Services\Enterprise\Audit\EnterpriseFileAuditRecorder::class);
        $this->app->singleton(EnterprisePlatformJobAuditRecorder::class);
        $this->app->singleton(EnterpriseSchedulerAuditRecorder::class);
        $this->app->singleton(EnterpriseSearchAuditRecorder::class);

        $this->app->singleton(EventBusPort::class, LaravelEventBusAdapter::class);
        $this->app->singleton(NotificationPort::class, LaravelNotificationAdapter::class);
        $this->app->singleton(ReferenceDataPort::class, LaravelReferenceDataAdapter::class);

        $this->app->singleton(EventBusService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(ReferenceDataService::class);

        $this->app->singleton(\App\Services\Enterprise\Audit\EnterpriseEventAuditRecorder::class);
        $this->app->singleton(\App\Services\Enterprise\Audit\EnterpriseNotificationAuditRecorder::class);
        $this->app->singleton(\App\Services\Enterprise\Audit\EnterpriseReferenceAuditRecorder::class);
        $this->app->singleton(InAppNotificationChannel::class);
        $this->app->singleton(LogEmailNotificationChannel::class);
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            $this->app->make(PlatformJobHandlerRegistry::class)->register(
                'workflow.automation.trigger',
                fn (\App\Models\PlatformJob $job) => $this->app->make(WorkflowScheduledTriggerService::class)->executeFromJob($job),
            );
        });
    }
}

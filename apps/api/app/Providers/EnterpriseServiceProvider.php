<?php

namespace App\Providers;

use App\Modules\Sdk\Enterprise\Contracts\EventBusPort;
use App\Modules\Sdk\Enterprise\Contracts\FileServicePort;
use App\Modules\Sdk\Enterprise\Contracts\NotificationPort;
use App\Modules\Sdk\Enterprise\Contracts\PlatformJobPort;
use App\Modules\Sdk\Enterprise\Contracts\ReferenceDataPort;
use App\Modules\Sdk\Enterprise\Contracts\SchedulerPort;
use App\Modules\Sdk\Enterprise\Contracts\StoragePort;
use App\Services\Enterprise\Audit\EnterprisePlatformJobAuditRecorder;
use App\Services\Enterprise\Audit\EnterpriseSchedulerAuditRecorder;
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
                subscribers: [],
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

        $this->app->singleton(\App\Services\Enterprise\Audit\EnterpriseFileAuditRecorder::class);
        $this->app->singleton(EnterprisePlatformJobAuditRecorder::class);
        $this->app->singleton(EnterpriseSchedulerAuditRecorder::class);

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
}

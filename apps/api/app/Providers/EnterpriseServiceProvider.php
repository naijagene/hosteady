<?php

namespace App\Providers;

use App\Services\Enterprise\FileMedia\EnterpriseStorageHealthService;
use App\Services\Enterprise\FileMedia\FileQueryService;
use App\Services\Enterprise\FileMedia\FileService;
use App\Services\Enterprise\FileMedia\FileVisibilityResolver;
use App\Services\Enterprise\FileMedia\LaravelFileServiceAdapter;
use App\Services\Enterprise\FileMedia\LaravelStorageAdapter;
use App\Modules\Sdk\Enterprise\Contracts\EventBusPort;
use App\Modules\Sdk\Enterprise\Contracts\FileServicePort;
use App\Modules\Sdk\Enterprise\Contracts\StoragePort;
use App\Modules\Sdk\Enterprise\Contracts\NotificationPort;
use App\Modules\Sdk\Enterprise\Contracts\ReferenceDataPort;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Services\Enterprise\EventBus\LaravelEventBusAdapter;
use App\Services\Enterprise\EventBus\PlatformEventMapper;
use App\Services\Enterprise\EventBus\PlatformEventProcessor;
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

        $this->app->singleton(\App\Services\Enterprise\Audit\EnterpriseFileAuditRecorder::class);

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

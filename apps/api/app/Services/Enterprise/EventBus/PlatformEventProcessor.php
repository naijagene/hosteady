<?php

namespace App\Services\Enterprise\EventBus;

use App\Enums\PlatformEventStatus;
use App\Models\PlatformEvent;
use App\Modules\Sdk\Enterprise\Contracts\ModuleEventSubscriber;
use App\Services\Enterprise\Audit\EnterpriseEventAuditRecorder;

class PlatformEventProcessor
{
    /**
     * @param  list<ModuleEventSubscriber>  $subscribers
     */
    public function __construct(
        private readonly array $subscribers,
        private readonly EnterpriseEventAuditRecorder $auditRecorder,
        private readonly PlatformEventMapper $mapper,
    ) {
    }

    public function process(PlatformEvent $event): void
    {
        $event->loadMissing(['organization', 'workspace']);
        $eventData = $this->mapper->toData($event);

        foreach ($this->subscribers as $subscriber) {
            if (! in_array($event->event_name, $subscriber->subscribedEvents(), true)) {
                continue;
            }

            $subscriber->handle($eventData);
        }

        $event->update([
            'status' => PlatformEventStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    public function processByPublicId(string $eventPublicId): void
    {
        $event = PlatformEvent::query()->where('public_id', $eventPublicId)->firstOrFail();

        try {
            $this->process($event);
            $this->auditRecorder->recordProcessed($event->fresh());
        } catch (\Throwable $exception) {
            $event->update([
                'status' => PlatformEventStatus::Failed,
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);
            $this->auditRecorder->recordFailed($event, $exception->getMessage());

            throw $exception;
        }
    }
}

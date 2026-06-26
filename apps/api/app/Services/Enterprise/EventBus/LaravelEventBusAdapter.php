<?php

namespace App\Services\Enterprise\EventBus;

use App\Enums\PlatformEventStatus;
use App\Models\Organization;
use App\Models\PlatformEvent;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Contracts\EventBusPort;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Modules\Sdk\Enterprise\Data\PlatformEventResult;
use App\Services\Enterprise\Audit\EnterpriseEventAuditRecorder;
use App\Services\Enterprise\EventBus\Jobs\ProcessPlatformEventJob;
use Illuminate\Support\Str;

class LaravelEventBusAdapter implements EventBusPort
{
    public function __construct(
        private readonly PlatformEventProcessor $processor,
        private readonly EnterpriseEventAuditRecorder $auditRecorder,
    ) {
    }

    public function dispatch(PlatformEventRequest $request): PlatformEventResult
    {
        $event = $this->persistEvent($request, async: false);
        $this->auditRecorder->recordDispatched($event);

        try {
            $this->processor->process($event);
            $event->refresh();
            $this->auditRecorder->recordProcessed($event);
        } catch (\Throwable $exception) {
            $event->update([
                'status' => PlatformEventStatus::Failed,
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);
            $this->auditRecorder->recordFailed($event, $exception->getMessage());
        }

        return new PlatformEventResult(
            eventPublicId: $event->public_id,
            status: $event->status->value,
            async: false,
        );
    }

    public function dispatchAsync(PlatformEventRequest $request): PlatformEventResult
    {
        $event = $this->persistEvent($request, async: true);
        $this->auditRecorder->recordDispatched($event);

        ProcessPlatformEventJob::dispatch($event->public_id);

        return new PlatformEventResult(
            eventPublicId: $event->public_id,
            status: $event->status->value,
            async: true,
        );
    }

    private function persistEvent(PlatformEventRequest $request, bool $async): PlatformEvent
    {
        $organization = Organization::query()
            ->where('public_id', $request->scope->organizationPublicId)
            ->firstOrFail();

        $workspaceId = null;

        if ($request->scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $request->scope->workspacePublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        return PlatformEvent::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organization->id,
            'workspace_id' => $workspaceId,
            'module_key' => $request->scope->moduleKey,
            'event_name' => $request->eventName,
            'payload' => $request->payload,
            'subject_reference' => $request->subject?->toArray(),
            'correlation_id' => $request->correlationId ?? (string) Str::uuid7(),
            'status' => PlatformEventStatus::Pending,
            'dispatched_at' => now(),
        ]);
    }
}

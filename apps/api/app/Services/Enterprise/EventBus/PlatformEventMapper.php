<?php

namespace App\Services\Enterprise\EventBus;

use App\Models\PlatformEvent;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformEventData;

class PlatformEventMapper
{
    public function toData(PlatformEvent $event): PlatformEventData
    {
        $subject = is_array($event->subject_reference)
            ? EntityReference::fromArray($event->subject_reference)
            : null;

        return new PlatformEventData(
            eventPublicId: $event->public_id,
            eventName: $event->event_name,
            scope: new EnterpriseScope(
                organizationPublicId: $event->organization->public_id,
                workspacePublicId: $event->workspace?->public_id,
                moduleKey: $event->module_key,
            ),
            payload: $event->payload ?? [],
            subject: $subject,
            correlationId: $event->correlation_id,
        );
    }
}

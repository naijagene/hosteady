<?php

namespace App\Services\Enterprise\Notification;

use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\NotificationRequest;

readonly class NotificationDeliveryContext
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public NotificationRequest $request,
        public string $organizationId,
        public ?string $workspaceId,
        public string $recipientMembershipId,
        public ?EntityReference $subject,
    ) {
    }
}

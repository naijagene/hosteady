<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class NotificationRequest
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $channels
     */
    public function __construct(
        public EnterpriseScope $scope,
        public string $recipientMembershipPublicId,
        public string $type,
        public string $title,
        public string $body,
        public array $data = [],
        public ?EntityReference $subject = null,
        public array $channels = ['in_app'],
    ) {
    }
}

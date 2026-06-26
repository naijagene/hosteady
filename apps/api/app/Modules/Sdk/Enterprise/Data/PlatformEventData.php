<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class PlatformEventData
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $eventPublicId,
        public string $eventName,
        public EnterpriseScope $scope,
        public array $payload,
        public ?EntityReference $subject = null,
        public ?string $correlationId = null,
    ) {
    }
}

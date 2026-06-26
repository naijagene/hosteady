<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class PlatformEventRequest
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public EnterpriseScope $scope,
        public string $eventName,
        public array $payload = [],
        public ?EntityReference $subject = null,
        public ?string $correlationId = null,
        public bool $async = false,
    ) {
    }
}

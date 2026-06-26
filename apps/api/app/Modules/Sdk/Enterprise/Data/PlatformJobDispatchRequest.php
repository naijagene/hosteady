<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class PlatformJobDispatchRequest
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public EnterpriseScope $scope,
        public string $jobType,
        public ?string $displayName = null,
        public array $payload = [],
        public ?EntityReference $entityReference = null,
        public string $priority = 'normal',
        public ?string $queueName = null,
        public ?int $maxAttempts = null,
        public ?string $correlationId = null,
        public ?string $createdMembershipPublicId = null,
        public ?string $scheduledTaskPublicId = null,
    ) {
    }
}

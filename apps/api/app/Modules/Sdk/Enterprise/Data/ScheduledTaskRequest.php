<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class ScheduledTaskRequest
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public EnterpriseScope $scope,
        public string $taskType,
        public string $displayName,
        public ?string $description = null,
        public ?string $cronExpression = null,
        public ?string $runAt = null,
        public ?string $timezone = null,
        public array $payload = [],
        public ?EntityReference $entityReference = null,
        public bool $enabled = true,
        public ?string $createdMembershipPublicId = null,
    ) {
    }
}

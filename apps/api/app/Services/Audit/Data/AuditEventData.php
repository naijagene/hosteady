<?php

namespace App\Services\Audit\Data;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;

readonly class AuditEventData
{
    /**
     * @param  array<string, mixed>|null  $beforeState
     * @param  array<string, mixed>|null  $afterState
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public AuditAction $action,
        public string $summary,
        public ?AuditScope $scope = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
        public ?AuditEntityType $entityType = null,
        public ?string $entityPublicId = null,
        public ?string $entityLabel = null,
        public ?array $beforeState = null,
        public ?array $afterState = null,
        public ?array $metadata = null,
        public ?AuditSeverity $severity = null,
        public ?AuditRetentionClass $retentionClass = null,
        public ?AuditActorType $actorType = null,
        public ?int $actorUserId = null,
        public ?string $actorMembershipId = null,
        public int $eventVersion = 1,
    ) {
    }
}

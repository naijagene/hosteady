<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationDeadLetterQueue
{
    public function enqueue(string $organizationId, ?string $workspaceId, array $payload): \App\Modules\Sdk\Integration\Data\IntegrationDeadLetterRecord;

    public function resolve(string $organizationId, ?string $workspaceId, string $publicId): \App\Modules\Sdk\Integration\Data\IntegrationDeadLetterRecord;
}

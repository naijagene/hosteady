<?php

namespace App\Modules\Sdk\Document\Contracts;

use App\Modules\Sdk\Document\Data\DocumentRetentionRule;

interface DocumentRetentionPolicy
{
    public function apply(string $organizationId, ?string $workspaceId, DocumentRetentionRule $rule): DocumentRetentionRule;
}

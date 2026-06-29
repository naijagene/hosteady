<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class QuickActionService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function generate(TenantContext $context): array
    {
        return [
            [
                'type' => 'navigation',
                'label' => 'Open workspace home',
                'metadata' => [
                    'workspace_public_id' => $context->workspacePublicId,
                ],
            ],
        ];
    }
}

<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class EnterpriseScope
{
    public function __construct(
        public string $organizationPublicId,
        public ?string $workspacePublicId = null,
        public ?string $moduleKey = null,
    ) {
    }
}

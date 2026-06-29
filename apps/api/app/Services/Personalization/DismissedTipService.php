<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class DismissedTipService
{
    public function record(TenantContext $context, string $flowKey, string $tipKey): void
    {
        // Best-effort metadata hook; onboarding state stores dismissed tips.
    }
}

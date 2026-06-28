<?php

namespace App\Services\Integration;

use App\Models\IntegrationDispatch;
use App\Modules\Sdk\Integration\Contracts\IntegrationRetryPolicy;
use App\Modules\Sdk\Integration\Enums\IntegrationDeliveryStatus;

class IntegrationRetryPolicyService implements IntegrationRetryPolicy
{
    public function scheduleRetry(IntegrationDispatch $dispatch): IntegrationDispatch
    {
        $attempt = (int) $dispatch->attempt + 1;
        $maxAttempts = (int) $dispatch->max_attempts;

        if ($attempt >= $maxAttempts) {
            $dispatch->status = IntegrationDeliveryStatus::Failed;
            $dispatch->completed_at = now();
            $dispatch->save();

            return $dispatch->fresh();
        }

        $delaySeconds = (int) (2 ** $attempt);
        $dispatch->attempt = $attempt;
        $dispatch->status = IntegrationDeliveryStatus::Pending;
        $dispatch->next_retry_at = now()->addSeconds($delaySeconds);
        $dispatch->save();

        return $dispatch->fresh();
    }
}

<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationRetryPolicy
{
    public function scheduleRetry(\App\Models\IntegrationDispatch $dispatch): \App\Models\IntegrationDispatch;
}

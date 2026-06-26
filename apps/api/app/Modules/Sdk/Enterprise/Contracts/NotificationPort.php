<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\NotificationRequest;
use App\Modules\Sdk\Enterprise\Data\NotificationResult;

interface NotificationPort
{
    public function notify(NotificationRequest $request): NotificationResult;
}

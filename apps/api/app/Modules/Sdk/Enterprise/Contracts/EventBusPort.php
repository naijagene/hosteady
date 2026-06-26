<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Modules\Sdk\Enterprise\Data\PlatformEventResult;

interface EventBusPort
{
    public function dispatch(PlatformEventRequest $request): PlatformEventResult;

    public function dispatchAsync(PlatformEventRequest $request): PlatformEventResult;
}

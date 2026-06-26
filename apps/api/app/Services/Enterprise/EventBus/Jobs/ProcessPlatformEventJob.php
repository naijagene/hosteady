<?php

namespace App\Services\Enterprise\EventBus\Jobs;

use App\Services\Enterprise\EventBus\PlatformEventProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPlatformEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $eventPublicId,
    ) {
    }

    public function handle(PlatformEventProcessor $processor): void
    {
        $processor->processByPublicId($this->eventPublicId);
    }
}

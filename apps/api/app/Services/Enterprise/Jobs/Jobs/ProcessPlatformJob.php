<?php

namespace App\Services\Enterprise\Jobs\Jobs;

use App\Services\Enterprise\Jobs\PlatformJobTracker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPlatformJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $jobPublicId,
    ) {
    }

    public function handle(PlatformJobTracker $tracker): void
    {
        $tracker->execute($this->jobPublicId);
    }
}

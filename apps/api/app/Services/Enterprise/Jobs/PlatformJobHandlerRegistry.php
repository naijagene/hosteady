<?php

namespace App\Services\Enterprise\Jobs;

use App\Models\PlatformJob;

class PlatformJobHandlerRegistry
{
    /**
     * @var array<string, callable(PlatformJob): array<string, mixed>>
     */
    private array $handlers = [];

    /**
     * @param  callable(PlatformJob): array<string, mixed>  $handler
     */
    public function register(string $jobType, callable $handler): void
    {
        $this->handlers[$jobType] = $handler;
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(PlatformJob $job): array
    {
        $handler = $this->handlers[$job->job_type] ?? null;

        if ($handler !== null) {
            return $handler($job);
        }

        return [
            'status' => 'noop',
            'message' => sprintf('No handler registered for job type [%s].', $job->job_type),
        ];
    }
}

<?php

namespace App\Services\Enterprise\Jobs;

use App\Enums\PlatformJobStatus;
use App\Models\PlatformJob;
use App\Services\Enterprise\Audit\EnterprisePlatformJobAuditRecorder;

class PlatformJobTracker
{
    public function __construct(
        private readonly PlatformJobHandlerRegistry $handlerRegistry,
        private readonly EnterprisePlatformJobAuditRecorder $auditRecorder,
    ) {
    }

    public function execute(string $jobPublicId): void
    {
        $job = PlatformJob::query()
            ->where('public_id', $jobPublicId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        if (in_array($job->status, [PlatformJobStatus::Cancelled, PlatformJobStatus::Succeeded], true)) {
            return;
        }

        $job->status = PlatformJobStatus::Running;
        $job->started_at = now();
        $job->save();

        $this->auditRecorder->recordStarted($job);

        try {
            $result = $this->handlerRegistry->execute($job);

            $job->result = $result;
            $job->status = PlatformJobStatus::Succeeded;
            $job->finished_at = now();
            $job->save();

            $this->auditRecorder->recordCompleted($job);
        } catch (\Throwable $exception) {
            $job->attempts++;
            $job->error_message = $exception->getMessage();
            $job->error_class = $exception::class;

            if ($job->attempts >= $job->max_attempts) {
                $job->status = PlatformJobStatus::Failed;
                $job->failed_at = now();
                $job->finished_at = now();
                $job->save();

                $this->auditRecorder->recordFailed($job);

                throw $exception;
            }

            $job->status = PlatformJobStatus::Queued;
            $job->save();

            $this->auditRecorder->recordFailed($job);

            throw $exception;
        }
    }
}

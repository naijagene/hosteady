<?php

namespace App\Services\Enterprise\Jobs;

use App\Enums\PlatformJobPriority;
use App\Enums\PlatformJobStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\PlatformJob;
use App\Models\ScheduledTask;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Contracts\PlatformJobPort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformJobDispatchRequest;
use App\Modules\Sdk\Enterprise\Data\PlatformJobReference;
use App\Modules\Sdk\Enterprise\Data\PlatformJobResult;
use App\Services\Enterprise\Audit\EnterprisePlatformJobAuditRecorder;
use App\Services\Enterprise\Jobs\Jobs\ProcessPlatformJob;
use Illuminate\Support\Str;

class LaravelPlatformJobAdapter implements PlatformJobPort
{
    public function __construct(
        private readonly EnterprisePlatformJobAuditRecorder $auditRecorder,
    ) {
    }

    public function dispatch(PlatformJobDispatchRequest $request): PlatformJobResult
    {
        $organization = Organization::query()
            ->where('public_id', $request->scope->organizationPublicId)
            ->firstOrFail();

        $workspaceId = null;

        if ($request->scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $request->scope->workspacePublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        $membership = $this->resolveMembership($organization->id, $request->createdMembershipPublicId);
        $scheduledTaskId = null;

        if ($request->scheduledTaskPublicId !== null) {
            $scheduledTaskId = ScheduledTask::query()
                ->where('public_id', $request->scheduledTaskPublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        $priority = PlatformJobPriority::tryFrom($request->priority) ?? PlatformJobPriority::Normal;
        $queueName = $request->queueName ?? (string) config('heos.enterprise.jobs.default_queue', 'default');
        $maxAttempts = $request->maxAttempts ?? (int) config('heos.enterprise.jobs.max_attempts', 3);

        $job = PlatformJob::query()->create([
            'organization_id' => $organization->id,
            'workspace_id' => $workspaceId,
            'module_key' => $request->scope->moduleKey,
            'job_type' => $request->jobType,
            'display_name' => $request->displayName ?? $request->jobType,
            'queue_name' => $queueName,
            'status' => PlatformJobStatus::Pending,
            'priority' => $priority,
            'payload' => $request->payload,
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'correlation_id' => $request->correlationId ?? (string) Str::uuid7(),
            'entity_reference' => $request->entityReference?->toArray(),
            'scheduled_task_id' => $scheduledTaskId,
            'created_by_user_id' => $membership->user_id,
            'created_membership_id' => $membership->id,
        ]);

        $job->status = PlatformJobStatus::Queued;
        $job->save();

        ProcessPlatformJob::dispatch($job->public_id)->onQueue($queueName);

        $job->refresh();

        $this->auditRecorder->recordDispatched($job);

        return new PlatformJobResult(
            job: $this->toReference($job),
            queued: true,
        );
    }

    public function cancel(EnterpriseScope $scope, string $jobPublicId): PlatformJobReference
    {
        $job = $this->findModel($scope, $jobPublicId);

        if (in_array($job->status, [PlatformJobStatus::Succeeded, PlatformJobStatus::Cancelled], true)) {
            return $this->toReference($job);
        }

        if ($job->status === PlatformJobStatus::Running) {
            abort(409, 'Running jobs cannot be cancelled.');
        }

        $job->status = PlatformJobStatus::Cancelled;
        $job->cancelled_at = now();
        $job->finished_at = now();
        $job->save();

        $this->auditRecorder->recordCancelled($job);

        return $this->toReference($job);
    }

    public function find(EnterpriseScope $scope, string $jobPublicId): ?PlatformJobReference
    {
        $job = PlatformJob::query()
            ->where('public_id', $jobPublicId)
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at')
            ->first();

        return $job !== null ? $this->toReference($job) : null;
    }

    /**
     * @return list<PlatformJobReference>
     */
    public function list(EnterpriseScope $scope, int $limit = 50): array
    {
        $query = PlatformJob::query()
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at');

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $this->organizationId($scope))
                ->value('id');

            $query->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')
                    ->orWhere('workspace_id', $workspaceId);
            });
        }

        if ($scope->moduleKey !== null) {
            $query->where('module_key', $scope->moduleKey);
        }

        return $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (PlatformJob $job) => $this->toReference($job))
            ->all();
    }

    private function findModel(EnterpriseScope $scope, string $jobPublicId): PlatformJob
    {
        return PlatformJob::query()
            ->where('public_id', $jobPublicId)
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function organizationId(EnterpriseScope $scope): string
    {
        return (string) Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');
    }

    private function resolveMembership(string $organizationId, ?string $membershipPublicId): OrganizationMembership
    {
        $query = OrganizationMembership::query()->where('organization_id', $organizationId);

        if ($membershipPublicId !== null) {
            $query->where('public_id', $membershipPublicId);
        }

        return $query->firstOrFail();
    }

    private function toReference(PlatformJob $job): PlatformJobReference
    {
        $entityReference = null;

        if (is_array($job->entity_reference) && isset($job->entity_reference['type'])) {
            $entityReference = EntityReference::fromArray($job->entity_reference);
        }

        return new PlatformJobReference(
            publicId: $job->public_id,
            jobType: $job->job_type,
            status: $job->status->value,
            priority: $job->priority->value,
            displayName: $job->display_name,
            moduleKey: $job->module_key,
            entityReference: $entityReference,
            payload: $job->payload ?? [],
            result: $job->result,
            errorMessage: $job->error_message,
            attempts: $job->attempts,
            maxAttempts: $job->max_attempts,
            correlationId: $job->correlation_id,
            queueName: $job->queue_name,
            startedAt: $job->started_at?->toIso8601String(),
            finishedAt: $job->finished_at?->toIso8601String(),
            failedAt: $job->failed_at?->toIso8601String(),
            cancelledAt: $job->cancelled_at?->toIso8601String(),
            createdAt: $job->created_at?->toIso8601String(),
        );
    }
}

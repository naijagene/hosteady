<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskReference;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRequest;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRunReference;

interface SchedulerPort
{
    public function create(ScheduledTaskRequest $request): ScheduledTaskReference;

    public function pause(EnterpriseScope $scope, string $taskPublicId): ScheduledTaskReference;

    public function resume(EnterpriseScope $scope, string $taskPublicId): ScheduledTaskReference;

    public function cancel(EnterpriseScope $scope, string $taskPublicId): void;

    public function find(EnterpriseScope $scope, string $taskPublicId): ?ScheduledTaskReference;

    /**
     * @return list<ScheduledTaskReference>
     */
    public function list(EnterpriseScope $scope, int $limit = 50): array;

    /**
     * @return list<ScheduledTaskRunReference>
     */
    public function listRuns(EnterpriseScope $scope, string $taskPublicId, int $limit = 50): array;
}

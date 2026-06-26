<?php

namespace App\Modules\Sdk\Workflow\Automation\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationRule;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationStatistics;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTimerReference;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTriggerReference;

interface WorkflowAutomationPort
{
    /**
     * @return list<WorkflowAutomationRule>
     */
    public function listRules(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array;

    public function getRule(EnterpriseScope $scope, string $publicId): WorkflowAutomationRule;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRule(
        EnterpriseScope $scope,
        array $data,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowAutomationRule;

    public function enableRule(EnterpriseScope $scope, string $publicId): WorkflowAutomationRule;

    public function disableRule(EnterpriseScope $scope, string $publicId): WorkflowAutomationRule;

    public function deleteRule(EnterpriseScope $scope, string $publicId): void;

    /**
     * @return list<WorkflowTriggerReference>
     */
    public function listTriggerExecutions(EnterpriseScope $scope, int $limit = 50): array;

    /**
     * @return list<WorkflowTimerReference>
     */
    public function listTimers(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array;

    public function statistics(EnterpriseScope $scope): WorkflowAutomationStatistics;
}

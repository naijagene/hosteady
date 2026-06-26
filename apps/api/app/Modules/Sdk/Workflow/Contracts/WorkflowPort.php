<?php

namespace App\Modules\Sdk\Workflow\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Data\WorkflowCategoryReference;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionReference;
use App\Modules\Sdk\Workflow\Data\WorkflowPublishResult;
use App\Modules\Sdk\Workflow\Data\WorkflowStatistics;
use App\Modules\Sdk\Workflow\Data\WorkflowValidationReport;
use App\Modules\Sdk\Workflow\Data\WorkflowVersionData;

interface WorkflowPort
{
    /**
     * @return list<WorkflowDefinitionReference>
     */
    public function listDefinitions(EnterpriseScope $scope, ?string $status = null): array;

    public function getDefinition(EnterpriseScope $scope, string $publicId): WorkflowDefinitionReference;

    public function createDefinition(
        EnterpriseScope $scope,
        WorkflowDefinitionData $data,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowDefinitionReference;

    public function updateDefinition(
        EnterpriseScope $scope,
        string $publicId,
        WorkflowDefinitionData $data,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowDefinitionReference;

    public function publishDefinition(
        EnterpriseScope $scope,
        string $publicId,
        ?string $versionPublicId = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPublishResult;

    public function archiveDefinition(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowDefinitionReference;

    /**
     * @return list<WorkflowVersionData>
     */
    public function listVersions(EnterpriseScope $scope, string $definitionPublicId): array;

    public function validateDefinition(WorkflowDefinitionData $data): WorkflowValidationReport;

    /**
     * @return list<WorkflowCategoryReference>
     */
    public function listCategories(EnterpriseScope $scope): array;

    public function createCategory(
        EnterpriseScope $scope,
        string $categoryKey,
        string $name,
        ?string $description = null,
        ?string $moduleKey = null,
        ?array $metadata = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowCategoryReference;

    public function statistics(EnterpriseScope $scope): WorkflowStatistics;
}

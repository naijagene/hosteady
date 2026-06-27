<?php

namespace App\Services\Entity;

use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityHealthReport;
use App\Modules\Sdk\Entity\Data\EntityMutationRequest;
use App\Modules\Sdk\Entity\Data\EntityMutationResult;
use App\Modules\Sdk\Entity\Data\EntityStatistics;
use App\Modules\Sdk\Entity\Data\EntityValidationReport;
use App\Modules\Sdk\Entity\Exceptions\EntityNotFoundException;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Services\Authorization\TenantAuthorizationService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnterpriseEntityDevelopmentService
{
    public function __construct(
        private readonly EnterpriseEntityRegistryService $registryService,
        private readonly EnterpriseEntityRepositoryService $repositoryService,
        private readonly EnterpriseEntityValidationService $validationService,
        private readonly EnterpriseEntityRelationshipService $relationshipService,
        private readonly EnterpriseEntityActivityService $activityService,
        private readonly EnterpriseEntityCommentService $commentService,
        private readonly EnterpriseEntityTagService $tagService,
        private readonly EnterpriseEntityHealthService $healthService,
        private readonly EnterpriseEntityStatisticsService $statisticsService,
        private readonly EnterpriseEntityAuditRecorder $auditRecorder,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    /**
     * @return list<EntityDefinition>
     */
    public function listDefinitions(TenantContext $context, ?string $moduleKey = null): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->list($moduleKey);
    }

    public function showDefinition(TenantContext $context, string $moduleKey, string $entityKey): EntityDefinition
    {
        return $this->findDefinition($context, $moduleKey, $entityKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function registerRelationship(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        \App\Modules\Sdk\Entity\Data\EntityRelationshipDefinition|array $definition,
    ): array {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->relationshipService->register($moduleKey, $entityKey, $definition);
    }

    public function findDefinition(TenantContext $context, string $moduleKey, string $entityKey): EntityDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->registryService->find($moduleKey, $entityKey);

        if ($definition === null) {
            throw new EntityNotFoundException(sprintf('Entity [%s.%s] was not found.', $moduleKey, $entityKey));
        }

        return $definition;
    }

    public function findDefinitionByPublicId(TenantContext $context, string $publicId): EntityDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->registryService->findByPublicId($publicId);

        if ($definition === null) {
            throw new EntityNotFoundException(sprintf('Entity [%s] was not found.', $publicId));
        }

        return $definition;
    }

    /**
     * @param  EntityDefinition|array<string, mixed>|\App\Modules\Sdk\Entity\EnterpriseEntity|class-string<\App\Modules\Sdk\Entity\EnterpriseEntity>  $source
     */
    public function registerDefinition(TenantContext $context, mixed $source): EntityDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->register($source);
    }

    public function updateDefinition(TenantContext $context, EntityDefinition $definition): EntityDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->update($definition);
    }

    /**
     * @param  list<array<string, mixed>>  $entities
     * @return list<EntityDefinition>
     */
    public function registerFromManifestEntities(TenantContext $context, array $entities, string $moduleKey): array
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->registerFromManifestEntities($entities, $moduleKey);
    }

    /**
     * @param  EntityDefinition|array<string, mixed>|\App\Modules\Sdk\Entity\EnterpriseEntity|class-string<\App\Modules\Sdk\Entity\EnterpriseEntity>  $source
     */
    public function validateDefinition(mixed $source): EntityValidationReport
    {
        $definition = $this->validationService->resolveDefinition($source);
        $report = $this->validationService->validate($definition);
        $this->auditRecorder->recordValidated($definition);

        return $report;
    }

    public function mutateEntity(TenantContext $context, EntityMutationRequest $request): EntityMutationResult
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->repositoryService->mutate($request);
    }

    public function validateMutation(TenantContext $context, EntityMutationRequest $request): EntityValidationReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->repositoryService->validateMutation($request);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRelationships(TenantContext $context, string $moduleKey, string $entityKey): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->relationshipService->listForEntity($moduleKey, $entityKey);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActivity(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
    ): array {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->activityService->list(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $entityPublicId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function createComment(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $commentBody,
        array $metadata = [],
    ): array {
        $this->requireCapability($context);
        $this->assertComment($context);

        return $this->commentService->create(
            $this->scope($context),
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $entityPublicId,
            $commentBody,
            $context->user->id,
            $context->membership->id,
            $metadata,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listComments(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
    ): array {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->commentService->list(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $entityPublicId,
        );
    }

    public function deleteComment(TenantContext $context, string $commentPublicId): void
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $this->commentService->delete(
            $context->organization->id,
            $context->workspace?->id,
            $commentPublicId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function createTag(
        TenantContext $context,
        string $tagKey,
        string $name,
        ?string $color = null,
        array $metadata = [],
    ): array {
        $this->requireCapability($context);
        $this->assertTag($context);

        return $this->tagService->createTag(
            $this->scope($context),
            $context->organization->id,
            $context->workspace?->id,
            $tagKey,
            $name,
            $color,
            $metadata,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTags(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->tagService->listTags(
            $context->organization->id,
            $context->workspace?->id,
        );
    }

    public function attachTag(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $tagPublicId,
    ): void {
        $this->requireCapability($context);
        $this->assertTag($context);

        $this->tagService->attachTag(
            $this->scope($context),
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $entityPublicId,
            $tagPublicId,
        );
    }

    public function detachTag(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $tagPublicId,
    ): void {
        $this->requireCapability($context);
        $this->assertTag($context);

        $this->tagService->detachTag(
            $this->scope($context),
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $entityPublicId,
            $tagPublicId,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTagsForEntity(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
    ): array {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->tagService->listTagsForEntity(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $entityPublicId,
        );
    }

    public function health(TenantContext $context): EntityHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): EntityStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope(
            $context->organization,
            $context->workspace,
        );
    }

    private function scope(TenantContext $context): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
        );
    }

    private function requireCapability(TenantContext $context): void
    {
        $this->runtimeBridge->requireCapability($context, 'entities');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'entities.read')) {
            throw new HttpException(403, 'You do not have permission to read entities.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'entities.manage')) {
            throw new HttpException(403, 'You do not have permission to manage entities.');
        }
    }

    private function assertComment(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'entities.comment')) {
            throw new HttpException(403, 'You do not have permission to comment on entities.');
        }
    }

    private function assertTag(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'entities.tag')) {
            throw new HttpException(403, 'You do not have permission to tag entities.');
        }
    }
}

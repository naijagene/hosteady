<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Contracts\EntityRecordMutationHandler;
use App\Modules\Sdk\DataRepository\Contracts\EntityRecordQueryProvider;
use App\Modules\Sdk\DataRepository\Contracts\EntityRecordRepository;
use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordCreateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordDeleteRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordHealthReport;
use App\Modules\Sdk\DataRepository\Data\EntityRecordMutationResult;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryResult;
use App\Modules\Sdk\DataRepository\Data\EntityRecordRestoreRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordStatistics;
use App\Modules\Sdk\DataRepository\Data\EntityRecordUpdateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordValidationReport;
use App\Modules\Sdk\DataRepository\Exceptions\EntityRecordNotFoundException;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Services\Authorization\TenantAuthorizationService;
use App\Services\Entity\EnterpriseEntityCommentService;
use App\Services\Entity\EnterpriseEntityRegistryService;
use App\Services\Entity\EnterpriseEntityTagService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnterpriseEntityRecordDevelopmentService
{
    public function __construct(
        private readonly EntityRecordRepository $repository,
        private readonly EntityRecordMutationHandler $mutationHandler,
        private readonly EntityRecordQueryProvider $queryProvider,
        private readonly EnterpriseEntityRecordValidationService $validationService,
        private readonly EnterpriseEntityRecordVersionService $versionService,
        private readonly EnterpriseEntityRecordRelationshipService $relationshipService,
        private readonly EnterpriseEntityRecordActivityService $activityService,
        private readonly EnterpriseEntityRecordHealthService $healthService,
        private readonly EnterpriseEntityRecordStatisticsService $statisticsService,
        private readonly EnterpriseEntityRecordAuditRecorder $auditRecorder,
        private readonly EnterpriseEntityRecordPolicyResolverService $policyResolver,
        private readonly EnterpriseEntityRegistryService $registryService,
        private readonly EnterpriseEntityCommentService $commentService,
        private readonly EnterpriseEntityTagService $tagService,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function query(TenantContext $context, EntityRecordQueryRequest $request): EntityRecordQueryResult
    {
        $this->requireCapability($context);
        $this->assertQuery($context);
        $this->repository->resolveDefinition($request->moduleKey, $request->entityKey);

        $result = $this->queryProvider->query(
            $context->organization->id,
            $context->workspace?->id,
            $request,
        );

        try {
            app(EnterpriseEntityRecordLifecycleService::class)->dispatchQueried(
                $context,
                $request->moduleKey,
                $request->entityKey,
                $result->total,
            );
        } catch (\Throwable) {
        }

        return $result;
    }

    public function show(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): EntityRecord {
        $this->requireCapability($context);
        $this->assertRead($context);

        $record = $this->repository->find(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $recordPublicId,
        );

        if ($record === null) {
            throw new EntityRecordNotFoundException(sprintf('Entity record [%s] was not found.', $recordPublicId));
        }

        return $record;
    }

    public function create(TenantContext $context, EntityRecordCreateRequest $request): EntityRecordMutationResult
    {
        $this->requireCapability($context);
        $this->assertCreate($context);

        return $this->mutationHandler->create(
            $context->organization->id,
            $context->workspace?->id,
            $request,
        );
    }

    public function update(TenantContext $context, EntityRecordUpdateRequest $request): EntityRecordMutationResult
    {
        $this->requireCapability($context);
        $this->assertUpdate($context);

        return $this->mutationHandler->update(
            $context->organization->id,
            $context->workspace?->id,
            $request,
        );
    }

    public function delete(TenantContext $context, EntityRecordDeleteRequest $request): EntityRecordMutationResult
    {
        $this->requireCapability($context);
        $this->assertDelete($context);

        return $this->mutationHandler->delete(
            $context->organization->id,
            $context->workspace?->id,
            $request,
        );
    }

    public function restore(TenantContext $context, EntityRecordRestoreRequest $request): EntityRecordMutationResult
    {
        $this->requireCapability($context);
        $this->assertRestore($context);

        return $this->mutationHandler->restore(
            $context->organization->id,
            $context->workspace?->id,
            $request,
        );
    }

    public function validateCreate(TenantContext $context, EntityRecordCreateRequest $request): EntityRecordValidationReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);
        $definition = $this->repository->resolveDefinition($request->moduleKey, $request->entityKey);

        return $this->validationService->validateCreate($request, $definition);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listVersions(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): array {
        $this->requireCapability($context);
        $this->assertRead($context);
        $this->show($context, $moduleKey, $entityKey, $recordPublicId);

        return $this->versionService->list(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $recordPublicId,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActivity(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): array {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->activityService->list(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $recordPublicId,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listLinks(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): array {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->relationshipService->listLinks(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $recordPublicId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function createLink(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
        array $payload,
    ): array {
        $this->requireCapability($context);
        $this->assertLink($context);

        return $this->relationshipService->link(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $recordPublicId,
            (string) ($payload['target_module_key'] ?? ''),
            (string) ($payload['target_entity_key'] ?? ''),
            (string) ($payload['target_record_public_id'] ?? ''),
            (string) ($payload['relationship_key'] ?? 'related'),
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    public function deleteLink(TenantContext $context, string $linkPublicId): void
    {
        $this->requireCapability($context);
        $this->assertLink($context);

        $this->relationshipService->unlink(
            $context->organization->id,
            $context->workspace?->id,
            $linkPublicId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function createComment(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
        string $commentBody,
        array $metadata = [],
    ): array {
        $this->requireCapability($context);
        $this->assertRead($context);
        if (! $this->authorizationService->allows($context, 'entities.comment')) {
            throw new HttpException(403, 'You do not have permission to comment on entity records.');
        }
        $this->show($context, $moduleKey, $entityKey, $recordPublicId);

        return $this->commentService->create(
            $this->scope($context),
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $recordPublicId,
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
        string $recordPublicId,
    ): array {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->commentService->list(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $recordPublicId,
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
        $this->assertRead($context);
        if (! $this->authorizationService->allows($context, 'entities.tag')) {
            throw new HttpException(403, 'You do not have permission to tag entity records.');
        }

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

    public function attachTag(
        TenantContext $context,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
        string $tagPublicId,
    ): void {
        $this->requireCapability($context);
        $this->assertRead($context);
        if (! $this->authorizationService->allows($context, 'entities.tag')) {
            throw new HttpException(403, 'You do not have permission to tag entity records.');
        }
        $this->show($context, $moduleKey, $entityKey, $recordPublicId);

        $this->tagService->attachTag(
            $this->scope($context),
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $entityKey,
            $recordPublicId,
            $tagPublicId,
        );
    }

    public function health(TenantContext $context): EntityRecordHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): EntityRecordStatistics
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
        $this->runtimeBridge->requireCapability($context, 'data_repository');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->policyResolver->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read entity records.');
        }
    }

    private function assertCreate(TenantContext $context): void
    {
        if (! $this->policyResolver->canCreate($context)) {
            throw new HttpException(403, 'You do not have permission to create entity records.');
        }
    }

    private function assertUpdate(TenantContext $context): void
    {
        if (! $this->policyResolver->canUpdate($context)) {
            throw new HttpException(403, 'You do not have permission to update entity records.');
        }
    }

    private function assertDelete(TenantContext $context): void
    {
        if (! $this->policyResolver->canDelete($context)) {
            throw new HttpException(403, 'You do not have permission to delete entity records.');
        }
    }

    private function assertRestore(TenantContext $context): void
    {
        if (! $this->policyResolver->canRestore($context)) {
            throw new HttpException(403, 'You do not have permission to restore entity records.');
        }
    }

    private function assertLink(TenantContext $context): void
    {
        if (! $this->policyResolver->canLink($context)) {
            throw new HttpException(403, 'You do not have permission to link entity records.');
        }
    }

    private function assertQuery(TenantContext $context): void
    {
        if (! $this->policyResolver->canQuery($context)) {
            throw new HttpException(403, 'You do not have permission to query entity records.');
        }
    }
}

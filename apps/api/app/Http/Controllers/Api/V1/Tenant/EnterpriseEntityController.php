<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntityActivityLogResource;
use App\Http\Resources\EntityCommentResource;
use App\Http\Resources\EntityDefinitionResource;
use App\Http\Resources\EntityRelationshipResource;
use App\Http\Resources\EntityTagResource;
use App\Models\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityRelationshipDefinition;
use App\Services\Entity\EnterpriseEntityDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class EnterpriseEntityController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EnterpriseEntityDevelopmentService $developmentService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', EntityDefinition::class);
        $context = app(TenantContext::class);

        return EntityDefinitionResource::collection($this->developmentService->listDefinitions($context));
    }

    public function show(string $moduleKey, string $entityKey): EntityDefinitionResource
    {
        $this->authorize('view', EntityDefinition::class);
        $context = app(TenantContext::class);

        return new EntityDefinitionResource(
            $this->developmentService->showDefinition($context, $moduleKey, $entityKey),
        );
    }

    public function relationships(string $moduleKey, string $entityKey): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', EntityDefinition::class);
        $context = app(TenantContext::class);

        return EntityRelationshipResource::collection(
            $this->developmentService->listRelationships($context, $moduleKey, $entityKey),
        );
    }

    public function storeRelationship(Request $request, string $moduleKey, string $entityKey): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', EntityDefinition::class);
        $validated = $request->validate([
            'relationship_key' => ['required', 'string', 'max:128'],
            'relationship_type' => ['required', 'string', 'max:32'],
            'target_module_key' => ['nullable', 'string', 'max:128'],
            'target_entity_key' => ['nullable', 'string', 'max:128'],
            'label' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new EntityRelationshipResource(
            $this->developmentService->registerRelationship(
                $context,
                $moduleKey,
                $entityKey,
                EntityRelationshipDefinition::fromArray(array_merge($validated, [
                    'source_module_key' => $moduleKey,
                    'source_entity_key' => $entityKey,
                ])),
            ),
        ))->response()->setStatusCode(201);
    }

    public function activity(
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
    ): \Illuminate\Http\Resources\Json\AnonymousResourceCollection {
        $this->authorize('view', EntityDefinition::class);
        $context = app(TenantContext::class);

        return EntityActivityLogResource::collection(
            $this->developmentService->listActivity($context, $moduleKey, $entityKey, $entityPublicId),
        );
    }

    public function comments(
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
    ): \Illuminate\Http\Resources\Json\AnonymousResourceCollection {
        $this->authorize('view', EntityDefinition::class);
        $context = app(TenantContext::class);

        return EntityCommentResource::collection(
            $this->developmentService->listComments($context, $moduleKey, $entityKey, $entityPublicId),
        );
    }

    public function storeComment(
        Request $request,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
    ): \Illuminate\Http\JsonResponse {
        $this->authorize('comment', EntityDefinition::class);
        $validated = $request->validate([
            'comment_body' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new EntityCommentResource(
            $this->developmentService->createComment(
                $context,
                $moduleKey,
                $entityKey,
                $entityPublicId,
                $validated['comment_body'],
                $validated['metadata'] ?? [],
            ),
        ))->response()->setStatusCode(201);
    }

    public function destroyComment(string $commentPublicId): \Illuminate\Http\Response
    {
        $this->authorize('manage', EntityDefinition::class);
        $context = app(TenantContext::class);

        $this->developmentService->deleteComment($context, $commentPublicId);

        return response()->noContent();
    }

    public function tags(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', EntityDefinition::class);
        $context = app(TenantContext::class);

        return EntityTagResource::collection($this->developmentService->listTags($context));
    }

    public function storeTag(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('tag', EntityDefinition::class);
        $validated = $request->validate([
            'tag_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new EntityTagResource(
            $this->developmentService->createTag(
                $context,
                $validated['tag_key'],
                $validated['name'],
                $validated['color'] ?? null,
                $validated['metadata'] ?? [],
            ),
        ))->response()->setStatusCode(201);
    }

    public function attachTag(
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $tagPublicId,
    ): \Illuminate\Http\Response {
        $this->authorize('tag', EntityDefinition::class);
        $context = app(TenantContext::class);

        $this->developmentService->attachTag(
            $context,
            $moduleKey,
            $entityKey,
            $entityPublicId,
            $tagPublicId,
        );

        return response()->noContent();
    }

    public function detachTag(
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $tagPublicId,
    ): \Illuminate\Http\Response {
        $this->authorize('tag', EntityDefinition::class);
        $context = app(TenantContext::class);

        $this->developmentService->detachTag(
            $context,
            $moduleKey,
            $entityKey,
            $entityPublicId,
            $tagPublicId,
        );

        return response()->noContent();
    }
}

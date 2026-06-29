<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandProfileResource;
use App\Http\Resources\ThemeDefinitionResource;
use App\Http\Resources\ThemeHealthResource;
use App\Http\Resources\ThemeRuntimeResource;
use App\Http\Resources\ThemeStatisticsResource;
use App\Http\Resources\ThemeVersionResource;
use App\Models\ThemeDefinition;
use App\Modules\Sdk\Theme\Data\ThemeDefinition as ThemeDefinitionDto;
use App\Services\Theme\ThemeDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterpriseThemeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ThemeDevelopmentService $developmentService) {}

    public function indexThemes(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ThemeDefinition::class);

        return ThemeDefinitionResource::collection(
            $this->developmentService->listDefinitions(app(TenantContext::class)),
        );
    }

    public function storeTheme(Request $request): JsonResponse
    {
        $this->authorize('create', ThemeDefinition::class);
        $validated = $request->validate([
            'module_key' => ['nullable', 'string', 'max:64'],
            'theme_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'inheritance_mode' => ['nullable', 'string'],
            'parent_theme_public_id' => ['nullable', 'string'],
            'tokens' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'application_public_id' => ['nullable', 'string'],
        ]);

        $created = $this->developmentService->registerDefinition(
            app(TenantContext::class),
            ThemeDefinitionDto::fromArray($validated),
        );

        return (new ThemeDefinitionResource($created))->response()->setStatusCode(201);
    }

    public function showTheme(string $themePublicId): ThemeDefinitionResource
    {
        $this->authorize('view', ThemeDefinition::class);

        return new ThemeDefinitionResource(
            $this->developmentService->findDefinitionByPublicId(app(TenantContext::class), $themePublicId),
        );
    }

    public function updateTheme(Request $request, string $themePublicId): ThemeDefinitionResource
    {
        $this->authorize('update', ThemeDefinition::class);
        $validated = $request->validate([
            'module_key' => ['sometimes', 'nullable', 'string', 'max:64'],
            'theme_key' => ['sometimes', 'string', 'max:128'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string'],
            'scope' => ['sometimes', 'string'],
            'inheritance_mode' => ['sometimes', 'string'],
            'parent_theme_public_id' => ['sometimes', 'nullable', 'string'],
            'tokens' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
            'application_public_id' => ['sometimes', 'nullable', 'string'],
        ]);

        return new ThemeDefinitionResource(
            $this->developmentService->updateDefinitionByPublicId(
                app(TenantContext::class),
                $themePublicId,
                $validated,
            ),
        );
    }

    public function indexBrandProfiles(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ThemeDefinition::class);

        return BrandProfileResource::collection(
            $this->developmentService->listBrandProfiles(app(TenantContext::class)),
        );
    }

    public function showBrandProfile(string $brandProfilePublicId): BrandProfileResource
    {
        $this->authorize('view', ThemeDefinition::class);

        return new BrandProfileResource(
            $this->developmentService->findBrandProfileByPublicId(app(TenantContext::class), $brandProfilePublicId),
        );
    }

    public function updateBrandProfile(Request $request, string $themePublicId): BrandProfileResource
    {
        $this->authorize('brand', ThemeDefinition::class);
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:512'],
            'colors' => ['nullable', 'array'],
            'typography' => ['nullable', 'array'],
            'assets' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        return new BrandProfileResource(
            $this->developmentService->updateBrandProfile(
                app(TenantContext::class),
                $themePublicId,
                $validated,
            ),
        );
    }

    public function indexVersions(string $themePublicId): AnonymousResourceCollection
    {
        $this->authorize('view', ThemeDefinition::class);

        return ThemeVersionResource::collection(
            $this->developmentService->listVersionsForDefinition(app(TenantContext::class), $themePublicId),
        );
    }

    public function storeVersion(Request $request, string $themePublicId): JsonResponse
    {
        $this->authorize('create', ThemeDefinition::class);
        $validated = $request->validate([
            'snapshot' => ['nullable', 'array'],
            'change_summary' => ['nullable', 'string'],
        ]);

        $created = $this->developmentService->createThemeVersion(
            app(TenantContext::class),
            $themePublicId,
            $validated['snapshot'] ?? [],
            $validated['change_summary'] ?? null,
        );

        return (new ThemeVersionResource($created))->response()->setStatusCode(201);
    }

    public function publishTheme(Request $request, string $themePublicId): ThemeDefinitionResource
    {
        $this->authorize('publish', ThemeDefinition::class);
        $validated = $request->validate([
            'version_public_id' => ['nullable', 'string'],
        ]);

        return new ThemeDefinitionResource(
            $this->developmentService->publishDefinition(
                app(TenantContext::class),
                $themePublicId,
                $validated['version_public_id'] ?? null,
            ),
        );
    }

    public function renderTheme(Request $request, string $themePublicId): ThemeRuntimeResource
    {
        $this->authorize('view', ThemeDefinition::class);

        return new ThemeRuntimeResource(
            $this->developmentService->renderDefinition(app(TenantContext::class), $themePublicId)->toArray(),
        );
    }

    public function runtime(Request $request): ThemeRuntimeResource
    {
        $this->authorize('viewAny', ThemeDefinition::class);
        $validated = $request->validate([
            'theme_key' => ['nullable', 'string', 'max:128'],
            'module_key' => ['nullable', 'string', 'max:64'],
        ]);

        return new ThemeRuntimeResource(
            $this->developmentService->composeRuntime(
                app(TenantContext::class),
                $validated['theme_key'] ?? 'default',
                $validated['module_key'] ?? null,
            ),
        );
    }

    public function statistics(): ThemeStatisticsResource
    {
        $this->authorize('viewAny', ThemeDefinition::class);

        return new ThemeStatisticsResource($this->developmentService->statistics(app(TenantContext::class)));
    }

    public function health(): ThemeHealthResource
    {
        $this->authorize('viewAny', ThemeDefinition::class);

        return new ThemeHealthResource($this->developmentService->health(app(TenantContext::class)));
    }
}

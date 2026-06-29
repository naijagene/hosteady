<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteResource;
use App\Http\Resources\OnboardingStateResource;
use App\Http\Resources\PersonalizationHealthResource;
use App\Http\Resources\PersonalizationRuntimeResource;
use App\Http\Resources\PersonalizationStatisticsResource;
use App\Http\Resources\PreferenceResource;
use App\Http\Resources\RecentItemResource;
use App\Http\Resources\ShortcutResource;
use App\Services\Personalization\PersonalizationDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterprisePersonalizationController extends Controller
{
    public function __construct(
        private readonly PersonalizationDevelopmentService $developmentService,
    ) {
    }

    public function runtime(TenantContext $context): PersonalizationRuntimeResource
    {
        return new PersonalizationRuntimeResource($this->developmentService->runtime($context));
    }

    public function health(TenantContext $context): PersonalizationHealthResource
    {
        return new PersonalizationHealthResource($this->developmentService->health($context));
    }

    public function statistics(TenantContext $context): PersonalizationStatisticsResource
    {
        return new PersonalizationStatisticsResource($this->developmentService->statistics($context));
    }

    public function indexPreferences(TenantContext $context): AnonymousResourceCollection
    {
        return PreferenceResource::collection($this->developmentService->listPreferences($context));
    }

    public function patchPreferences(Request $request, TenantContext $context): AnonymousResourceCollection
    {
        $input = (array) $request->input('preferences', []);

        return PreferenceResource::collection($this->developmentService->patchPreferences($context, $input));
    }

    public function indexFavorites(TenantContext $context): AnonymousResourceCollection
    {
        return FavoriteResource::collection($this->developmentService->listFavorites($context));
    }

    public function storeFavorite(Request $request, TenantContext $context): JsonResponse
    {
        return (new FavoriteResource($this->developmentService->addFavorite($context, $request->all())))
            ->response()
            ->setStatusCode(201);
    }

    public function destroyFavorite(string $favoritePublicId, TenantContext $context): JsonResponse
    {
        $this->developmentService->removeFavorite($context, $favoritePublicId);

        return response()->json(['deleted' => true]);
    }

    public function indexRecent(TenantContext $context): AnonymousResourceCollection
    {
        return RecentItemResource::collection($this->developmentService->listRecent($context));
    }

    public function storeRecent(Request $request, TenantContext $context): JsonResponse
    {
        return (new RecentItemResource($this->developmentService->recordRecent($context, $request->all())))
            ->response()
            ->setStatusCode(201);
    }

    public function indexShortcuts(TenantContext $context): AnonymousResourceCollection
    {
        return ShortcutResource::collection($this->developmentService->listShortcuts($context));
    }

    public function storeShortcut(Request $request, TenantContext $context): JsonResponse
    {
        return (new ShortcutResource($this->developmentService->createShortcut($context, $request->all())))
            ->response()
            ->setStatusCode(201);
    }

    public function patchShortcut(string $shortcutPublicId, Request $request, TenantContext $context): ShortcutResource
    {
        return new ShortcutResource($this->developmentService->updateShortcut($context, $shortcutPublicId, $request->all()));
    }

    public function destroyShortcut(string $shortcutPublicId, TenantContext $context): JsonResponse
    {
        $this->developmentService->deleteShortcut($context, $shortcutPublicId);

        return response()->json(['deleted' => true]);
    }

    public function onboardingIndex(TenantContext $context): AnonymousResourceCollection
    {
        return OnboardingStateResource::collection($this->developmentService->onboarding($context));
    }

    public function onboardingStart(Request $request, TenantContext $context): OnboardingStateResource
    {
        return new OnboardingStateResource(
            $this->developmentService->onboardingStart($context, (string) $request->input('flow_key', 'default'))
        );
    }

    public function onboardingStep(Request $request, TenantContext $context): OnboardingStateResource
    {
        return new OnboardingStateResource(
            $this->developmentService->onboardingStep(
                $context,
                (string) $request->input('flow_key', 'default'),
                (string) $request->input('step', 'unknown')
            )
        );
    }

    public function onboardingComplete(Request $request, TenantContext $context): OnboardingStateResource
    {
        return new OnboardingStateResource(
            $this->developmentService->onboardingComplete($context, (string) $request->input('flow_key', 'default'))
        );
    }

    public function onboardingReset(Request $request, TenantContext $context): OnboardingStateResource
    {
        return new OnboardingStateResource(
            $this->developmentService->onboardingReset($context, (string) $request->input('flow_key', 'default'))
        );
    }
}

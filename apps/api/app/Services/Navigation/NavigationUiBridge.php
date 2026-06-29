<?php

namespace App\Services\Navigation;

use App\Services\Ui\UiPageRegistryService;
use App\Support\Tenant\TenantContext;

class NavigationUiBridge
{
    public function __construct(
        private readonly UiPageRegistryService $pageRegistry,
    ) {
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>|null
     */
    public function resolvePageReferenceBestEffort(?string $moduleKey, ?string $route, array $metadata = []): ?array
    {
        try {
            if (! app()->bound(TenantContext::class)) {
                return null;
            }

            if (! (bool) config('heos.enterprise.ui_metadata.enabled', true)) {
                return null;
            }

            $context = app(TenantContext::class);
            $pagePublicId = (string) ($metadata['page_public_id'] ?? '');
            $pageKey = (string) ($metadata['page_key'] ?? '');

            if ($pagePublicId !== '') {
                $query = \App\Models\UiPage::query()->where('public_id', $pagePublicId);
                $page = $query->first();

                return $page !== null ? [
                    'public_id' => $page->public_id,
                    'module_key' => $page->module_key,
                    'page_key' => $page->page_key,
                    'route_path' => $page->route_path,
                ] : null;
            }

            if ($moduleKey !== null && $pageKey !== '') {
                $definition = $this->pageRegistry->findByKey(
                    $context->organization->id,
                    $context->workspace?->id,
                    $moduleKey,
                    $pageKey,
                );

                return $definition->toArray();
            }

            if ($route !== null && $route !== '') {
                $definition = $this->pageRegistry->findByRoutePath(
                    $context->organization->id,
                    $context->workspace?->id,
                    $route,
                );

                return $definition->toArray();
            }
        } catch (\Throwable) {
        }

        return null;
    }
}

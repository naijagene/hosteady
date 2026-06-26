<?php

namespace App\Services\Enterprise\Search;

use App\Enums\SearchVisibility;
use App\Support\Tenant\TenantContext;

class SearchVisibilityResolver
{
    public function applyScope($query, TenantContext $context): void
    {
        $query->where(function ($builder) use ($context) {
            $builder->whereNull('workspace_id')
                ->orWhere('workspace_id', $context->workspace->id);
        });

        $query->where(function ($builder) use ($context) {
            $builder->where('visibility', SearchVisibility::Organization->value)
                ->orWhere(function ($nested) use ($context) {
                    $nested->where('visibility', SearchVisibility::Workspace->value)
                        ->where('workspace_id', $context->workspace->id);
                })
                ->orWhere(function ($nested) use ($context) {
                    $nested->where('visibility', SearchVisibility::Private->value)
                        ->where('workspace_id', $context->workspace->id);
                });
        });
    }
}

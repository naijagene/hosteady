<?php

namespace App\Http\Resources;

use App\Services\Runtime\Data\WorkspaceRuntimeHealth;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkspaceRuntimeHealth
 */
class WorkspaceRuntimeHealthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkspaceRuntimeHealth $health */
        $health = $this->resource;

        return [
            'health' => $health->health->value,
            'diagnostics' => $health->diagnostics->toArray(),
            'integrity' => $health->integrity->toArray(),
            'cache' => $health->cache->toArray(),
            'dependency_summary' => $health->dependencySummary,
            'recommendations' => $health->recommendations,
        ];
    }
}

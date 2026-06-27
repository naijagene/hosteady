<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowPackageVersion */
class WorkflowPackageVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowPackageVersion $version */
        $version = $this->resource;

        return $version->toArray();
    }
}

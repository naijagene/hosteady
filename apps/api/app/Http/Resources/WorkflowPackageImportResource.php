<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowPackage */
class WorkflowPackageImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowPackage $package */
        $package = $this->resource;

        return $package->toArray();
    }
}

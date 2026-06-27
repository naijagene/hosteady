<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackage;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowPackage|WorkflowPackageReference */
class WorkflowPackageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowPackage|WorkflowPackageReference $package */
        $package = $this->resource;

        return $package->toArray();
    }
}

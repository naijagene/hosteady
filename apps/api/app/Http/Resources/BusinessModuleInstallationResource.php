<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Development\Data\BusinessModuleInstallResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BusinessModuleInstallResult */
class BusinessModuleInstallationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BusinessModuleInstallResult $installation */
        $installation = $this->resource;

        return $installation->toArray();
    }
}

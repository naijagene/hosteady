<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Development\Data\BusinessModuleInstallResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BusinessModuleInstallResult */
class BusinessModuleInstallResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BusinessModuleInstallResult $result */
        $result = $this->resource;

        return $result->toArray();
    }
}

<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Development\Data\BusinessModuleReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BusinessModuleReference */
class BusinessModuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BusinessModuleReference $module */
        $module = $this->resource;

        return $module->toArray();
    }
}

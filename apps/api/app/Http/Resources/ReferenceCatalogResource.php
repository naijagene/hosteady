<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Enterprise\Data\ReferenceCatalogData;
use App\Modules\Sdk\Enterprise\Data\ReferenceItemData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReferenceCatalogData */
class ReferenceCatalogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ReferenceCatalogData $catalog */
        $catalog = $this->resource;

        return [
            'key' => $catalog->key,
            'name' => $catalog->name,
            'version' => $catalog->version,
            'module_key' => $catalog->moduleKey,
            'description' => $catalog->description,
        ];
    }
}

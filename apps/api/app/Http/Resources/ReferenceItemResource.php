<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Enterprise\Data\ReferenceItemData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReferenceItemData */
class ReferenceItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ReferenceItemData $item */
        $item = $this->resource;

        return [
            'catalog_key' => $item->catalogKey,
            'code' => $item->code,
            'label' => $item->label,
            'metadata' => $item->metadata,
            'sort_order' => $item->sortOrder,
            'active' => $item->active,
        ];
    }
}

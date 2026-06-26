<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Enterprise\Data\SavedSearchReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SavedSearchReference */
class SavedSearchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SavedSearchReference $saved */
        $saved = $this->resource;

        return $saved->toArray();
    }
}

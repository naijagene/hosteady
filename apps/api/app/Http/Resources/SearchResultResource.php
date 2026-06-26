<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Enterprise\Data\SearchResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SearchResult */
class SearchResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SearchResult $result */
        $result = $this->resource;

        return $result->toArray();
    }
}

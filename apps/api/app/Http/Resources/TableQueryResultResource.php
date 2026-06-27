<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Table\Data\TableQueryResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TableQueryResult */
class TableQueryResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TableQueryResult $result */
        $result = $this->resource;

        return $result->toArray();
    }
}

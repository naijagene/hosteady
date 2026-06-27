<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Table\Data\TableView;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TableView */
class TableViewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TableView $view */
        $view = $this->resource;

        return $view->toArray();
    }
}

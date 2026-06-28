<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Dashboard\Data\DashboardView;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DashboardView */
class DashboardViewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DashboardView $view */
        $view = $this->resource;

        return $view->toArray();
    }
}

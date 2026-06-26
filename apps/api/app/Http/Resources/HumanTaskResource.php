<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Human\Data\HumanTaskReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HumanTaskReference */
class HumanTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var HumanTaskReference $task */
        $task = $this->resource;

        return $task->toArray();
    }
}

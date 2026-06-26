<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Human\Data\ApprovalReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApprovalReference */
class ApprovalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ApprovalReference $approval */
        $approval = $this->resource;

        return $approval->toArray();
    }
}

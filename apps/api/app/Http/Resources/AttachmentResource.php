<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Document\Data\AttachmentReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttachmentReference */
class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}

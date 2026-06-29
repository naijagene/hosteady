<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Personalization\Data\ShortcutItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortcutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ShortcutItem) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}

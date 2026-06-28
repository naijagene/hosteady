<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Application\Data\ApplicationWorkspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApplicationWorkspace */
class ApplicationWorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ApplicationWorkspace) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}

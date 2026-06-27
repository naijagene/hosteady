<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Form\Data\FormDraftReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FormDraftReference */
class FormDraftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof FormDraftReference) {
            return $this->resource->toArray();
        }

        /** @var array<string, mixed> $draft */
        $draft = $this->resource;

        return [
            'public_id' => $draft['public_id'] ?? null,
            'module_key' => $draft['module_key'] ?? null,
            'form_key' => $draft['form_key'] ?? null,
            'draft_id' => $draft['draft_id'] ?? $draft['public_id'] ?? null,
            'expires_at' => $draft['expires_at'] ?? null,
            'metadata' => $draft['metadata'] ?? [],
        ];
    }
}

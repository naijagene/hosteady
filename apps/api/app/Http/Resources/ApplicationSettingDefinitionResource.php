<?php

namespace App\Http\Resources;

use App\Services\Application\Data\SettingDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SettingDefinition
 */
class ApplicationSettingDefinitionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SettingDefinition $definition */
        $definition = $this->resource;

        return [
            'public_id' => $definition->publicId,
            'setting_key' => $definition->settingKey,
            'label' => $definition->label,
            'description' => $definition->description,
            'type' => $definition->settingType->value,
            'default_value' => $definition->defaultValue,
            'is_required' => $definition->isRequired,
            'is_sensitive' => $definition->isSensitive,
            'is_encrypted' => $definition->isEncrypted,
            'scope' => $definition->scope->value,
            'category' => $definition->category,
            'sort_order' => $definition->sortOrder,
            'validation_rules' => $definition->validationRules?->toArray(),
            'status' => $definition->status->value,
        ];
    }
}

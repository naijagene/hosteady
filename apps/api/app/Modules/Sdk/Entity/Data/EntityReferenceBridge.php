<?php

namespace App\Modules\Sdk\Entity\Data;

/**
 * Bridges to the existing enterprise EntityReference DTO.
 *
 * @see \App\Modules\Sdk\Enterprise\Data\EntityReference
 */
final class EntityReferenceBridge
{
    public static function fromEntity(
        string $moduleKey,
        string $entityKey,
        string $publicId,
        ?string $label = null,
    ): \App\Modules\Sdk\Enterprise\Data\EntityReference {
        return new \App\Modules\Sdk\Enterprise\Data\EntityReference(
            type: $moduleKey.'.'.$entityKey,
            publicId: $publicId,
            moduleKey: $moduleKey,
            label: $label ?? $entityKey,
        );
    }

    public static function fromArray(array $data): \App\Modules\Sdk\Enterprise\Data\EntityReference
    {
        return \App\Modules\Sdk\Enterprise\Data\EntityReference::fromArray($data);
    }
}

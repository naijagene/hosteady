<?php

namespace App\Services\WorkspaceApplication;

use App\Enums\WorkspaceSettingType;
use App\Exceptions\WorkspaceApplication\InvalidWorkspaceSettingTypeException;

class WorkspaceSettingValueValidator
{
    public function __construct(
        private readonly WorkspaceSettingsNormalizer $normalizer,
    ) {
    }

    public function assertValid(mixed $value, WorkspaceSettingType $type): mixed
    {
        return match ($type) {
            WorkspaceSettingType::String => $this->validateString($value),
            WorkspaceSettingType::Boolean => $this->validateBoolean($value),
            WorkspaceSettingType::Integer => $this->validateInteger($value),
            WorkspaceSettingType::Float => $this->validateFloat($value),
            WorkspaceSettingType::Array => $this->validateArray($value),
            WorkspaceSettingType::Json => $this->validateJson($value),
        };
    }

    private function validateString(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            throw new InvalidWorkspaceSettingTypeException('String settings require a string value.');
        }

        return $this->normalizer->normalize($value, WorkspaceSettingType::String);
    }

    private function validateBoolean(mixed $value): bool
    {
        if (! is_bool($value) && ! in_array($value, [0, 1, '0', '1', 'true', 'false', 'yes', 'no', 'on', 'off'], true)) {
            throw new InvalidWorkspaceSettingTypeException('Boolean settings require a boolean value.');
        }

        return $this->normalizer->normalize($value, WorkspaceSettingType::Boolean);
    }

    private function validateInteger(mixed $value): int
    {
        if (is_string($value) && ! is_numeric($value)) {
            throw new InvalidWorkspaceSettingTypeException('Integer settings require an integer value.');
        }

        if (is_float($value) && floor($value) !== $value) {
            throw new InvalidWorkspaceSettingTypeException('Integer settings require an integer value.');
        }

        return $this->normalizer->normalize($value, WorkspaceSettingType::Integer);
    }

    private function validateFloat(mixed $value): float
    {
        if (is_string($value) && ! is_numeric($value)) {
            throw new InvalidWorkspaceSettingTypeException('Float settings require a numeric value.');
        }

        if (! is_int($value) && ! is_float($value) && ! is_numeric($value)) {
            throw new InvalidWorkspaceSettingTypeException('Float settings require a numeric value.');
        }

        return $this->normalizer->normalize($value, WorkspaceSettingType::Float);
    }

    /**
     * @return list<mixed>
     */
    private function validateArray(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidWorkspaceSettingTypeException('Array settings require a JSON array value.');
        }

        return $this->normalizer->normalize($value, WorkspaceSettingType::Array);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateJson(mixed $value): array
    {
        if (! is_array($value) || array_is_list($value)) {
            throw new InvalidWorkspaceSettingTypeException('JSON settings require a JSON object value.');
        }

        return $this->normalizer->normalize($value, WorkspaceSettingType::Json);
    }
}

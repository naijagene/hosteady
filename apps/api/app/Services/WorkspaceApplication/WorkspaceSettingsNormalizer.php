<?php

namespace App\Services\WorkspaceApplication;

use App\Enums\WorkspaceSettingType;

class WorkspaceSettingsNormalizer
{
    public function normalize(mixed $value, WorkspaceSettingType $type): mixed
    {
        return match ($type) {
            WorkspaceSettingType::String => $this->normalizeString($value),
            WorkspaceSettingType::Boolean => $this->normalizeBoolean($value),
            WorkspaceSettingType::Integer => $this->normalizeInteger($value),
            WorkspaceSettingType::Float => $this->normalizeFloat($value),
            WorkspaceSettingType::Array => $this->normalizeArray($value),
            WorkspaceSettingType::Json => $this->normalizeJson($value),
        };
    }

    public function equals(mixed $left, mixed $right, WorkspaceSettingType $type): bool
    {
        return json_encode($this->normalize($left, $type)) === json_encode($this->normalize($right, $type));
    }

    private function normalizeString(mixed $value): string
    {
        return trim((string) $value);
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    private function normalizeInteger(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        return (int) $value;
    }

    private function normalizeFloat(mixed $value): float
    {
        return (float) $value;
    }

    /**
     * @return list<mixed>
     */
    private function normalizeArray(mixed $value): array
    {
        $array = is_array($value) ? array_values($value) : [(string) $value];

        return array_map(function (mixed $item) {
            if (is_array($item)) {
                return $this->sortRecursive($item);
            }

            return $item;
        }, $array);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeJson(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return $this->sortRecursive($value);
    }

    /**
     * @param  array<string|int, mixed>  $value
     * @return array<string|int, mixed>
     */
    private function sortRecursive(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(function (mixed $item) {
                if (is_array($item)) {
                    return $this->sortRecursive($item);
                }

                return $item;
            }, array_values($value));
        }

        ksort($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursive($item);
            }
        }

        return $value;
    }
}

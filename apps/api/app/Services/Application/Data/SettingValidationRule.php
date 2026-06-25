<?php

namespace App\Services\Application\Data;

readonly class SettingValidationRule
{
    public function __construct(
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public ?string $pattern = null,
        public int|float|null $min = null,
        public int|float|null $max = null,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $rules
     */
    public static function fromArray(?array $rules): ?self
    {
        if ($rules === null || $rules === []) {
            return null;
        }

        return new self(
            minLength: isset($rules['min_length']) ? (int) $rules['min_length'] : null,
            maxLength: isset($rules['max_length']) ? (int) $rules['max_length'] : null,
            pattern: isset($rules['pattern']) ? (string) $rules['pattern'] : null,
            min: $rules['min'] ?? null,
            max: $rules['max'] ?? null,
        );
    }

    /**
     * @return array<string, int|float|string>
     */
    public function toArray(): array
    {
        $payload = [];

        if ($this->minLength !== null) {
            $payload['min_length'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $payload['max_length'] = $this->maxLength;
        }

        if ($this->pattern !== null) {
            $payload['pattern'] = $this->pattern;
        }

        if ($this->min !== null) {
            $payload['min'] = $this->min;
        }

        if ($this->max !== null) {
            $payload['max'] = $this->max;
        }

        return $payload;
    }
}

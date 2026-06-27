<?php

namespace App\Modules\Sdk\Development\Data;

readonly class BusinessModuleHealthReport implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public bool $enabled,
        public int $registered,
        public int $installed,
        public array $warnings = [],
        public string $status = 'healthy',
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? false),
            registered: (int) ($data['registered'] ?? 0),
            installed: (int) ($data['installed'] ?? 0),
            warnings: is_array($data['warnings'] ?? null) ? array_values(array_map('strval', $data['warnings'])) : [],
            status: (string) ($data['status'] ?? 'healthy'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'registered' => $this->registered,
            'installed' => $this->installed,
            'warnings' => $this->warnings,
            'status' => $this->status,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

<?php

namespace App\Modules\Sdk\Development\Data;

readonly class BusinessModuleStatistics implements \JsonSerializable
{
    public function __construct(
        public int $registered,
        public int $installed,
        public int $enabledCount,
        public int $disabledCount,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            registered: (int) ($data['registered'] ?? 0),
            installed: (int) ($data['installed'] ?? 0),
            enabledCount: (int) ($data['enabled_count'] ?? 0),
            disabledCount: (int) ($data['disabled_count'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'registered' => $this->registered,
            'installed' => $this->installed,
            'enabled_count' => $this->enabledCount,
            'disabled_count' => $this->disabledCount,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

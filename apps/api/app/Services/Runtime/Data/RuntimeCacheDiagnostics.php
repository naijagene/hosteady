<?php

namespace App\Services\Runtime\Data;

readonly class RuntimeCacheDiagnostics
{
    public function __construct(
        public bool $enabled,
        public int $generation,
        public ?string $key,
        public int $ttl,
        public bool $hitPossible,
        public string $backend,
        public int $schemaVersion,
    ) {
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'generation' => $this->generation,
            'key' => $this->key,
            'ttl' => $this->ttl,
            'hit_possible' => $this->hitPossible,
            'backend' => $this->backend,
            'schema_version' => $this->schemaVersion,
        ];
    }
}

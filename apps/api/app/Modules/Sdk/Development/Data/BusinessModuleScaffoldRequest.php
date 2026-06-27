<?php

namespace App\Modules\Sdk\Development\Data;

use App\Modules\Sdk\Development\Enums\BusinessModuleScaffoldTarget;
use App\Modules\Sdk\Development\Enums\BusinessModuleType;

readonly class BusinessModuleScaffoldRequest implements \JsonSerializable
{
    /**
     * @param  list<string>  $targets
     */
    public function __construct(
        public string $moduleKey,
        public string $name,
        public string $type = BusinessModuleType::Business->value,
        public array $targets = [],
        public bool $force = false,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $targets = is_array($data['targets'] ?? null) ? array_values(array_map('strval', $data['targets'])) : [];

        if ($targets === []) {
            $targets = [BusinessModuleScaffoldTarget::Module->value];
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            type: (string) ($data['type'] ?? BusinessModuleType::Business->value),
            targets: $targets,
            force: (bool) ($data['force'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'name' => $this->name,
            'type' => $this->type,
            'targets' => $this->targets,
            'force' => $this->force,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

<?php

namespace App\Modules\Sdk\Development\Data;

readonly class BusinessModuleScaffoldResult implements \JsonSerializable
{
    /**
     * @param  list<string>  $createdFiles
     * @param  list<string>  $skippedFiles
     */
    public function __construct(
        public string $moduleKey,
        public string $modulePath,
        public array $createdFiles = [],
        public array $skippedFiles = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            modulePath: (string) ($data['module_path'] ?? ''),
            createdFiles: is_array($data['created_files'] ?? null) ? array_values(array_map('strval', $data['created_files'])) : [],
            skippedFiles: is_array($data['skipped_files'] ?? null) ? array_values(array_map('strval', $data['skipped_files'])) : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'module_path' => $this->modulePath,
            'created_files' => $this->createdFiles,
            'skipped_files' => $this->skippedFiles,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

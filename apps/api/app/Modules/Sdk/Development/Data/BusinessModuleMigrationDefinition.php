<?php

namespace App\Modules\Sdk\Development\Data;

readonly class BusinessModuleMigrationDefinition implements \JsonSerializable
{
    public function __construct(
        public string $file,
        public string $class,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            file: (string) ($data['file'] ?? ''),
            class: (string) ($data['class'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'class' => $this->class,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

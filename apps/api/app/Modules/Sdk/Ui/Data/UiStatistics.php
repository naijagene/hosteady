<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiStatistics implements \JsonSerializable
{
    public function __construct(
        public int $pages,
        public int $layouts,
        public int $components,
        public int $personalizations,
        public int $registeredModules
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            pages: (int) ($data['pages'] ?? $data['pages'] ?? 0),
            layouts: (int) ($data['layouts'] ?? $data['layouts'] ?? 0),
            components: (int) ($data['components'] ?? $data['components'] ?? 0),
            personalizations: (int) ($data['personalizations'] ?? $data['personalizations'] ?? 0),
            registeredModules: (int) ($data['registered_modules'] ?? $data['registeredModules'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'pages' => $this->pages,
            'layouts' => $this->layouts,
            'components' => $this->components,
            'personalizations' => $this->personalizations,
            'registered_modules' => $this->registeredModules,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

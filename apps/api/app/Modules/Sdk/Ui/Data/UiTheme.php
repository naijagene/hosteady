<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiTheme implements \JsonSerializable
{
    public function __construct(
        public string $themeKey,
        public string $name,
        public array $tokens,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            themeKey: (string) ($data['theme_key'] ?? $data['themeKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            tokens: is_array($data['tokens'] ?? $data['tokens'] ?? null) ? ($data['tokens'] ?? $data['tokens']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'theme_key' => $this->themeKey,
            'name' => $this->name,
            'tokens' => $this->tokens,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

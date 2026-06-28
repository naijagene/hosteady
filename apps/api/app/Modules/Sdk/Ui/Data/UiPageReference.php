<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiPageReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public ?string $moduleKey,
        public string $pageKey,
        public string $name,
        public string $pageType,
        public string $status,
        public ?string $routePath
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            pageKey: (string) ($data['page_key'] ?? $data['pageKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            pageType: (string) ($data['page_type'] ?? $data['pageType'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            routePath: isset($data['route_path']) ? (string) $data['route_path'] : (isset($data['routePath']) ? (string) $data['routePath'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'page_key' => $this->pageKey,
            'name' => $this->name,
            'page_type' => $this->pageType,
            'status' => $this->status,
            'route_path' => $this->routePath,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

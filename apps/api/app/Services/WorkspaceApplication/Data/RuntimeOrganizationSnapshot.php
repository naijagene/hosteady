<?php

namespace App\Services\WorkspaceApplication\Data;

readonly class RuntimeOrganizationSnapshot
{
    public function __construct(
        public string $publicId,
        public string $name,
        public string $slug,
        public string $status,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
        ];
    }
}

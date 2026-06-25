<?php

namespace App\Services\WorkspaceApplication\Data;

readonly class RuntimeMembershipSnapshot
{
    public function __construct(
        public string $publicId,
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
            'status' => $this->status,
        ];
    }
}

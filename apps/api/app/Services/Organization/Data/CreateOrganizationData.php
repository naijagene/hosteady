<?php

namespace App\Services\Organization\Data;

readonly class CreateOrganizationData
{
    public function __construct(
        public int $creatorUserId,
        public string $name,
        public string $slug,
        public string $timezone = 'UTC',
        public string $locale = 'en',
        public string $planTier = 'free',
        public ?string $organizationCode = null,
    ) {
    }
}

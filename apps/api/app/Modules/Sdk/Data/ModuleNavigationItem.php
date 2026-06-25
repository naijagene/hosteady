<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleNavigationItem
{
    public function __construct(
        public string $publicId,
        public string $moduleKey,
        public string $label,
        public ?string $icon,
        public string $routeName,
        public ?string $requiredPermission,
        public int $sortOrder,
        public ?string $parentPublicId = null,
    ) {
    }
}

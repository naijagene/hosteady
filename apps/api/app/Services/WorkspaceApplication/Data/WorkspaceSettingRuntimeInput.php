<?php

namespace App\Services\WorkspaceApplication\Data;

readonly class WorkspaceSettingRuntimeInput
{
    public function __construct(
        public string $settingKey,
        public int $version,
        public string $type,
        public mixed $value,
        public bool $isSensitive,
    ) {
    }
}

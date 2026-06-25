<?php

namespace App\Services\WorkspaceApplication;

class WorkspaceSettingMasker
{
    public const MASK = '***';

    public function maskValue(mixed $value, bool $isSensitive): mixed
    {
        if (! $isSensitive) {
            return $value;
        }

        return self::MASK;
    }

    public function isRedacted(bool $isSensitive): bool
    {
        return $isSensitive;
    }
}

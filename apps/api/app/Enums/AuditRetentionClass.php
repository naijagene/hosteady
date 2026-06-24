<?php

namespace App\Enums;

enum AuditRetentionClass: string
{
    case Permanent = 'permanent';
    case Standard = 'standard';
    case Ephemeral = 'ephemeral';

    public function retentionDays(): ?int
    {
        return match ($this) {
            self::Permanent => null,
            self::Standard => 1825,
            self::Ephemeral => 180,
        };
    }
}

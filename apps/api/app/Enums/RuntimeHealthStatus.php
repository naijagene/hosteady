<?php

namespace App\Enums;

enum RuntimeHealthStatus: string
{
    case Healthy = 'healthy';
    case Warning = 'warning';
    case Critical = 'critical';

    public static function worst(RuntimeHealthStatus ...$statuses): self
    {
        foreach ($statuses as $status) {
            if ($status === self::Critical) {
                return self::Critical;
            }
        }

        foreach ($statuses as $status) {
            if ($status === self::Warning) {
                return self::Warning;
            }
        }

        return self::Healthy;
    }
}

<?php

namespace App\Services\Theme;

class ThemeDefaultGeneratorService
{
    /**
     * @return array<string, mixed>
     */
    public static function safeDefaultTokens(): array
    {
        return [
            'color.primary' => '#2563eb',
            'color.surface' => '#ffffff',
            'color.text' => '#111827',
            'spacing.unit' => '0.25rem',
            'radius.base' => '0.375rem',
        ];
    }
}

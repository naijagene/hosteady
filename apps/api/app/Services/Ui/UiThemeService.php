<?php

namespace App\Services\Ui;

use App\Modules\Sdk\Ui\Contracts\UiThemeProvider;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Modules\Sdk\Ui\Data\UiTheme;

class UiThemeService implements UiThemeProvider
{
    public function themeForPage(UiPageDefinition $page): UiTheme
    {
        if ($page->theme !== []) {
            return UiTheme::fromArray(array_merge([
                'theme_key' => 'page.'.$page->pageKey,
                'name' => $page->name,
            ], $page->theme));
        }

        return new UiTheme(
            themeKey: 'default',
            name: 'Default',
            tokens: [
                'color.primary' => '#2563eb',
                'color.surface' => '#ffffff',
                'color.text' => '#111827',
                'spacing.unit' => '0.25rem',
            ],
            metadata: [],
        );
    }
}

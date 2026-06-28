<?php

namespace App\Modules\Sdk\Ui\Contracts;

interface UiThemeProvider
{
    public function themeForPage(\App\Modules\Sdk\Ui\Data\UiPageDefinition $page): \App\Modules\Sdk\Ui\Data\UiTheme;
}

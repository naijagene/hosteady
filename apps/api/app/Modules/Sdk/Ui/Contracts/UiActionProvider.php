<?php

namespace App\Modules\Sdk\Ui\Contracts;

interface UiActionProvider
{
    /** @return list<array<string, mixed>> */
    public function pageActions(\App\Modules\Sdk\Ui\Data\UiPageDefinition $page): array;

    /** @return list<array<string, mixed>> */
    public function componentActions(\App\Modules\Sdk\Ui\Data\UiComponent $component): array;
}

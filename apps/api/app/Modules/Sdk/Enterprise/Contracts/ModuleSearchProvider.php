<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

interface ModuleSearchProvider
{
    public function moduleKey(): string;

    /**
     * @return list<string>
     */
    public function searchableEntityTypes(): array;
}

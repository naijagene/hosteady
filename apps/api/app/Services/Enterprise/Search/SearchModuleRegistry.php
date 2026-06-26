<?php

namespace App\Services\Enterprise\Search;

class SearchModuleRegistry
{
    /**
     * @var array<string, list<string>>
     */
    private array $modules = [];

    /**
     * @param  list<string>  $entityTypes
     */
    public function register(string $moduleKey, array $entityTypes): void
    {
        $this->modules[$moduleKey] = array_values(array_unique($entityTypes));
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * @return list<string>
     */
    public function moduleKeys(): array
    {
        return array_keys($this->modules);
    }
}

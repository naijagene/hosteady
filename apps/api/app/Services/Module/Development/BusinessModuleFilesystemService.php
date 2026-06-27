<?php

namespace App\Services\Module\Development;

use Illuminate\Support\Str;

class BusinessModuleFilesystemService
{
    public function modulePathForKey(string $moduleKey): string
    {
        return app_path('Modules/'.Str::studly(str_replace(['.', '-', '_'], ' ', $moduleKey)));
    }

    public function moduleDirectoryExists(string $moduleKey): bool
    {
        return is_dir($this->modulePathForKey($moduleKey));
    }

    /**
     * @return list<string>
     */
    public function ensureStructure(string $modulePath): array
    {
        $directories = [
            'Config',
            'Domain/Models',
            'Domain/Data',
            'Domain/Enums',
            'Domain/Exceptions',
            'Services',
            'Http/Controllers',
            'Http/Resources',
            'Http/Requests',
            'Policies',
            'Database/Migrations',
            'Database/Seeders',
            'Tests',
            'Routes',
            'Providers',
        ];

        $created = [];

        foreach ($directories as $directory) {
            $path = $modulePath.'/'.$directory;

            if (! is_dir($path)) {
                mkdir($path, 0777, true);
                $created[] = $path;
            }
        }

        return $created;
    }
}

<?php

namespace App\Modules\Sdk\Development\Support;

use Illuminate\Support\Str;

class BusinessModuleConventionResolver
{
    /**
     * @return array{
     *     module_key: string,
     *     studly_name: string,
     *     namespace: string,
     *     base_path: string,
     *     manifest_path: string,
     *     routes_path: string,
     *     migration_path: string,
     *     seeder_path: string,
     *     test_path: string,
     *     provider_class: string,
     *     service_class: string,
     *     module_class: string
     * }
     */
    public function resolveFromKey(string $moduleKey): array
    {
        $normalizedKey = Str::kebab($moduleKey);
        $studlyName = $this->studlyNameFromKey($normalizedKey);
        $namespace = 'App\\Modules\\'.$studlyName;
        $basePath = app_path('Modules/'.$studlyName);

        return [
            'module_key' => $normalizedKey,
            'studly_name' => $studlyName,
            'namespace' => $namespace,
            'base_path' => $basePath,
            'manifest_path' => $basePath.'/Config/manifest.php',
            'routes_path' => $basePath.'/Routes/api.php',
            'migration_path' => $basePath.'/Database/Migrations',
            'seeder_path' => $basePath.'/Database/Seeders',
            'test_path' => base_path('tests/Feature/Modules/'.$studlyName),
            'provider_class' => $namespace.'\\Providers\\'.$studlyName.'ServiceProvider',
            'service_class' => $namespace.'\\Services\\'.$studlyName.'Service',
            'module_class' => $namespace.'\\'.$studlyName.'Module',
        ];
    }

    /**
     * @return array{
     *     module_key: string,
     *     studly_name: string,
     *     namespace: string,
     *     base_path: string,
     *     manifest_path: string,
     *     routes_path: string,
     *     migration_path: string,
     *     seeder_path: string,
     *     test_path: string,
     *     provider_class: string,
     *     service_class: string,
     *     module_class: string
     * }
     */
    public function resolveFromClass(string $class): array
    {
        $studlyName = class_basename($class);
        $studlyName = Str::endsWith($studlyName, 'Module')
            ? substr($studlyName, 0, -6)
            : $studlyName;

        $moduleKey = $this->moduleKeyFromStudlyName($studlyName);
        $conventions = $this->resolveFromKey($moduleKey);
        $conventions['studly_name'] = $studlyName;
        $conventions['namespace'] = 'App\\Modules\\'.$studlyName;
        $conventions['base_path'] = app_path('Modules/'.$studlyName);
        $conventions['manifest_path'] = $conventions['base_path'].'/Config/manifest.php';
        $conventions['routes_path'] = $conventions['base_path'].'/Routes/api.php';
        $conventions['migration_path'] = $conventions['base_path'].'/Database/Migrations';
        $conventions['seeder_path'] = $conventions['base_path'].'/Database/Seeders';
        $conventions['test_path'] = base_path('tests/Feature/Modules/'.$studlyName);
        $conventions['provider_class'] = $conventions['namespace'].'\\Providers\\'.$studlyName.'ServiceProvider';
        $conventions['service_class'] = $conventions['namespace'].'\\Services\\'.$studlyName.'Service';
        $conventions['module_class'] = $conventions['namespace'].'\\'.$studlyName.'Module';

        return $conventions;
    }

    public function studlyNameFromKey(string $moduleKey): string
    {
        return Str::studly(str_replace(['.', '-', '_'], ' ', Str::kebab($moduleKey)));
    }

    public function moduleKeyFromStudlyName(string $studlyName): string
    {
        return Str::kebab(preg_replace('/(?<=\\w)(?=[A-Z])/', '-', $studlyName) ?? $studlyName);
    }
}

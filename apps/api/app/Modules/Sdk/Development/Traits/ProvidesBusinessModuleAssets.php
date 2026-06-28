<?php

namespace App\Modules\Sdk\Development\Traits;

use App\Modules\Sdk\Development\Data\BusinessModuleMigrationDefinition;
use App\Modules\Sdk\Development\Data\BusinessModuleSeederDefinition;
use App\Modules\Sdk\Development\Support\BusinessModuleConventionResolver;

trait ProvidesBusinessModuleAssets
{
    /**
     * @return list<BusinessModuleMigrationDefinition>
     */
    public function migrations(): array
    {
        $manifestMigrations = $this->manifestMigrationsFromFile();

        if ($manifestMigrations !== []) {
            return $manifestMigrations;
        }

        return $this->discoverMigrationDefinitions();
    }

    /**
     * @return list<BusinessModuleSeederDefinition>
     */
    public function seeders(): array
    {
        $manifestSeeders = $this->manifestSeedersFromFile();

        if ($manifestSeeders !== []) {
            return $manifestSeeders;
        }

        return $this->discoverSeederDefinitions();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function entities(): array
    {
        return $this->manifest()->entities;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forms(): array
    {
        return $this->manifest()->forms;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tables(): array
    {
        return $this->manifest()->tables;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dashboards(): array
    {
        return $this->manifest()->dashboards;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function reports(): array
    {
        return $this->manifest()->reports;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function workflows(): array
    {
        return $this->manifest()->workflows;
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return $this->manifest()->settings;
    }

    /**
     * @return list<BusinessModuleMigrationDefinition>
     */
    private function manifestMigrationsFromFile(): array
    {
        $raw = $this->loadManifestData()['migrations'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_map(
            fn (array $migration) => BusinessModuleMigrationDefinition::fromArray($migration),
            array_filter($raw, is_array(...)),
        ));
    }

    /**
     * @return list<BusinessModuleSeederDefinition>
     */
    private function manifestSeedersFromFile(): array
    {
        $raw = $this->loadManifestData()['seeders'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_map(
            fn (array $seeder) => BusinessModuleSeederDefinition::fromArray($seeder),
            array_filter($raw, is_array(...)),
        ));
    }

    /**
     * @return list<BusinessModuleMigrationDefinition>
     */
    private function discoverMigrationDefinitions(): array
    {
        $conventions = app(BusinessModuleConventionResolver::class)->resolveFromClass(static::class);
        $migrationPath = $conventions['migration_path'];

        if (! is_dir($migrationPath)) {
            return [];
        }

        $definitions = [];

        foreach (glob($migrationPath.'/*.php') ?: [] as $file) {
            $definitions[] = new BusinessModuleMigrationDefinition(
                file: basename($file),
                class: pathinfo($file, PATHINFO_FILENAME),
            );
        }

        return $definitions;
    }

    /**
     * @return list<BusinessModuleSeederDefinition>
     */
    private function discoverSeederDefinitions(): array
    {
        $conventions = app(BusinessModuleConventionResolver::class)->resolveFromClass(static::class);
        $seederPath = $conventions['seeder_path'];

        if (! is_dir($seederPath)) {
            return [];
        }

        $definitions = [];

        foreach (glob($seederPath.'/*.php') ?: [] as $file) {
            $definitions[] = new BusinessModuleSeederDefinition(
                file: basename($file),
                class: pathinfo($file, PATHINFO_FILENAME),
            );
        }

        return $definitions;
    }
}

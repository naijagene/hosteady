<?php

namespace App\Modules\Sdk\Dashboard\Contracts;

use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;

interface DashboardRegistry
{
    public function register(mixed $source): DashboardDefinition;

    public function update(DashboardDefinition $definition): DashboardDefinition;

    public function find(string $moduleKey, string $dashboardKey): ?DashboardDefinition;

    public function findByPublicId(string $publicId): ?DashboardDefinition;

    /**
     * @return list<DashboardDefinition>
     */
    public function list(?string $moduleKey = null): array;

    /**
     * @return list<DashboardDefinition>
     */
    public function findByEntity(string $moduleKey, string $entityKey): array;

    /**
     * @param  list<array<string, mixed>>  $dashboards
     * @return list<DashboardDefinition>
     */
    public function registerFromManifestDashboards(array $dashboards, string $moduleKey): array;
}

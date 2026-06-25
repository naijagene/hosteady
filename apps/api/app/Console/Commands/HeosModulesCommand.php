<?php

namespace App\Console\Commands;

use App\Services\Module\ModuleInspectionService;
use Illuminate\Console\Command;

class HeosModulesCommand extends Command
{
    protected $signature = 'heos:modules
        {--json : Output module inspection data as JSON}
        {--health : Include health status in output}
        {--details : Include full module details}';

    protected $description = 'Inspect registered HEOS modules';

    public function handle(ModuleInspectionService $inspectionService): int
    {
        $summary = $inspectionService->summary();

        if ($this->option('json')) {
            $payload = $this->option('details')
                ? $summary->toArray()
                : [
                    'module_count' => $summary->moduleCount,
                    'modules' => array_map(
                        fn ($module) => $this->compactModulePayload($module->toArray()),
                        $summary->modules,
                    ),
                ];

            if ($this->option('health')) {
                $payload['statistics'] = $inspectionService->statistics();
            }

            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $headers = ['Key', 'UUID', 'Version', 'Manifest', 'Dependencies'];

        if ($this->option('health')) {
            $headers[] = 'Health';
        }

        if ($this->option('details')) {
            $headers = array_merge($headers, ['Lifecycle', 'Runtime', 'Sync']);
        }

        $rows = [];

        foreach ($summary->modules as $module) {
            $row = [
                $module->moduleKey,
                $module->moduleUuid,
                $module->version,
                (string) $module->manifestVersion,
                implode(', ', $module->dependencies) ?: '-',
            ];

            if ($this->option('health')) {
                $row[] = $module->healthStatus;
            }

            if ($this->option('details')) {
                $row[] = $module->lifecycleSupported ? 'yes' : 'no';
                $row[] = $module->runtimeContributor ? 'yes' : 'no';
                $row[] = $module->syncSupported ? 'yes' : 'no';
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);

        if ($this->option('details')) {
            $this->newLine();
            $this->components->info('Statistics');
            $statistics = $inspectionService->statistics();

            foreach ([
                'module_count' => 'Modules',
                'healthy_count' => 'Healthy',
                'runtime_contributor_count' => 'Runtime contributors',
                'permission_count' => 'Permissions',
                'setting_count' => 'Settings',
            ] as $key => $label) {
                $this->line(sprintf('%s: %d', $label, $statistics[$key]));
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $module
     * @return array<string, mixed>
     */
    private function compactModulePayload(array $module): array
    {
        return [
            'module_key' => $module['module_key'],
            'module_uuid' => $module['module_uuid'],
            'version' => $module['version'],
            'manifest_version' => $module['manifest_version'],
            'dependencies' => $module['dependencies'],
            'health_status' => $module['health_status'],
            'runtime_contributor' => $module['runtime_contributor'],
            'lifecycle_supported' => $module['lifecycle_supported'],
            'sync_supported' => $module['sync_supported'],
        ];
    }
}

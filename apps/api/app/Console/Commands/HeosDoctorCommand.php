<?php

namespace App\Console\Commands;

use App\Services\Module\ModuleDoctorService;
use App\Services\Module\ModuleDocumentationService;
use Illuminate\Console\Command;

class HeosDoctorCommand extends Command
{
    protected $signature = 'heos:doctor
        {--json : Output the doctor report as JSON}
        {--generate-docs : Generate module markdown documentation}';

    protected $description = 'Run HEOS platform and module diagnostics';

    public function handle(
        ModuleDoctorService $doctorService,
        ModuleDocumentationService $documentationService,
    ): int {
        if ($this->option('generate-docs')) {
            $documentation = $documentationService->generate();
            $this->components->info(sprintf(
                'Generated documentation for %d module(s) in %s',
                $documentation->moduleCount,
                $documentation->outputDirectory,
            ));
        }

        $report = $doctorService->diagnose();

        if ($this->option('json')) {
            $this->line(json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $report->exitCode;
        }

        $this->components->info('HEOS Platform Doctor');
        $this->line(sprintf(
            'Modules: %d | Overall: %s | Exit code: %d',
            $report->platformSummary['module_count'],
            $report->platformSummary['overall_status'],
            $report->exitCode,
        ));

        $this->newLine();
        $this->components->info('Registered Modules');

        foreach ($report->modules as $module) {
            $this->line(sprintf(
                '- %s (%s) v%s [%s]',
                $module['module_key'],
                $module['name'],
                $module['version'],
                $module['health_status'],
            ));
        }

        $this->newLine();
        $this->components->info('Manifest Validation');

        if ($report->validation->isValid()) {
            $this->components->info('All module manifests are valid.');
        } else {
            foreach ($report->validation->issues as $issue) {
                $this->components->error(sprintf('[%s] %s', $issue->code, $issue->message));
            }
        }

        $this->newLine();
        $this->components->info('Dependency Graph');
        $this->line('Topological order: '.implode(', ', $report->dependencyGraph['nodes'] ?? []));

        if (($report->dependencyGraph['cycles'] ?? []) !== []) {
            $this->components->warn('Cycles detected: '.implode(', ', $report->dependencyGraph['cycles']));
        }

        $this->newLine();
        $this->components->info('Lifecycle Support');

        foreach ($report->lifecycleSupport as $moduleKey => $supported) {
            $this->line(sprintf('- %s: %s', $moduleKey, $supported ? 'yes' : 'no'));
        }

        $this->newLine();
        $this->components->info('Runtime Contribution Support');

        foreach ($report->runtimeContributionSupport as $moduleKey => $supported) {
            $this->line(sprintf('- %s: %s', $moduleKey, $supported ? 'yes' : 'no'));
        }

        $this->newLine();
        $this->components->info('Sync Support');
        $this->line(sprintf(
            'Synced: %d | Missing: %d',
            $report->syncSupport['synced'],
            $report->syncSupport['missing'],
        ));

        $this->newLine();
        $this->components->info('Health');
        $this->line('Overall status: '.$report->health->overallStatus);

        foreach ($report->warnings as $warning) {
            $this->components->warn($warning);
        }

        foreach ($report->errors as $error) {
            $this->components->error($error);
        }

        foreach ($report->recommendations as $recommendation) {
            $this->line('- '.$recommendation);
        }

        return $report->exitCode;
    }
}

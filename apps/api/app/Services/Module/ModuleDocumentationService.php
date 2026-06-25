<?php

namespace App\Services\Module;

use App\Modules\Sdk\AbstractApplicationModule;
use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Contracts\ModuleRuntimeContext;
use App\Modules\Sdk\ModuleRegistry;
use App\Services\Module\Data\ModuleDocumentationResult;
use App\Services\Module\Data\PlatformModuleHealthContext;

class ModuleDocumentationService
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleDeveloperAuditRecorder $auditRecorder,
    ) {
    }

    public function generate(?string $outputDirectory = null): ModuleDocumentationResult
    {
        $directory = $outputDirectory ?? base_path('docs/modules');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $generatedFiles = [];
        $modules = $this->sortedModules();

        foreach ($modules as $module) {
            $path = $directory.DIRECTORY_SEPARATOR.$module->key().'.md';
            file_put_contents($path, $this->renderModuleMarkdown($module));
            $generatedFiles[] = $path;
        }

        $indexPath = $directory.DIRECTORY_SEPARATOR.'index.md';
        file_put_contents($indexPath, $this->renderIndexMarkdown($modules));
        $generatedFiles[] = $indexPath;

        sort($generatedFiles);

        $result = new ModuleDocumentationResult(
            outputDirectory: $directory,
            generatedFiles: $generatedFiles,
            moduleCount: count($modules),
        );

        $this->auditRecorder->recordDocumentationGenerated($result);

        return $result;
    }

    /**
     * @return list<ApplicationModule>
     */
    private function sortedModules(): array
    {
        $modules = $this->registry->all();
        usort($modules, fn (ApplicationModule $left, ApplicationModule $right) => $left->key() <=> $right->key());

        return $modules;
    }

    private function renderModuleMarkdown(ApplicationModule $module): string
    {
        $manifest = $module->manifest();
        $health = $module->health(new PlatformModuleHealthContext);
        $contribution = $module->contributeRuntime(new PlatformModuleRuntimeContext);

        $lines = [
            '# '.$manifest->name,
            '',
            $manifest->description ?? 'No description provided.',
            '',
            '## Overview',
            '',
            '| Field | Value |',
            '| --- | --- |',
            '| Module key | `'.$manifest->key.'` |',
            '| UUID | `'.$manifest->moduleUuid.'` |',
            '| Version | '.$manifest->version.' |',
            '| Manifest version | '.$manifest->manifestVersion.' |',
            '| Category | '.($manifest->category ?? 'n/a').' |',
            '| Core | '.($manifest->isCore ? 'yes' : 'no').' |',
            '| Bootstrap | '.($manifest->bootstrap ? 'yes' : 'no').' |',
            '',
            '## Capabilities',
            '',
        ];

        $lines = array_merge($lines, $this->bulletList($manifest->capabilities));
        $lines[] = '';
        $lines[] = '## Dependencies';
        $lines[] = '';
        $lines = array_merge(
            $lines,
            $this->bulletList(array_map(fn ($dependency) => $dependency->key, $manifest->dependencies)),
        );
        $lines[] = '';
        $lines[] = '## Permissions';
        $lines[] = '';
        $lines = array_merge(
            $lines,
            $this->bulletList(array_map(fn ($permission) => $permission->key, $manifest->permissions)),
        );
        $lines[] = '';
        $lines[] = '## Settings';
        $lines[] = '';

        foreach ($manifest->settings as $setting) {
            $lines[] = '- `'.$setting->settingKey.'` ('.$setting->settingType.') — '.$setting->label;
        }

        if ($manifest->settings === []) {
            $lines[] = '- None';
        }

        $lines[] = '';
        $lines[] = '## Navigation';
        $lines[] = '';

        foreach ($manifest->navigation as $item) {
            $lines[] = '- '.$item->label.' (`'.$item->publicId.'`) → '.$item->routeName;
        }

        if ($manifest->navigation === []) {
            $lines[] = '- None';
        }

        $lines[] = '';
        $lines[] = '## Feature Flags';
        $lines[] = '';
        $lines = array_merge($lines, $this->featureFlagLines($contribution->featureFlags));
        $lines[] = '';
        $lines[] = '## Runtime Extensions';
        $lines[] = '';
        $lines[] = '- Runtime contributor: '.($this->supportsRuntimeContribution($module) ? 'yes' : 'no');
        $lines[] = '- Contribution capabilities: '.implode(', ', $contribution->capabilities ?: ['none']);
        $lines[] = '';
        $lines[] = '## Health';
        $lines[] = '';
        $lines[] = '- Status: '.$health->status;

        if ($health->message !== null) {
            $lines[] = '- Message: '.$health->message;
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param  list<ApplicationModule>  $modules
     */
    private function renderIndexMarkdown(array $modules): string
    {
        $lines = [
            '# HEOS Module Documentation',
            '',
            'Generated module reference for the HEOS platform.',
            '',
            '| Module | Version | UUID | Health |',
            '| --- | --- | --- | --- |',
        ];

        foreach ($modules as $module) {
            $manifest = $module->manifest();
            $health = $module->health(new PlatformModuleHealthContext);
            $lines[] = sprintf(
                '| [%s](./%s.md) | %s | `%s` | %s |',
                $manifest->name,
                $manifest->key,
                $manifest->version,
                $manifest->moduleUuid,
                $health->status,
            );
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $items
     * @return list<string>
     */
    private function bulletList(array $items): array
    {
        if ($items === []) {
            return ['- None'];
        }

        return array_map(fn (string $item) => '- '.$item, $items);
    }

    /**
     * @param  array<string, mixed>  $featureFlags
     * @return list<string>
     */
    private function featureFlagLines(array $featureFlags): array
    {
        if ($featureFlags === []) {
            return ['- None'];
        }

        ksort($featureFlags);

        $lines = [];

        foreach ($featureFlags as $key => $value) {
            $lines[] = '- `'.$key.'`: '.json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        return $lines;
    }

    private function supportsRuntimeContribution(ApplicationModule $module): bool
    {
        $method = new \ReflectionMethod($module, 'contributeRuntime');

        return $method->getDeclaringClass()->getName() !== AbstractApplicationModule::class;
    }
}

class PlatformModuleRuntimeContext implements ModuleRuntimeContext
{
    public function organizationPublicId(): string
    {
        return 'platform';
    }

    public function workspacePublicId(): ?string
    {
        return null;
    }
}

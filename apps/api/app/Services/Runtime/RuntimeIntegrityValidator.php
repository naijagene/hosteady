<?php

namespace App\Services\Runtime;

use App\Enums\OrganizationApplicationStatus;
use App\Enums\RuntimeHealthStatus;
use App\Enums\SettingDefinitionStatus;
use App\Enums\WorkspaceApplicationStatus;
use App\Models\ApplicationSettingDefinition;
use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationSetting;
use App\Services\Application\ApplicationSettingsRegistry;
use App\Services\Runtime\Data\RuntimeIntegrityReport;
use App\Services\Module\RuntimeExtensionService;
use App\Services\WorkspaceApplication\Data\ResolvedWorkspaceApplication;
use App\Services\WorkspaceApplication\WorkspaceRuntimeResolver;
use App\Services\WorkspaceApplication\WorkspaceRuntimeVersionCalculator;
use App\Support\Tenant\TenantContext;

class RuntimeIntegrityValidator
{
    public function __construct(
        private readonly WorkspaceRuntimeResolver $runtimeResolver,
        private readonly WorkspaceRuntimeVersionCalculator $versionCalculator,
        private readonly ApplicationSettingsRegistry $settingsRegistry,
        private readonly RuntimeExtensionService $runtimeExtensionService,
    ) {
    }

    public function validate(TenantContext $context): RuntimeIntegrityReport
    {
        $errors = [];
        $warnings = [];

        $manifest = $this->runtimeResolver->buildManifest($context);
        $activeModuleKeys = array_map(
            fn (ResolvedWorkspaceApplication $application) => $application->key,
            $manifest->applications,
        );
        $extensionReport = $this->runtimeExtensionService->resolveForTenant($context, $activeModuleKeys);
        $calculatedVersion = $this->versionCalculator->calculate(
            $manifest,
            $extensionReport->contributions->fingerprint(),
        );
        $summary = $this->runtimeResolver->resolveSummary($context);
        $fingerprintValid = hash_equals($calculatedVersion, $summary->runtimeVersion);

        if (! $fingerprintValid) {
            $errors[] = 'Runtime fingerprint does not match manifest fingerprint.';
        }

        $seenKeys = [];

        foreach ($manifest->applications as $application) {
            if (isset($seenKeys[$application->key])) {
                $errors[] = sprintf('Duplicate runtime application key [%s].', $application->key);
            }

            $seenKeys[$application->key] = true;
        }

        $workspaceApplications = WorkspaceApplication::query()
            ->with(['application', 'organizationApplication'])
            ->where('workspace_id', $context->workspace->id)
            ->where('organization_id', $context->organization->id)
            ->whereIn('public_id', array_keys($manifest->applicationsByPublicId))
            ->whereNull('deleted_at')
            ->get();

        foreach ($workspaceApplications as $workspaceApplication) {
            if ($workspaceApplication->organizationApplication === null) {
                $errors[] = sprintf(
                    'Workspace application [%s] is missing organization installation.',
                    $workspaceApplication->application->key,
                );

                continue;
            }

            if ($workspaceApplication->organizationApplication->status !== OrganizationApplicationStatus::Active) {
                $errors[] = sprintf(
                    'Workspace application [%s] references inactive organization installation.',
                    $workspaceApplication->application->key,
                );
            }

            if ($workspaceApplication->application === null) {
                $errors[] = sprintf(
                    'Workspace application [%s] references missing catalog application.',
                    $workspaceApplication->public_id,
                );
            }

            if ($workspaceApplication->status === WorkspaceApplicationStatus::Archived) {
                $errors[] = sprintf('Archived workspace application [%s] present in runtime.', $workspaceApplication->application->key);
            }
        }

        foreach ($manifest->applications as $application) {
            $workspaceApplication = $workspaceApplications->firstWhere('public_id', $application->workspaceApplicationPublicId);

            if ($workspaceApplication === null) {
                continue;
            }

            if ($this->settingsRegistry->hasWorkspaceDefinitions($workspaceApplication->application_id)) {
                $definitions = $this->settingsRegistry->workspaceDefinitionsForApplication($workspaceApplication->application_id);
                $definitionKeys = $definitions->pluck('settingKey')->all();

                $settings = WorkspaceApplicationSetting::query()
                    ->where('workspace_application_id', $workspaceApplication->id)
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($settings as $setting) {
                    if (! in_array($setting->setting_key, $definitionKeys, true)) {
                        $warnings[] = sprintf(
                            'Orphan workspace setting [%s] on application [%s].',
                            $setting->setting_key,
                            $application->key,
                        );
                    }

                    $definition = $definitions->firstWhere('settingKey', $setting->setting_key);

                    if ($definition !== null && $definition->settingType->value !== $setting->setting_type->value) {
                        $errors[] = sprintf(
                            'Workspace setting [%s] type mismatch on application [%s].',
                            $setting->setting_key,
                            $application->key,
                        );
                    }
                }

                $activeDefinitionIds = ApplicationSettingDefinition::query()
                    ->where('application_id', $workspaceApplication->application_id)
                    ->where('status', SettingDefinitionStatus::Active)
                    ->whereNull('deleted_at')
                    ->pluck('setting_key')
                    ->all();

                foreach ($definitionKeys as $definitionKey) {
                    if (! in_array($definitionKey, $activeDefinitionIds, true)) {
                        $warnings[] = sprintf(
                            'Orphan definition [%s] on application [%s].',
                            $definitionKey,
                            $application->key,
                        );
                    }
                }
            }
        }

        foreach ($manifest->applications as $application) {
            foreach ($application->dependencies as $dependencyKey) {
                if (! isset($seenKeys[$dependencyKey]) && ! in_array($dependencyKey, ['core', 'workspace'], true)) {
                    $warnings[] = sprintf(
                        'Application [%s] declares dependency [%s] that is not active in runtime.',
                        $application->key,
                        $dependencyKey,
                    );
                }
            }
        }

        $status = RuntimeHealthStatus::Healthy;

        if ($errors !== []) {
            $status = RuntimeHealthStatus::Critical;
        } elseif ($warnings !== []) {
            $status = RuntimeHealthStatus::Warning;
        }

        return new RuntimeIntegrityReport(
            status: $status,
            fingerprintValid: $fingerprintValid,
            errors: $errors,
            warnings: $warnings,
        );
    }
}

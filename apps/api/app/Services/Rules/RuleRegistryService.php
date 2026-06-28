<?php

namespace App\Services\Rules;

use App\Models\RuleDefinition as RuleDefinitionModel;
use App\Modules\Sdk\Rules\Contracts\RuleRegistry;
use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Enums\RuleStatus;

class RuleRegistryService implements RuleRegistry
{
    public function listEnabled(string $organizationId, ?string $workspaceId, string $triggerType, ?string $moduleKey = null, ?string $entityKey = null): array
    {
        $query = RuleDefinitionModel::query()
            ->where('organization_id', $organizationId)
            ->where('status', RuleStatus::Enabled)
            ->where('trigger_type', $triggerType)
            ->orderBy('priority');

        RuleMapper::applyWorkspaceScope($query, $workspaceId);

        if ($moduleKey !== null) {
            $query->where(function ($scoped) use ($moduleKey) {
                $scoped->whereNull('module_key')->orWhere('module_key', $moduleKey);
            });
        }

        if ($entityKey !== null) {
            $query->where(function ($scoped) use ($entityKey) {
                $scoped->whereNull('entity_key')->orWhere('entity_key', $entityKey);
            });
        }

        return $query->get()->map(fn (RuleDefinitionModel $model) => RuleMapper::toRuleDefinition($model))->all();
    }

    public function find(string $organizationId, ?string $workspaceId, string $publicId): ?RuleDefinition
    {
        return app(RuleDefinitionService::class)->find($organizationId, $workspaceId, $publicId);
    }
}

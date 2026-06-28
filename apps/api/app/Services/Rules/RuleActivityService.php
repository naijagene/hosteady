<?php

namespace App\Services\Rules;

use App\Models\RuleActivityLog;
use App\Models\RuleDefinition as RuleDefinitionModel;
use App\Models\RuleSet;
use Illuminate\Support\Str;

class RuleActivityService
{
    public function logRuleSet(RuleSet $model, string $action, ?array $before = null, ?array $after = null): void
    {
        $this->create([
            'organization_id' => $model->organization_id,
            'workspace_id' => $model->workspace_id,
            'rule_set_id' => $model->id,
            'rule_public_id' => $model->public_id,
            'action' => 'rule_set.'.$action,
            'before_state' => $before,
            'after_state' => $after,
        ]);
    }

    public function logRuleDefinition(RuleDefinitionModel $model, string $action, ?array $before = null, ?array $after = null): void
    {
        $this->create([
            'organization_id' => $model->organization_id,
            'workspace_id' => $model->workspace_id,
            'rule_set_id' => $model->rule_set_id,
            'rule_definition_id' => $model->id,
            'rule_public_id' => $model->public_id,
            'action' => 'rule_definition.'.$action,
            'before_state' => $before,
            'after_state' => $after,
        ]);
    }

    private function create(array $attributes): void
    {
        RuleActivityLog::query()->create(array_merge([
            'id' => (string) Str::uuid7(),
            'created_at' => now(),
        ], $attributes));
    }
}

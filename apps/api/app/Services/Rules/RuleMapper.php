<?php

namespace App\Services\Rules;

use App\Models\RuleDefinition as RuleDefinitionModel;
use App\Models\RuleEvaluation as RuleEvaluationModel;
use App\Models\RuleExecution as RuleExecutionModel;
use App\Models\RuleSet as RuleSetModel;
use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Data\RuleEvaluationResult;
use App\Modules\Sdk\Rules\Data\RuleExecutionResult;
use App\Modules\Sdk\Rules\Data\RuleSetDefinition;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;

class RuleMapper
{
    public static function toRuleSet(RuleSetModel $model): RuleSetDefinition
    {
        return new RuleSetDefinition(
            publicId: $model->public_id,
            name: $model->name,
            description: $model->description,
            scope: self::enumValue($model->scope, 'organization'),
            status: self::enumValue($model->status, 'draft'),
            moduleKey: $model->module_key,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toRuleDefinition(RuleDefinitionModel $model): RuleDefinition
    {
        return new RuleDefinition(
            publicId: $model->public_id,
            ruleSetPublicId: $model->rule_set_public_id,
            name: $model->name,
            description: $model->description,
            type: self::enumValue($model->type, 'validation'),
            scope: self::enumValue($model->scope, 'organization'),
            status: self::enumValue($model->status, 'draft'),
            triggerType: self::enumValue($model->trigger_type, 'manual'),
            priority: (int) $model->priority,
            conditions: is_array($model->conditions_json) ? $model->conditions_json : [],
            actions: is_array($model->actions_json) ? $model->actions_json : [],
            moduleKey: $model->module_key,
            entityKey: $model->entity_key,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toEvaluationResult(RuleEvaluationModel $model): RuleEvaluationResult
    {
        return new RuleEvaluationResult(
            publicId: $model->public_id,
            matched: (bool) $model->matched,
            allowed: (bool) $model->allowed,
            violations: is_array($model->violations_json) ? $model->violations_json : [],
            traces: is_array($model->traces_json) ? $model->traces_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toExecutionResult(RuleExecutionModel $model): RuleExecutionResult
    {
        return new RuleExecutionResult(
            publicId: $model->public_id,
            status: (string) $model->status,
            matchedRules: is_array($model->matched_rules_json) ? $model->matched_rules_json : [],
            actionsApplied: is_array($model->actions_applied_json) ? $model->actions_applied_json : [],
            warnings: is_array($model->warnings_json) ? $model->warnings_json : [],
            violations: is_array($model->violations_json) ? $model->violations_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    /**
     * @param  Builder<RuleSetModel|RuleDefinitionModel>  $query
     */
    public static function applyWorkspaceScope(Builder $query, ?string $workspaceId): Builder
    {
        if ($workspaceId === null) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($workspaceId) {
            $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
        });
    }

    public static function enumValue(mixed $value, string $default): string
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return is_string($value) && $value !== '' ? $value : $default;
    }
}

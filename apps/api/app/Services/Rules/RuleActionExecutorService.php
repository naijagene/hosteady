<?php

namespace App\Services\Rules;

use App\Modules\Sdk\Rules\Contracts\RuleActionExecutor;
use App\Modules\Sdk\Rules\Data\RuleAction;
use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Enums\RuleActionType;

class RuleActionExecutorService implements RuleActionExecutor
{
    /** @var list<string> */
    private const SAFE_ACTIONS = [
        'add_violation',
        'set_value',
        'calculate_value',
        'require_field',
        'show_field',
        'hide_field',
        'noop',
    ];

    public function execute(array $actions, array $facts, RuleDefinition $rule): array
    {
        $applied = [];
        $warnings = [];
        $violations = [];
        $mutations = [];

        foreach ($actions as $actionData) {
            $action = $actionData instanceof RuleAction
                ? $actionData
                : RuleAction::fromArray(is_array($actionData) ? $actionData : []);

            $type = RuleActionType::from($action->type);

            if (! in_array($type->value, self::SAFE_ACTIONS, true)) {
                $warnings[] = [
                    'action' => $type->value,
                    'message' => sprintf('External action [%s] is not executed in metadata-only mode.', $type->value),
                    'rule_public_id' => $rule->publicId,
                ];
                continue;
            }

            $result = match ($type) {
                RuleActionType::AddViolation => $this->addViolation($action, $rule),
                RuleActionType::SetValue => $this->setValue($action, $facts),
                RuleActionType::CalculateValue => $this->calculateValue($action, $facts),
                RuleActionType::RequireField => $this->fieldDirective('require_field', $action),
                RuleActionType::ShowField => $this->fieldDirective('show_field', $action),
                RuleActionType::HideField => $this->fieldDirective('hide_field', $action),
                RuleActionType::Noop => ['type' => 'noop'],
            };

            $applied[] = $result;

            if ($type === RuleActionType::AddViolation) {
                $violations[] = $result['violation'];
            }

            if (isset($result['mutations'])) {
                $mutations = array_merge($mutations, $result['mutations']);
            }
        }

        return [
            'actions_applied' => $applied,
            'warnings' => $warnings,
            'violations' => $violations,
            'mutations' => $mutations,
        ];
    }

    private function addViolation(RuleAction $action, RuleDefinition $rule): array
    {
        return [
            'type' => 'add_violation',
            'violation' => [
                'code' => $action->metadata['code'] ?? 'rule_violation',
                'message' => $action->message ?? 'Rule violation recorded.',
                'field' => $action->field,
                'severity' => $action->severity,
                'rule_public_id' => $rule->publicId,
                'metadata' => $action->metadata,
            ],
        ];
    }

    private function setValue(RuleAction $action, array $facts): array
    {
        $field = (string) ($action->field ?? '');
        $value = $action->value;

        return [
            'type' => 'set_value',
            'mutations' => $field !== '' ? [[
                'field' => $field,
                'value' => $value,
                'previous' => $facts[$field] ?? null,
            ]] : [],
        ];
    }

    private function calculateValue(RuleAction $action, array $facts): array
    {
        $field = (string) ($action->field ?? '');
        $expression = is_array($action->value) ? $action->value : [];
        $left = $facts[$expression['left'] ?? ''] ?? $expression['left'] ?? 0;
        $right = $facts[$expression['right'] ?? ''] ?? $expression['right'] ?? 0;
        $operator = (string) ($expression['operator'] ?? '+');

        $result = match ($operator) {
            '+', 'add' => (float) $left + (float) $right,
            '-', 'subtract' => (float) $left - (float) $right,
            '*', 'multiply' => (float) $left * (float) $right,
            '/', 'divide' => ((float) $right) != 0.0 ? (float) $left / (float) $right : 0.0,
            default => (float) $left,
        };

        return [
            'type' => 'calculate_value',
            'mutations' => $field !== '' ? [[
                'field' => $field,
                'value' => $result,
                'previous' => $facts[$field] ?? null,
            ]] : [],
        ];
    }

    private function fieldDirective(string $type, RuleAction $action): array
    {
        return [
            'type' => $type,
            'field' => $action->field,
            'metadata' => $action->metadata,
        ];
    }
}

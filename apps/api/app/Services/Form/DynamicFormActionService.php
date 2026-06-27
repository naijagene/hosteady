<?php

namespace App\Services\Form;

use App\Modules\Sdk\Form\Contracts\FormConditionEvaluator;
use App\Modules\Sdk\Form\Data\FormAction;
use App\Modules\Sdk\Form\Data\FormDefinition;

class DynamicFormActionService
{
    public function __construct(
        private readonly FormConditionEvaluator $conditionEvaluator,
    ) {
    }

    /**
     * @return list<FormAction>
     */
    public function resolve(FormDefinition $definition, array $values = [], array $context = []): array
    {
        $resolved = [];

        foreach ($definition->actions as $action) {
            if ($this->isActionVisible($action, $definition, $values, $context)) {
                $resolved[] = $action;
            }
        }

        if ($resolved === []) {
            return $definition->actions;
        }

        return $resolved;
    }

    private function isActionVisible(
        FormAction $action,
        FormDefinition $definition,
        array $values,
        array $context,
    ): bool {
        foreach ($definition->conditions as $condition) {
            if (($condition->targetType ?? '') !== 'action') {
                continue;
            }

            if (($condition->targetKey ?? '') !== $action->key) {
                continue;
            }

            if (! $this->conditionEvaluator->evaluate($condition, $values, $context)) {
                return false;
            }
        }

        return true;
    }
}

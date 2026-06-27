<?php

namespace App\Services\Form;

use App\Modules\Sdk\Form\Contracts\FormConditionEvaluator;
use App\Modules\Sdk\Form\Contracts\FormFieldResolver;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormField;

class DynamicFormFieldResolver implements FormFieldResolver
{
    public function __construct(
        private readonly FormConditionEvaluator $conditionEvaluator,
    ) {
    }

    public function resolve(FormDefinition $definition, array $context = []): array
    {
        $values = is_array($context['values'] ?? null) ? $context['values'] : [];
        $resolved = [];

        foreach ($definition->fields as $field) {
            if ($this->isVisible($field, $definition, $values, $context)) {
                $resolved[] = $this->applyRuntimeState($field, $values, $context);
            }
        }

        return $resolved;
    }

    public function resolveField(FormDefinition $definition, string $fieldKey, array $context = []): ?FormField
    {
        foreach ($definition->fields as $field) {
            if ($field->key !== $fieldKey) {
                continue;
            }

            $values = is_array($context['values'] ?? null) ? $context['values'] : [];

            if (! $this->isVisible($field, $definition, $values, $context)) {
                return null;
            }

            return $this->applyRuntimeState($field, $values, $context);
        }

        return null;
    }

    private function isVisible(FormField $field, FormDefinition $definition, array $values, array $context): bool
    {
        foreach ($field->conditions as $condition) {
            if (($condition->targetType ?? 'visibility') !== 'visibility') {
                continue;
            }

            if (! $this->conditionEvaluator->evaluate($condition, $values, $context)) {
                return false;
            }
        }

        foreach ($definition->conditions as $condition) {
            if (($condition->targetType ?? '') !== 'visibility') {
                continue;
            }

            if (($condition->targetKey ?? '') !== $field->key) {
                continue;
            }

            if (! $this->conditionEvaluator->evaluate($condition, $values, $context)) {
                return false;
            }
        }

        return true;
    }

    private function applyRuntimeState(FormField $field, array $values, array $context): FormField
    {
        $required = $field->required;

        foreach ($field->conditions as $condition) {
            if (($condition->targetType ?? '') === 'required' && $this->conditionEvaluator->evaluate($condition, $values, $context)) {
                $required = true;
            }
        }

        if ($required === $field->required) {
            return $field;
        }

        return new FormField(
            key: $field->key,
            label: $field->label,
            type: $field->type,
            required: $required,
            readOnly: $field->readOnly,
            searchable: $field->searchable,
            options: $field->options,
            validationRules: $field->validationRules,
            conditions: $field->conditions,
            metadata: $field->metadata,
            sectionKey: $field->sectionKey,
            tabKey: $field->tabKey,
            groupKey: $field->groupKey,
            repeatable: $field->repeatable,
        );
    }
}

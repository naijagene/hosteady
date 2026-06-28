<?php

namespace App\Services\Form;

use App\Modules\Sdk\Form\Contracts\FormConditionEvaluator;
use App\Modules\Sdk\Form\Contracts\FormValidator;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormField;
use App\Modules\Sdk\Form\Data\FormFieldValidationRule;
use App\Modules\Sdk\Form\Data\FormSubmissionRequest;
use App\Modules\Sdk\Form\Data\FormValidationIssue;
use App\Modules\Sdk\Form\Data\FormValidationReport;
use App\Modules\Sdk\Form\Enums\FormValidationSeverity;
use App\Modules\Sdk\Form\Exceptions\FormValidationException;
use App\Support\Tenant\TenantContext;

class DynamicFormValidationService implements FormValidator
{
    public function __construct(
        private readonly FormConditionEvaluator $conditionEvaluator,
        private readonly \App\Services\Rules\RuleFormBridge $ruleFormBridge,
    ) {
    }

    public function validate(FormDefinition $definition): FormValidationReport
    {
        $issues = [];

        if ($definition->moduleKey === '') {
            $issues[] = $this->issue('missing_module_key', 'Module key is required.', 'module_key');
        }

        if ($definition->formKey === '') {
            $issues[] = $this->issue('missing_form_key', 'Form key is required.', 'form_key');
        }

        if ($definition->name === '') {
            $issues[] = $this->issue('missing_name', 'Form name is required.', 'name');
        }

        if ($definition->moduleKey !== '' && ! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $definition->moduleKey)) {
            $issues[] = $this->issue('invalid_module_key', 'Module key format is invalid.', 'module_key');
        }

        if ($definition->formKey !== '' && ! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $definition->formKey)) {
            $issues[] = $this->issue('invalid_form_key', 'Form key format is invalid.', 'form_key');
        }

        $fieldKeys = [];
        foreach ($definition->fields as $field) {
            if ($field->key === '') {
                $issues[] = $this->issue('missing_field_key', 'Field key is required.', null, FormValidationSeverity::Error->value);
                continue;
            }

            if (isset($fieldKeys[$field->key])) {
                $issues[] = $this->issue('duplicate_field_key', sprintf('Duplicate field key [%s].', $field->key), $field->key);
            }

            $fieldKeys[$field->key] = true;
        }

        return new FormValidationReport(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            valid: $issues === [],
            issues: $issues,
        );
    }

    public function validateSubmission(
        FormSubmissionRequest $request,
        FormDefinition $definition,
    ): FormValidationReport {
        $issues = [];

        if ($request->moduleKey !== $definition->moduleKey || $request->formKey !== $definition->formKey) {
            $issues[] = $this->issue('definition_mismatch', 'Submission request does not match the form definition.');
        }

        $fieldMap = $this->fieldMap($definition->fields);
        $values = $request->values;

        foreach ($values as $key => $value) {
            if (! isset($fieldMap[$key])) {
                $issues[] = $this->issue('unknown_field', sprintf('Unknown field [%s].', $key), (string) $key);
            }
        }

        foreach ($definition->fields as $field) {
            $value = $values[$field->key] ?? null;
            $required = $this->isFieldRequired($field, $definition, $values);

            if ($required && $this->isEmpty($value)) {
                $issues[] = $this->issue('required', sprintf('%s is required.', $field->label), $field->key);
            }

            if ($field->readOnly && array_key_exists($field->key, $values) && $value !== null) {
                $issues[] = $this->issue('read_only_mutation', sprintf('Field [%s] is read-only.', $field->key), $field->key);
            }

            if ($value === null || $value === '') {
                continue;
            }

            $issues = array_merge($issues, $this->validateFieldType($field, $value));
            $issues = array_merge($issues, $this->validateFieldRules($field, $value));
        }

        if (app()->bound(TenantContext::class)) {
            $this->ruleFormBridge->validateFormBestEffort($request, $definition, $issues);
        }

        $this->dispatchIfBound(fn () => $this->ruleFormBridge->validateFormBestEffort($request, $definition, $issues));

        foreach ($definition->validationRules as $rule) {
            $field = $fieldMap[$rule->field] ?? null;
            $value = $values[$rule->field] ?? null;

            if ($field === null) {
                continue;
            }

            $issues = array_merge($issues, $this->validateRule($rule, $field, $value));
        }

        return new FormValidationReport(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            valid: $this->isValid($issues),
            issues: $issues,
        );
    }

    public function assertValid(FormDefinition $definition): void
    {
        $report = $this->validate($definition);

        if (! $report->valid) {
            throw new FormValidationException(sprintf(
                'Form definition [%s.%s] is invalid.',
                $definition->moduleKey,
                $definition->formKey,
            ));
        }
    }

    /**
     * @param  list<FormField>  $fields
     * @return array<string, FormField>
     */
    private function dispatchIfBound(callable $callback): void
    {
        if (app()->bound(TenantContext::class)) {
            $callback();
        }
    }

    /**
     * @param  list<FormField>  $fields
     * @return array<string, FormField>
     */
    private function fieldMap(array $fields): array
    {
        $map = [];

        foreach ($fields as $field) {
            $map[$field->key] = $field;
        }

        return $map;
    }

    private function isFieldRequired(FormField $field, FormDefinition $definition, array $values): bool
    {
        if (! $field->required) {
            return false;
        }

        foreach ($field->conditions as $condition) {
            if (($condition->targetType ?? '') === 'required') {
                return $this->conditionEvaluator->evaluate($condition, $values);
            }
        }

        foreach ($definition->conditions as $condition) {
            if (($condition->targetType ?? '') === 'required' && ($condition->targetKey ?? '') === $field->key) {
                return $this->conditionEvaluator->evaluate($condition, $values);
            }
        }

        return true;
    }

    /**
     * @return list<FormValidationIssue>
     */
    private function validateFieldType(FormField $field, mixed $value): array
    {
        $issues = [];
        $type = strtolower($field->type);

        $valid = match ($type) {
            'text', 'textarea', 'password', 'email', 'url', 'phone', 'uuid', 'display', 'hidden' => is_string($value),
            'number', 'integer', 'decimal' => is_numeric($value),
            'checkbox', 'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true),
            'date' => is_string($value) && $this->isDate($value),
            'datetime' => is_string($value) && $this->isDateTime($value),
            'select', 'enum', 'radio' => $this->isValidEnumValue($field, $value),
            'multiselect' => is_array($value),
            'entity_selector', 'reference' => $this->isValidReference($value),
            'file', 'document', 'attachment' => $this->isValidFileShape($value),
            'json' => is_array($value) || is_string($value),
            default => true,
        };

        if (! $valid) {
            $issues[] = $this->issue('invalid_type', sprintf('Field [%s] has an invalid value for type [%s].', $field->key, $field->type), $field->key);
        }

        return $issues;
    }

    /**
     * @return list<FormValidationIssue>
     */
    private function validateFieldRules(FormField $field, mixed $value): array
    {
        $issues = [];

        foreach ($field->validationRules as $rule) {
            $issues = array_merge($issues, $this->validateRule($rule, $field, $value));
        }

        return $issues;
    }

    /**
     * @return list<FormValidationIssue>
     */
    private function validateRule(FormFieldValidationRule $rule, FormField $field, mixed $value): array
    {
        if ($this->isEmpty($value)) {
            return [];
        }

        return match ($rule->rule) {
            'required' => $this->isEmpty($value)
                ? [$this->issue('required', $rule->message ?? sprintf('%s is required.', $field->label), $field->key)]
                : [],
            'min' => $this->validateMin($field, $value, $rule),
            'max' => $this->validateMax($field, $value, $rule),
            'regex' => $this->validateRegex($field, $value, $rule),
            'enum' => $this->validateEnumRule($field, $value, $rule),
            default => [],
        };
    }

    /**
     * @return list<FormValidationIssue>
     */
    private function validateMin(FormField $field, mixed $value, FormFieldValidationRule $rule): array
    {
        $min = $rule->parameters['min'] ?? $rule->parameters['value'] ?? null;

        if ($min === null) {
            return [];
        }

        if (is_numeric($value) && (float) $value < (float) $min) {
            return [$this->issue('min', $rule->message ?? sprintf('%s must be at least %s.', $field->label, $min), $field->key)];
        }

        if (is_string($value) && mb_strlen($value) < (int) $min) {
            return [$this->issue('min', $rule->message ?? sprintf('%s must be at least %s characters.', $field->label, $min), $field->key)];
        }

        return [];
    }

    /**
     * @return list<FormValidationIssue>
     */
    private function validateMax(FormField $field, mixed $value, FormFieldValidationRule $rule): array
    {
        $max = $rule->parameters['max'] ?? $rule->parameters['value'] ?? null;

        if ($max === null) {
            return [];
        }

        if (is_numeric($value) && (float) $value > (float) $max) {
            return [$this->issue('max', $rule->message ?? sprintf('%s must be at most %s.', $field->label, $max), $field->key)];
        }

        if (is_string($value) && mb_strlen($value) > (int) $max) {
            return [$this->issue('max', $rule->message ?? sprintf('%s must be at most %s characters.', $field->label, $max), $field->key)];
        }

        return [];
    }

    /**
     * @return list<FormValidationIssue>
     */
    private function validateRegex(FormField $field, mixed $value, FormFieldValidationRule $rule): array
    {
        $pattern = $rule->parameters['pattern'] ?? $rule->parameters['regex'] ?? null;

        if (! is_string($pattern) || ! is_string($value)) {
            return [];
        }

        if (@preg_match($pattern, $value) !== 1) {
            return [$this->issue('regex', $rule->message ?? sprintf('%s format is invalid.', $field->label), $field->key)];
        }

        return [];
    }

    /**
     * @return list<FormValidationIssue>
     */
    private function validateEnumRule(FormField $field, mixed $value, FormFieldValidationRule $rule): array
    {
        $allowed = $rule->parameters['values'] ?? $rule->parameters['enum'] ?? [];

        if (! is_array($allowed) || $allowed === []) {
            return $this->isValidEnumValue($field, $value)
                ? []
                : [$this->issue('enum', $rule->message ?? sprintf('%s has an invalid option.', $field->label), $field->key)];
        }

        if (! in_array($value, $allowed, true)) {
            return [$this->issue('enum', $rule->message ?? sprintf('%s has an invalid option.', $field->label), $field->key)];
        }

        return [];
    }

    private function isValidEnumValue(FormField $field, mixed $value): bool
    {
        if ($field->options === []) {
            return is_string($value) || is_numeric($value);
        }

        foreach ($field->options as $option) {
            if ((string) $option->value === (string) $value) {
                return true;
            }
        }

        return false;
    }

    private function isValidReference(mixed $value): bool
    {
        if (is_string($value)) {
            return $value !== '';
        }

        if (! is_array($value)) {
            return false;
        }

        return isset($value['public_id']) || isset($value['entity_public_id']) || isset($value['id']);
    }

    private function isValidFileShape(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        return isset($value['public_id'])
            || isset($value['file_public_id'])
            || isset($value['document_public_id'])
            || isset($value['name']);
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        return is_array($value) && $value === [];
    }

    private function isDate(string $value): bool
    {
        return (bool) strtotime($value);
    }

    private function isDateTime(string $value): bool
    {
        return (bool) strtotime($value);
    }

    /**
     * @param  list<FormValidationIssue>  $issues
     */
    private function isValid(array $issues): bool
    {
        foreach ($issues as $issue) {
            if ($issue->severity === FormValidationSeverity::Error->value) {
                return false;
            }
        }

        return true;
    }

    private function issue(
        string $code,
        string $message,
        ?string $field = null,
        string $severity = FormValidationSeverity::Error->value,
    ): FormValidationIssue {
        return new FormValidationIssue(
            code: $code,
            message: $message,
            severity: $severity,
            field: $field,
        );
    }
}

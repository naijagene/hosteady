<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Contracts\EntityRecordValidator;
use App\Modules\Sdk\DataRepository\Data\EntityRecordCreateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordUpdateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordValidationIssue;
use App\Modules\Sdk\DataRepository\Data\EntityRecordValidationReport;
use App\Modules\Sdk\DataRepository\Enums\EntityRecordValidationSeverity;
use App\Modules\Sdk\DataRepository\Exceptions\EntityRecordValidationException;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityFieldDefinition;

class EnterpriseEntityRecordValidationService implements EntityRecordValidator
{
    public function validateCreate(EntityRecordCreateRequest $request, EntityDefinition $definition): EntityRecordValidationReport
    {
        return $this->validateValues(
            moduleKey: $definition->moduleKey,
            entityKey: $definition->entityKey,
            values: $request->values,
            definition: $definition,
            existingValues: [],
            isUpdate: false,
        );
    }

    public function validateUpdate(
        EntityRecordUpdateRequest $request,
        EntityDefinition $definition,
        array $existingValues,
    ): EntityRecordValidationReport {
        return $this->validateValues(
            moduleKey: $definition->moduleKey,
            entityKey: $definition->entityKey,
            values: array_merge($existingValues, $request->values),
            definition: $definition,
            existingValues: $existingValues,
            isUpdate: true,
            changedKeys: array_keys($request->values),
        );
    }

    public function assertValid(EntityRecordValidationReport $report): void
    {
        if (! $report->valid) {
            throw new EntityRecordValidationException(sprintf(
                'Entity record [%s.%s] is invalid.',
                $report->moduleKey,
                $report->entityKey,
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $existingValues
     * @param  list<string>  $changedKeys
     */
    private function validateValues(
        string $moduleKey,
        string $entityKey,
        array $values,
        EntityDefinition $definition,
        array $existingValues,
        bool $isUpdate,
        array $changedKeys = [],
    ): EntityRecordValidationReport {
        $issues = [];
        $knownKeys = [];
        $allowExtra = ($definition->metadata['allow_extra_fields'] ?? false) === true;

        foreach ($definition->fields as $field) {
            $fieldKey = $this->fieldKey($field);
            if ($fieldKey === '') {
                continue;
            }

            $knownKeys[] = $fieldKey;
            $fieldType = $this->fieldType($field);
            $rules = is_array($field->metadata['validation'] ?? null) ? $field->metadata['validation'] : [];
            $readOnly = (bool) ($field->metadata['read_only'] ?? false);
            $hasValue = array_key_exists($fieldKey, $values);
            $value = $values[$fieldKey] ?? null;

            if ($readOnly && $isUpdate && in_array($fieldKey, $changedKeys, true)) {
                $issues[] = new EntityRecordValidationIssue(
                    code: 'read_only_field',
                    message: sprintf('Field [%s] is read-only.', $fieldKey),
                    severity: EntityRecordValidationSeverity::Error->value,
                    field: $fieldKey,
                );
            }

            if ($field->required && ! $hasValue && ($value === null || $value === '')) {
                $issues[] = new EntityRecordValidationIssue(
                    code: 'required_field',
                    message: sprintf('Field [%s] is required.', $fieldKey),
                    severity: EntityRecordValidationSeverity::Error->value,
                    field: $fieldKey,
                );

                continue;
            }

            if (! $hasValue || $value === null) {
                continue;
            }

            $issues = array_merge($issues, $this->validateFieldType($fieldKey, $fieldType, $value));
            $issues = array_merge($issues, $this->validateFieldRules($fieldKey, $fieldType, $value, $rules));
        }

        if (! $allowExtra) {
            foreach (array_keys($values) as $key) {
                if (! in_array($key, $knownKeys, true)) {
                    $issues[] = new EntityRecordValidationIssue(
                        code: 'unknown_field',
                        message: sprintf('Field [%s] is not defined on the entity.', $key),
                        severity: EntityRecordValidationSeverity::Error->value,
                        field: $key,
                    );
                }
            }
        }

        return new EntityRecordValidationReport(
            moduleKey: $moduleKey,
            entityKey: $entityKey,
            valid: $issues === [],
            issues: $issues,
        );
    }

    private function fieldKey(EntityFieldDefinition $field): string
    {
        if ($field->key !== '') {
            return $field->key;
        }

        return (string) ($field->metadata['field_key'] ?? '');
    }

    private function fieldType(EntityFieldDefinition $field): string
    {
        if ($field->type !== '' && $field->type !== 'string') {
            return $field->type;
        }

        return (string) ($field->metadata['field_type'] ?? $field->type ?: 'string');
    }

    /**
     * @return list<EntityRecordValidationIssue>
     */
    private function validateFieldType(string $fieldKey, string $fieldType, mixed $value): array
    {
        $issues = [];

        $valid = match ($fieldType) {
            'string', 'text' => is_string($value),
            'number', 'integer' => is_int($value) || (is_string($value) && ctype_digit($value)),
            'decimal' => is_numeric($value),
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true),
            'date' => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1,
            'datetime' => is_string($value) && strtotime($value) !== false,
            'enum' => is_string($value) || is_int($value),
            'json' => is_array($value) || (is_string($value) && json_decode($value, true) !== null),
            'uuid' => is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1,
            'reference' => is_string($value) && $value !== '',
            default => true,
        };

        if (! $valid) {
            $issues[] = new EntityRecordValidationIssue(
                code: 'invalid_type',
                message: sprintf('Field [%s] must be of type [%s].', $fieldKey, $fieldType),
                severity: EntityRecordValidationSeverity::Error->value,
                field: $fieldKey,
            );
        }

        return $issues;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<EntityRecordValidationIssue>
     */
    private function validateFieldRules(string $fieldKey, string $fieldType, mixed $value, array $rules): array
    {
        $issues = [];

        if (isset($rules['min']) && is_numeric($value) && (float) $value < (float) $rules['min']) {
            $issues[] = new EntityRecordValidationIssue(
                code: 'min_value',
                message: sprintf('Field [%s] must be at least [%s].', $fieldKey, $rules['min']),
                severity: EntityRecordValidationSeverity::Error->value,
                field: $fieldKey,
            );
        }

        if (isset($rules['max']) && is_numeric($value) && (float) $value > (float) $rules['max']) {
            $issues[] = new EntityRecordValidationIssue(
                code: 'max_value',
                message: sprintf('Field [%s] must be at most [%s].', $fieldKey, $rules['max']),
                severity: EntityRecordValidationSeverity::Error->value,
                field: $fieldKey,
            );
        }

        if (isset($rules['regex']) && is_string($value) && preg_match('/'.$rules['regex'].'/', $value) !== 1) {
            $issues[] = new EntityRecordValidationIssue(
                code: 'regex_mismatch',
                message: sprintf('Field [%s] does not match the required pattern.', $fieldKey),
                severity: EntityRecordValidationSeverity::Error->value,
                field: $fieldKey,
            );
        }

        if (isset($rules['enum_values']) && is_array($rules['enum_values']) && ! in_array($value, $rules['enum_values'], true)) {
            $issues[] = new EntityRecordValidationIssue(
                code: 'invalid_enum',
                message: sprintf('Field [%s] contains an invalid enum value.', $fieldKey),
                severity: EntityRecordValidationSeverity::Error->value,
                field: $fieldKey,
            );
        }

        if (isset($rules['min_length']) && is_string($value) && mb_strlen($value) < (int) $rules['min_length']) {
            $issues[] = new EntityRecordValidationIssue(
                code: 'min_length',
                message: sprintf('Field [%s] is too short.', $fieldKey),
                severity: EntityRecordValidationSeverity::Error->value,
                field: $fieldKey,
            );
        }

        if (isset($rules['max_length']) && is_string($value) && mb_strlen($value) > (int) $rules['max_length']) {
            $issues[] = new EntityRecordValidationIssue(
                code: 'max_length',
                message: sprintf('Field [%s] is too long.', $fieldKey),
                severity: EntityRecordValidationSeverity::Error->value,
                field: $fieldKey,
            );
        }

        return $issues;
    }
}

<?php

namespace App\Services\Ui;

use App\Modules\Sdk\Ui\Contracts\UiConditionEvaluator;
use App\Modules\Sdk\Ui\Enums\UiConditionOperator;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class UiConditionEvaluatorService implements UiConditionEvaluator
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function evaluate(TenantContext $context, array $conditions, array $values = []): bool
    {
        if ($conditions === []) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            if (! $this->evaluateCondition($context, $condition, $values)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    private function evaluateCondition(TenantContext $context, array $condition, array $values = []): bool
    {
        $operator = (string) ($condition['operator'] ?? '');
        $field = (string) ($condition['field'] ?? '');
        $value = $condition['value'] ?? null;
        $metadata = is_array($condition['metadata'] ?? null) ? $condition['metadata'] : [];
        $actual = $this->resolveFieldValue($context, $field, $metadata, $values);

        return match ($operator) {
            UiConditionOperator::Equals->value, 'eq' => $this->normalize($actual) === $this->normalize($value),
            UiConditionOperator::NotEquals->value, 'neq' => $this->normalize($actual) !== $this->normalize($value),
            UiConditionOperator::Contains->value => $this->contains($actual, $value),
            UiConditionOperator::GreaterThan->value, 'gt' => $this->compare($actual, $value) > 0,
            UiConditionOperator::LessThan->value, 'lt' => $this->compare($actual, $value) < 0,
            UiConditionOperator::IsEmpty->value => $this->isEmpty($actual),
            UiConditionOperator::IsNotEmpty->value => ! $this->isEmpty($actual),
            UiConditionOperator::HasPermission->value => $this->hasPermission($context, $value, $field),
            UiConditionOperator::HasRole->value => $this->hasRole($context, $value, $field),
            UiConditionOperator::FeatureEnabled->value => $this->featureEnabled($context, $value, $field, $metadata),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function resolveFieldValue(TenantContext $context, string $field, array $metadata, array $values = []): mixed
    {
        if ($field === '') {
            return null;
        }

        $contextValues = [
            'organization_public_id' => $context->organizationPublicId,
            'workspace_public_id' => $context->workspacePublicId,
            'membership_public_id' => $context->membershipPublicId,
            'user_public_id' => $context->userPublicId,
            'module_key' => $metadata['module_key'] ?? null,
        ];

        if (array_key_exists($field, $contextValues)) {
            return $contextValues[$field];
        }

        if (array_key_exists($field, $metadata)) {
            return $metadata[$field];
        }

        if (array_key_exists($field, $values)) {
            return $values[$field];
        }

        return $metadata['values'][$field] ?? null;
    }

    private function hasPermission(TenantContext $context, mixed $value, string $field): bool
    {
        $permission = is_string($value) && $value !== '' ? $value : $field;

        if ($permission === '') {
            return false;
        }

        return $this->authorizationService->allows($context, $permission);
    }

    private function hasRole(TenantContext $context, mixed $value, string $field): bool
    {
        $role = is_string($value) && $value !== '' ? $value : $field;

        if ($role === '') {
            return false;
        }

        $memberRoles = $context->membership->memberRoles()->with('role')->get();

        foreach ($memberRoles as $memberRole) {
            $assignedRole = $memberRole->role;

            if ($assignedRole === null) {
                continue;
            }

            if (
                strcasecmp((string) $assignedRole->key, $role) === 0
                || strcasecmp((string) $assignedRole->name, $role) === 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function featureEnabled(TenantContext $context, mixed $value, string $field, array $metadata): bool
    {
        $feature = is_string($value) && $value !== '' ? $value : $field;

        if ($feature === '') {
            return false;
        }

        $capabilities = is_array($metadata['capabilities'] ?? null) ? $metadata['capabilities'] : [];

        if (array_key_exists($feature, $capabilities)) {
            return (bool) $capabilities[$feature];
        }

        $configKey = str_contains($feature, '.')
            ? $feature
            : 'heos.enterprise.'.$feature.'.enabled';

        return (bool) config($configKey, true);
    }

    private function normalize(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            return strtolower(trim($value));
        }

        return $value;
    }

    private function contains(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual)) {
            return in_array($expected, $actual, true)
                || (is_string($expected) && in_array($expected, array_map('strval', $actual), true));
        }

        if (is_string($actual) && (is_string($expected) || is_numeric($expected))) {
            return str_contains(strtolower($actual), strtolower((string) $expected));
        }

        return false;
    }

    private function compare(mixed $actual, mixed $expected): int
    {
        if (! is_numeric($actual) || ! is_numeric($expected)) {
            return strcmp((string) $actual, (string) $expected);
        }

        return $actual <=> $expected;
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
}

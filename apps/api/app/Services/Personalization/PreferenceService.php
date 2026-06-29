<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationPreference;
use App\Modules\Sdk\Personalization\Data\PreferenceItem;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class PreferenceService implements \App\Modules\Sdk\Personalization\Contracts\PersonalizationPreferenceStore
{
    public function __construct(
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
        private readonly PersonalizationAuditRecorder $auditRecorder,
    ) {
    }

    /** @return list<PreferenceItem> */
    public function list(TenantContext $context, ?string $scope = null): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_preferences')) {
            return [];
        }

        $query = PersonalizationPreference::query();
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        PersonalizationMapper::applyMembershipScope($query, $context);

        if ($scope !== null) {
            $query->where('scope', $scope);
        }

        return $query->get()->map(fn (PersonalizationPreference $model) => PersonalizationMapper::toPreference($model))->all();
    }

    public function upsert(TenantContext $context, string $key, string $type, mixed $value, string $scope = 'membership'): PreferenceItem
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_preferences')) {
            throw new \App\Modules\Sdk\Personalization\Exceptions\PreferenceException('Personalization preferences table is not available.');
        }

        $attributes = PersonalizationMapper::scopeAttributes($context, $scope);
        $query = PersonalizationPreference::query()
            ->where('preference_key', $key)
            ->where('scope', $scope);
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        PersonalizationMapper::applyMembershipScope($query, $context);

        /** @var PersonalizationPreference|null $existing */
        $existing = $query->first();

        $payload = $this->encodeValue($type, $value);

        if ($existing === null) {
            $existing = PersonalizationPreference::query()->create(array_merge($attributes, [
                'id' => (string) Str::uuid7(),
                'preference_key' => $key,
                'value_type' => $type,
            ], $payload));
        } else {
            $existing->fill(array_merge(['value_type' => $type], $payload));
            $existing->save();
        }

        $mapped = PersonalizationMapper::toPreference($existing->fresh());
        $this->auditRecorder->recordPreferenceUpdated($mapped->publicId);

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return list<PreferenceItem>
     */
    public function patchMany(TenantContext $context, array $preferences, string $scope = 'membership'): array
    {
        $updated = [];
        foreach ($preferences as $key => $value) {
            $type = $this->inferType($value);
            $updated[] = $this->upsert($context, (string) $key, $type, $value, $scope);
        }

        return $updated;
    }

    /**
     * @return array<string, mixed>
     */
    private function encodeValue(string $type, mixed $value): array
    {
        return match ($type) {
            'boolean' => [
                'value_boolean' => (bool) $value,
                'value_string' => null,
                'value_integer' => null,
                'value_decimal' => null,
                'value_payload' => null,
            ],
            'integer' => [
                'value_integer' => (int) $value,
                'value_string' => null,
                'value_boolean' => null,
                'value_decimal' => null,
                'value_payload' => null,
            ],
            'decimal' => [
                'value_decimal' => (float) $value,
                'value_string' => null,
                'value_boolean' => null,
                'value_integer' => null,
                'value_payload' => null,
            ],
            'json', 'map', 'list', 'enum' => [
                'value_payload' => is_array($value) ? $value : [],
                'value_string' => null,
                'value_boolean' => null,
                'value_integer' => null,
                'value_decimal' => null,
            ],
            default => [
                'value_string' => (string) $value,
                'value_boolean' => null,
                'value_integer' => null,
                'value_decimal' => null,
                'value_payload' => null,
            ],
        };
    }

    private function inferType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'decimal',
            is_array($value) && array_is_list($value) => 'list',
            is_array($value) => 'map',
            default => 'string',
        };
    }
}

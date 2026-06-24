<?php

namespace App\Services\Audit;

use App\Enums\AuditEntityType;

class AuditRedactor
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN_FIELDS = [
        'password',
        'password_hash',
        'token',
        'token_hash',
        'remember_token',
        'plain_text_token',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const ENTITY_FIELD_ALLOWLIST = [
        AuditEntityType::Organization->value => [
            'name',
            'slug',
            'status',
            'timezone',
            'locale',
            'plan_tier',
        ],
        AuditEntityType::Workspace->value => [
            'name',
            'slug',
            'status',
            'is_default',
        ],
        AuditEntityType::OrganizationMembership->value => [
            'status',
            'title',
            'join_method',
        ],
        AuditEntityType::Role->value => [
            'key',
            'name',
            'status',
            'is_system',
        ],
        AuditEntityType::Invitation->value => [
            'email',
            'status',
            'expires_at',
        ],
        AuditEntityType::Application->value => [
            'key',
            'name',
            'version',
            'status',
            'is_core',
            'category',
        ],
        AuditEntityType::OrganizationApplication->value => [
            'status',
            'installed_version',
            'installed_at',
            'config',
        ],
        AuditEntityType::User->value => [
            'name',
            'display_name',
            'status',
            'email',
        ],
    ];

    /**
     * @param  array<string, mixed>|null  $state
     * @return array<string, mixed>|null
     */
    public function redact(?array $state, ?AuditEntityType $entityType): ?array
    {
        if ($state === null) {
            return null;
        }

        $allowlist = $entityType !== null
            ? (self::ENTITY_FIELD_ALLOWLIST[$entityType->value] ?? [])
            : [];

        if (isset($state['snapshot']) && is_array($state['snapshot'])) {
            return [
                'snapshot' => $this->redactSnapshot($state['snapshot'], $allowlist),
            ];
        }

        if (isset($state['fields']) && is_array($state['fields'])) {
            return [
                'fields' => $this->redactFieldDiffs($state['fields'], $allowlist),
                'snapshot' => isset($state['snapshot']) && is_array($state['snapshot'])
                    ? $this->redactSnapshot($state['snapshot'], $allowlist)
                    : null,
            ];
        }

        return $this->redactSnapshot($state, $allowlist);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  list<string>  $allowlist
     * @return array<string, mixed>
     */
    private function redactFieldDiffs(array $fields, array $allowlist): array
    {
        $redacted = [];

        foreach ($fields as $field => $change) {
            if (! $this->isAllowedField($field, $allowlist)) {
                continue;
            }

            if (! is_array($change)) {
                continue;
            }

            $redacted[$field] = [
                'from' => $change['from'] ?? null,
                'to' => $change['to'] ?? null,
            ];
        }

        return $redacted;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  list<string>  $allowlist
     * @return array<string, mixed>
     */
    private function redactSnapshot(array $snapshot, array $allowlist): array
    {
        $redacted = [];

        foreach ($snapshot as $field => $value) {
            if ($this->isAllowedField($field, $allowlist)) {
                $redacted[$field] = $value;
            }
        }

        return $redacted;
    }

    /**
     * @param  list<string>  $allowlist
     */
    private function isAllowedField(string $field, array $allowlist): bool
    {
        if (in_array($field, self::FORBIDDEN_FIELDS, true)) {
            return false;
        }

        if ($allowlist === []) {
            return false;
        }

        return in_array($field, $allowlist, true);
    }
}

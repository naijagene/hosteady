<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationShortcut;
use App\Modules\Sdk\Personalization\Data\ShortcutItem;
use App\Modules\Sdk\Personalization\Exceptions\ShortcutException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class ShortcutService
{
    public function __construct(
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
        private readonly PersonalizationAuditRecorder $auditRecorder,
    ) {
    }

    /** @return list<ShortcutItem> */
    public function list(TenantContext $context): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_shortcuts')) {
            return [];
        }

        $query = PersonalizationShortcut::query();
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        $query->where('user_id', $context->user->id);

        return $query->orderBy('label')->get()
            ->map(fn (PersonalizationShortcut $model) => PersonalizationMapper::toShortcut($model))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(TenantContext $context, array $payload): ShortcutItem
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_shortcuts')) {
            throw new ShortcutException('Personalization shortcuts table is not available.');
        }

        $label = (string) ($payload['label'] ?? 'Shortcut');
        $shortcutKey = (string) ($payload['shortcut_key'] ?? Str::slug($label));
        $keybinding = $this->normalizeKeybinding(isset($payload['keybinding']) ? (string) $payload['keybinding'] : null);
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];
        $warnings = [];

        if (isset($payload['keybinding']) && $keybinding === null) {
            $warnings[] = 'Invalid keybinding metadata was ignored.';
        }

        if ($keybinding !== null) {
            $duplicate = $this->findByKeybinding($context, $keybinding);
            if ($duplicate !== null) {
                $warnings[] = sprintf('Duplicate keybinding [%s] detected.', $keybinding);
            }
            $metadata['keybinding'] = $keybinding;
        }

        if ($warnings !== []) {
            $metadata['warnings'] = $warnings;
        }

        $query = PersonalizationShortcut::query()->where('shortcut_key', $shortcutKey);
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        $query->where('user_id', $context->user->id);

        /** @var PersonalizationShortcut|null $existing */
        $existing = $query->first();

        if ($existing !== null) {
            $existing->fill([
                'label' => $label,
                'route' => isset($payload['route']) ? (string) $payload['route'] : $existing->route,
                'target' => isset($payload['target']) ? (string) $payload['target'] : $existing->target,
                'is_active' => isset($payload['is_active']) ? (bool) $payload['is_active'] : $existing->is_active,
                'metadata' => array_merge(is_array($existing->metadata) ? $existing->metadata : [], $metadata),
            ]);
            $existing->save();
            $this->auditRecorder->recordShortcutUpdated($existing->public_id);

            return PersonalizationMapper::toShortcut($existing->fresh());
        }

        $created = PersonalizationShortcut::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'membership_id' => $context->membership->id,
            'user_id' => $context->user->id,
            'shortcut_key' => $shortcutKey,
            'label' => $label,
            'route' => isset($payload['route']) ? (string) $payload['route'] : null,
            'target' => isset($payload['target']) ? (string) $payload['target'] : null,
            'is_active' => isset($payload['is_active']) ? (bool) $payload['is_active'] : true,
            'metadata' => $metadata,
        ]);

        $this->auditRecorder->recordShortcutCreated($created->public_id);

        return PersonalizationMapper::toShortcut($created);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(TenantContext $context, string $shortcutPublicId, array $payload): ShortcutItem
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_shortcuts')) {
            throw new ShortcutException('Personalization shortcuts table is not available.');
        }

        $query = PersonalizationShortcut::query()->where('public_id', $shortcutPublicId);
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        $query->where('user_id', $context->user->id);

        /** @var PersonalizationShortcut $model */
        $model = $query->firstOrFail();

        $metadata = is_array($model->metadata) ? $model->metadata : [];
        if (array_key_exists('keybinding', $payload)) {
            $keybinding = $this->normalizeKeybinding($payload['keybinding'] !== null ? (string) $payload['keybinding'] : null);
            if ($payload['keybinding'] !== null && $keybinding === null) {
                unset($metadata['keybinding']);
            } elseif ($keybinding !== null) {
                $metadata['keybinding'] = $keybinding;
            }
        }

        $model->fill([
            'label' => (string) ($payload['label'] ?? $model->label),
            'route' => array_key_exists('route', $payload) ? ($payload['route'] !== null ? (string) $payload['route'] : null) : $model->route,
            'target' => array_key_exists('target', $payload) ? ($payload['target'] !== null ? (string) $payload['target'] : null) : $model->target,
            'is_active' => isset($payload['is_active']) ? (bool) $payload['is_active'] : $model->is_active,
            'metadata' => is_array($payload['metadata'] ?? null) ? array_merge($metadata, $payload['metadata']) : $metadata,
        ]);
        $model->save();

        $this->auditRecorder->recordShortcutUpdated($model->public_id);

        return PersonalizationMapper::toShortcut($model->fresh());
    }

    public function delete(TenantContext $context, string $shortcutPublicId): void
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_shortcuts')) {
            return;
        }

        $query = PersonalizationShortcut::query()->where('public_id', $shortcutPublicId);
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        $query->where('user_id', $context->user->id);

        $model = $query->first();
        if ($model !== null) {
            $model->delete();
            $this->auditRecorder->recordShortcutDeleted($shortcutPublicId);
        }
    }

    private function normalizeKeybinding(?string $keybinding): ?string
    {
        if ($keybinding === null || trim($keybinding) === '') {
            return null;
        }

        if (! preg_match('/^[a-z0-9+\-_]+$/i', $keybinding)) {
            return null;
        }

        return $keybinding;
    }

    private function findByKeybinding(TenantContext $context, string $keybinding): ?PersonalizationShortcut
    {
        $query = PersonalizationShortcut::query();
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        $query->where('user_id', $context->user->id);

        return $query->get()->first(function (PersonalizationShortcut $shortcut) use ($keybinding) {
            $metadata = is_array($shortcut->metadata) ? $shortcut->metadata : [];

            return ($metadata['keybinding'] ?? null) === $keybinding;
        });
    }
}

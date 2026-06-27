<?php

namespace App\Services\Form;

use App\Models\FormDefinition as FormDefinitionModel;
use App\Models\FormDraft;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormDraftReference;
use Illuminate\Support\Str;

class DynamicFormDraftService
{
    public function save(
        string $organizationId,
        ?string $workspaceId,
        FormDefinition $definition,
        array $draftData,
        ?string $entityPublicId = null,
        ?string $userId = null,
        ?string $membershipId = null,
        ?\DateTimeInterface $expiresAt = null,
    ): array {
        $formModel = FormDefinitionModel::query()->where('public_id', $definition->publicId)->first();
        $existing = $this->findLatestModel(
            $organizationId,
            $workspaceId,
            $definition->moduleKey,
            $definition->formKey,
            $entityPublicId,
            $userId,
        );

        if ($existing !== null) {
            $existing->update([
                'draft_data' => $draftData,
                'expires_at' => $expiresAt,
            ]);

            return DynamicFormMapper::toDraftReference($existing);
        }

        $draft = FormDraft::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'form_definition_id' => $formModel?->id,
            'module_key' => $definition->moduleKey,
            'entity_key' => $definition->entityKey,
            'entity_public_id' => $entityPublicId,
            'draft_data' => $draftData,
            'expires_at' => $expiresAt,
            'created_by_user_id' => $userId,
            'created_by_membership_id' => $membershipId,
        ]);

        return DynamicFormMapper::toDraftReference($draft);
    }

    public function loadLatest(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $formKey,
        ?string $entityPublicId = null,
        ?string $userId = null,
    ): ?array {
        $draft = $this->findLatestModel($organizationId, $workspaceId, $moduleKey, $formKey, $entityPublicId, $userId);

        if ($draft === null) {
            return null;
        }

        if ($this->isExpired($draft)) {
            $draft->delete();

            return null;
        }

        return [
            'reference' => DynamicFormMapper::toDraftReference($draft),
            'draft_data' => is_array($draft->draft_data) ? $draft->draft_data : [],
        ];
    }

    public function delete(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $formKey,
        ?string $entityPublicId = null,
        ?string $userId = null,
    ): void {
        $draft = $this->findLatestModel($organizationId, $workspaceId, $moduleKey, $formKey, $entityPublicId, $userId);

        $draft?->delete();
    }

    public function deleteByPublicId(string $publicId): void
    {
        FormDraft::query()->where('public_id', $publicId)->delete();
    }

    public function purgeExpired(): int
    {
        return FormDraft::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }

    public function toReference(FormDraft $draft): FormDraftReference
    {
        $formKey = $draft->formDefinition?->form_key ?? $draft->module_key;

        return new FormDraftReference(
            formKey: $formKey,
            draftId: $draft->public_id,
            publicId: $draft->public_id,
            moduleKey: $draft->module_key,
            expiresAt: $draft->expires_at?->toIso8601String(),
        );
    }

    private function findLatestModel(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $formKey,
        ?string $entityPublicId,
        ?string $userId,
    ): ?FormDraft {
        $formDefinition = FormDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('form_key', $formKey)
            ->where(function ($query) use ($organizationId, $workspaceId) {
                $query->where(function ($scoped) use ($organizationId, $workspaceId) {
                    $scoped->where('organization_id', $organizationId);
                    if ($workspaceId !== null) {
                        $scoped->where('workspace_id', $workspaceId);
                    } else {
                        $scoped->whereNull('workspace_id');
                    }
                })->orWhere(function ($global) {
                    $global->whereNull('organization_id')->whereNull('workspace_id');
                });
            })
            ->first();

        $query = FormDraft::query()
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->orderByDesc('updated_at');

        if ($formDefinition !== null) {
            $query->where('form_definition_id', $formDefinition->id);
        }

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        } else {
            $query->whereNull('workspace_id');
        }

        if ($entityPublicId !== null) {
            $query->where('entity_public_id', $entityPublicId);
        }

        if ($userId !== null) {
            $query->where('created_by_user_id', $userId);
        }

        return $query->first();
    }

    private function isExpired(FormDraft $draft): bool
    {
        return $draft->expires_at !== null && $draft->expires_at->isPast();
    }
}

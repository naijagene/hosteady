<?php

namespace App\Services\Navigation;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\NavigationActivityLog;
use App\Models\NavigationDefinition;
use App\Models\NavigationItem;
use App\Modules\Sdk\Navigation\Data\NavigationDefinition as NavigationDefinitionDto;
use App\Modules\Sdk\Navigation\Data\NavigationItem as NavigationItemDto;
use App\Modules\Sdk\Navigation\Data\NavigationPersonalization;
use App\Modules\Sdk\Navigation\Data\NavigationVersion as NavigationVersionDto;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class NavigationAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
        private readonly NavigationPlatformEventBridge $platformEventBridge,
    ) {
    }

    public function recordDefinitionRegistered(NavigationDefinitionDto $definition, ?TenantContext $context = null): void
    {
        $this->recordDefinition($definition->publicId, AuditAction::NavigationDefinitionRegistered, 'Navigation definition registered', $definition->toArray(), $context);
        $this->recordActivity($definition->publicId, null, 'definition.registered', null, $definition->toArray(), $context);
        $this->platformEventBridge->dispatchBestEffort($context ?? $this->context(), 'navigation.definition.registered', [
            'definition_public_id' => $definition->publicId,
            'module_key' => $definition->moduleKey,
            'navigation_key' => $definition->navigationKey,
        ]);
    }

    public function recordDefinitionUpdated(NavigationDefinitionDto $definition, ?array $before = null, ?TenantContext $context = null): void
    {
        $this->recordDefinition($definition->publicId, AuditAction::NavigationDefinitionUpdated, 'Navigation definition updated', $definition->toArray(), $context, $before);
        $this->recordActivity($definition->publicId, null, 'definition.updated', $before, $definition->toArray(), $context);
    }

    public function recordDefinitionDeleted(string $definitionPublicId, ?array $before = null, ?TenantContext $context = null): void
    {
        $this->recordDefinition($definitionPublicId, AuditAction::NavigationDefinitionUpdated, 'Navigation definition deleted', [], $context, $before);
        $this->recordActivity($definitionPublicId, null, 'definition.deleted', $before, null, $context);
    }

    public function recordItemCreated(NavigationItemDto $item, ?TenantContext $context = null): void
    {
        $this->recordEntity($item->publicId, AuditAction::NavigationItemUpdated, 'Navigation item created', $item->toArray(), AuditEntityType::NavigationItem, $context);
        $this->recordActivity($item->navigationDefinitionPublicId, $item->publicId, 'item.created', null, $item->toArray(), $context);
    }

    public function recordItemUpdated(NavigationItemDto $item, ?array $before = null, ?TenantContext $context = null): void
    {
        $this->recordEntity($item->publicId, AuditAction::NavigationItemUpdated, 'Navigation item updated', $item->toArray(), AuditEntityType::NavigationItem, $context, $before);
        $this->recordActivity($item->navigationDefinitionPublicId, $item->publicId, 'item.updated', $before, $item->toArray(), $context);
    }

    public function recordItemDeleted(string $itemPublicId, ?array $before = null, ?TenantContext $context = null): void
    {
        $this->recordEntity($itemPublicId, AuditAction::NavigationItemUpdated, 'Navigation item deleted', [], AuditEntityType::NavigationItem, $context, $before);
        $this->recordActivity(null, $itemPublicId, 'item.deleted', $before, null, $context);
    }

    public function recordDraftSaved(NavigationVersionDto $version, ?array $before = null, ?TenantContext $context = null): void
    {
        $this->recordEntity($version->publicId, AuditAction::NavigationDraftSaved, 'Navigation draft saved', $version->toArray(), AuditEntityType::NavigationVersion, $context, $before);
        $this->recordActivity($version->navigationDefinitionPublicId, null, 'draft.saved', $before, $version->toArray(), $context);
    }

    public function recordDraftDiscarded(string $definitionPublicId, ?array $before = null, ?TenantContext $context = null): void
    {
        $this->recordDefinition($definitionPublicId, AuditAction::NavigationDraftDiscarded, 'Navigation draft discarded', [], $context, $before);
        $this->recordActivity($definitionPublicId, null, 'draft.discarded', $before, null, $context);
    }

    public function recordPublished(
        NavigationDefinitionDto $definition,
        NavigationVersionDto $version,
        ?TenantContext $context = null,
    ): void {
        $this->recordDefinition($definition->publicId, AuditAction::NavigationPublished, 'Navigation published', $definition->toArray(), $context);
        $this->recordActivity($definition->publicId, null, 'navigation.published', null, [
            'definition' => $definition->toArray(),
            'version' => $version->toArray(),
        ], $context);
        $this->platformEventBridge->dispatchBestEffort($context ?? $this->context(), 'navigation.published', [
            'definition_public_id' => $definition->publicId,
            'version_public_id' => $version->publicId,
        ]);
    }

    public function recordPersonalizationUpdated(NavigationPersonalization $personalization, ?TenantContext $context = null): void
    {
        $this->recordEntity(
            $personalization->publicId,
            AuditAction::NavigationPersonalizationUpdated,
            'Navigation personalization updated',
            $personalization->toArray(),
            AuditEntityType::NavigationPersonalization,
            $context,
        );
        $this->recordActivity(
            $personalization->navigationDefinitionPublicId,
            null,
            'personalization.updated',
            null,
            $personalization->toArray(),
            $context,
        );
    }

    public function recordRendered(string $definitionPublicId, ?TenantContext $context = null): void
    {
        $this->recordDefinition($definitionPublicId, AuditAction::NavigationRendered, 'Navigation rendered', [], $context);
        $this->recordActivity($definitionPublicId, null, 'navigation.rendered', null, null, $context);
        $this->platformEventBridge->dispatchBestEffort($context ?? $this->context(), 'navigation.rendered', [
            'definition_public_id' => $definitionPublicId,
        ]);
    }

    private function recordDefinition(
        string $definitionPublicId,
        AuditAction $action,
        string $summary,
        array $metadata,
        ?TenantContext $context,
        ?array $before = null,
    ): void {
        $this->recordEntity($definitionPublicId, $action, $summary, $metadata, AuditEntityType::NavigationDefinition, $context, $before);
    }

    private function recordEntity(
        string $entityPublicId,
        AuditAction $action,
        string $summary,
        array $metadata,
        AuditEntityType $entityType,
        ?TenantContext $context,
        ?array $before = null,
    ): void {
        try {
            $context ??= $this->context();

            if ($context === null) {
                return;
            }

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context->organization->id,
                workspaceId: $context->workspace?->id,
                entityType: $entityType,
                entityPublicId: $entityPublicId,
                actorType: AuditActorType::User,
                actorUserId: $context->user->id,
                actorMembershipId: $context->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: array_filter([
                    'before' => $before,
                    'after' => $metadata !== [] ? $metadata : null,
                ]),
            ));
        } catch (\Throwable) {
        }
    }

    private function recordActivity(
        ?string $definitionPublicId,
        ?string $itemPublicId,
        string $action,
        ?array $before,
        ?array $after,
        ?TenantContext $context,
    ): void {
        try {
            $context ??= $this->context();

            if ($context === null) {
                return;
            }

            $definition = $definitionPublicId !== null
                ? NavigationDefinition::query()->where('public_id', $definitionPublicId)->first()
                : null;

            $item = $itemPublicId !== null
                ? NavigationItem::query()->where('public_id', $itemPublicId)->first()
                : null;

            NavigationActivityLog::query()->create([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'navigation_definition_id' => $definition?->id,
                'navigation_item_id' => $item?->id,
                'action' => $action,
                'before_state' => $before,
                'after_state' => $after,
                'actor_user_id' => $context->user->id,
                'actor_membership_id' => $context->membership->id,
                'metadata' => ['audit_action' => 'navigation.activity.logged'],
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }
    }

    private function context(): ?TenantContext
    {
        return app()->bound(TenantContext::class) ? app(TenantContext::class) : null;
    }
}

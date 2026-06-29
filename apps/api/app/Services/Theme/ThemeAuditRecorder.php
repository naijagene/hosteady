<?php

namespace App\Services\Theme;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\ThemeActivityLog;
use App\Modules\Sdk\Theme\Data\ThemeDefinition;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class ThemeAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
        private readonly ThemePlatformEventBridge $platformEventBridge,
    ) {
    }

    public function recordDefinitionRegistered(ThemeDefinition $definition): void
    {
        $this->recordEntity($definition->publicId, AuditAction::ThemeDefinitionRegistered, 'Theme definition registered', $definition->toArray(), AuditEntityType::ThemeDefinition);
        $this->recordActivity($definition->publicId, null, 'definition.registered', null, $definition->toArray());
        $this->platformEventBridge->dispatchBestEffort('theme.definition.registered', [
            'definition_public_id' => $definition->publicId,
            'theme_key' => $definition->themeKey,
        ]);
    }

    public function recordDefinitionUpdated(ThemeDefinition $definition): void
    {
        $this->recordEntity($definition->publicId, AuditAction::ThemeDefinitionUpdated, 'Theme definition updated', $definition->toArray(), AuditEntityType::ThemeDefinition);
        $this->recordActivity($definition->publicId, null, 'definition.updated', null, $definition->toArray());
    }

    public function recordBrandProfileUpdated(string $brandProfilePublicId): void
    {
        $this->recordEntity($brandProfilePublicId, AuditAction::ThemeBrandProfileUpdated, 'Theme brand profile updated', [], AuditEntityType::BrandProfile);
        $this->recordActivity(null, $brandProfilePublicId, 'brand_profile.updated', null, null);
    }

    public function recordPublished(ThemeDefinition $definition): void
    {
        $this->recordEntity($definition->publicId, AuditAction::ThemePublished, 'Theme published', $definition->toArray(), AuditEntityType::ThemeDefinition);
        $this->recordActivity($definition->publicId, null, 'theme.published', null, $definition->toArray());
        $this->platformEventBridge->dispatchBestEffort('theme.published', [
            'definition_public_id' => $definition->publicId,
        ]);
    }

    public function recordRendered(string $themeDefinitionPublicId, TenantContext $context): void
    {
        $this->recordEntity($themeDefinitionPublicId, AuditAction::ThemeRendered, 'Theme rendered', [], AuditEntityType::ThemeDefinition, $context);
        $this->recordActivity($themeDefinitionPublicId, null, 'theme.rendered', null, null, $context);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function recordActivity(
        ?string $themeDefinitionPublicId,
        ?string $brandProfilePublicId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?TenantContext $context = null,
    ): void {
        try {
            $context ??= $this->context();

            if ($context === null || ! app(ThemeTableHealthSupport::class)->isTablePresent('theme_activity_logs')) {
                return;
            }

            $themeDefinitionId = ThemeMapper::resolveThemeId($themeDefinitionPublicId);
            $brandProfileId = $brandProfilePublicId !== null
                ? \App\Models\BrandProfile::query()->where('public_id', $brandProfilePublicId)->value('id')
                : null;

            ThemeActivityLog::query()->create([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'theme_definition_id' => $themeDefinitionId,
                'brand_profile_id' => $brandProfileId,
                'action' => $action,
                'before_state' => $before,
                'after_state' => $after,
                'actor_user_id' => $context->user->id,
                'actor_membership_id' => $context->membership->id,
                'metadata' => ['audit_action' => 'theme.activity.logged'],
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>|null  $before
     */
    private function recordEntity(
        string $entityPublicId,
        AuditAction $action,
        string $summary,
        array $metadata,
        AuditEntityType $entityType,
        ?TenantContext $context = null,
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

    private function context(): ?TenantContext
    {
        return app()->bound(TenantContext::class) ? app(TenantContext::class) : null;
    }
}

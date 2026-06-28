<?php

namespace App\Services\Ui;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\UiActivityLog;
use App\Models\UiComponent;
use App\Models\UiPage;
use App\Modules\Sdk\Ui\Data\UiComponent as UiComponentDto;
use App\Modules\Sdk\Ui\Data\UiLayout;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Modules\Sdk\Ui\Data\UiPersonalization;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class UiAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
        private readonly UiPlatformEventBridge $platformEventBridge,
    ) {
    }

    public function recordPageRegistered(UiPageDefinition $page, ?TenantContext $context = null): void
    {
        $this->recordPage($page->publicId, AuditAction::UiPageRegistered, 'UI page registered', $page->toArray(), $context);
        $this->recordActivity($page->publicId, 'page.registered', null, $page->toArray(), $context);
        $this->platformEventBridge->dispatchBestEffort($context ?? $this->context(), 'ui.page.registered', [
            'page_public_id' => $page->publicId,
            'module_key' => $page->moduleKey,
            'page_key' => $page->pageKey,
        ]);
    }

    public function recordPageUpdated(UiPageDefinition $page, ?array $before = null, ?TenantContext $context = null): void
    {
        $this->recordPage($page->publicId, AuditAction::UiPageUpdated, 'UI page updated', $page->toArray(), $context, $before);
        $this->recordActivity($page->publicId, 'page.updated', $before, $page->toArray(), $context);
    }

    public function recordPageDeleted(string $pagePublicId, ?array $before = null, ?TenantContext $context = null): void
    {
        $this->recordPage($pagePublicId, AuditAction::UiPageUpdated, 'UI page deleted', [], $context, $before);
        $this->recordActivity($pagePublicId, 'page.deleted', $before, null, $context);
    }

    public function recordLayoutRegistered(UiLayout $layout, ?TenantContext $context = null): void
    {
        $this->recordEntity($layout->publicId, AuditAction::UiLayoutRegistered, 'UI layout registered', $layout->toArray(), $context);
        $this->recordActivity(null, 'layout.registered', null, $layout->toArray(), $context);
    }

    public function recordLayoutUpdated(UiLayout $layout, ?array $before = null, ?TenantContext $context = null): void
    {
        $this->recordEntity($layout->publicId, AuditAction::UiLayoutUpdated, 'UI layout updated', $layout->toArray(), $context, $before);
        $this->recordActivity(null, 'layout.updated', $before, $layout->toArray(), $context);
    }

    public function recordComponentRegistered(UiComponentDto $component, ?TenantContext $context = null): void
    {
        $model = UiComponent::query()->where('public_id', $component->publicId)->first();
        $this->recordEntity($component->publicId, AuditAction::UiComponentRegistered, 'UI component registered', $component->toArray(), $context);
        $this->recordActivity(null, 'component.registered', null, $component->toArray(), $context, uiComponentId: $model?->id);
        $this->platformEventBridge->dispatchBestEffort($context ?? $this->context(), 'ui.component.registered', [
            'component_public_id' => $component->publicId,
            'module_key' => $component->moduleKey,
            'component_key' => $component->componentKey,
        ]);
    }

    public function recordComponentUpdated(UiComponentDto $component, ?array $before = null, ?TenantContext $context = null): void
    {
        $this->recordEntity($component->publicId, AuditAction::UiComponentUpdated, 'UI component updated', $component->toArray(), $context, $before);
        $this->recordActivity(null, 'component.updated', $before, $component->toArray(), $context);
    }

    public function recordPersonalizationUpdated(UiPersonalization $personalization, ?TenantContext $context = null): void
    {
        $this->recordEntity(
            $personalization->publicId,
            AuditAction::UiPersonalizationUpdated,
            'UI personalization updated',
            $personalization->toArray(),
            $context,
        );
        $this->recordActivity(
            $personalization->pagePublicId ?? $personalization->publicId,
            'personalization.updated',
            null,
            $personalization->toArray(),
            $context,
        );
        $this->platformEventBridge->dispatchBestEffort($context ?? $this->context(), 'ui.personalization.updated', [
            'personalization_public_id' => $personalization->publicId,
            'page_public_id' => $personalization->pagePublicId,
        ]);
    }

    public function recordRendered(string $pagePublicId, ?TenantContext $context = null): void
    {
        $this->recordPage($pagePublicId, AuditAction::UiPageRendered, 'UI page rendered', [], $context);
        $this->recordActivity($pagePublicId, 'page.rendered', null, null, $context);
        $this->platformEventBridge->dispatchBestEffort($context ?? $this->context(), 'ui.page.rendered', [
            'page_public_id' => $pagePublicId,
        ]);
    }

    private function recordPage(
        string $pagePublicId,
        AuditAction $action,
        string $summary,
        array $metadata,
        ?TenantContext $context,
        ?array $before = null,
    ): void {
        $this->recordEntity($pagePublicId, $action, $summary, $metadata, $context, $before);
    }

    private function recordEntity(
        string $entityPublicId,
        AuditAction $action,
        string $summary,
        array $metadata,
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
                entityType: AuditEntityType::UiPage,
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
        ?string $pagePublicId,
        string $action,
        ?array $before,
        ?array $after,
        ?TenantContext $context,
        ?string $uiComponentId = null,
    ): void {
        try {
            $context ??= $this->context();

            if ($context === null) {
                return;
            }

            $page = $pagePublicId !== null
                ? UiPage::query()->where('public_id', $pagePublicId)->first()
                : null;

            UiActivityLog::query()->create([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'ui_page_id' => $page?->id,
                'ui_component_id' => $uiComponentId,
                'action' => $action,
                'before_state' => $before,
                'after_state' => $after,
                'actor_user_id' => $context->user->id,
                'actor_membership_id' => $context->membership->id,
                'metadata' => ['audit_action' => 'ui.activity.logged'],
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

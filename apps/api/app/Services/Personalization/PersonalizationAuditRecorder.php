<?php

namespace App\Services\Personalization;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\PersonalizationActivityLog;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class PersonalizationAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
        private readonly PersonalizationPlatformEventBridge $platformEventBridge,
    ) {
    }

    public function recordProfileCreated(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationProfileUpdated, 'Personalization profile created');
    }

    public function recordPreferenceUpdated(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationPreferenceUpdated, 'Personalization preference updated');
        $this->platformEventBridge->dispatchBestEffort('personalization.preference.updated', ['public_id' => $publicId]);
    }

    public function recordFavoriteAdded(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationFavoriteAdded, 'Personalization favorite added');
        $this->platformEventBridge->dispatchBestEffort('personalization.favorite.added', ['public_id' => $publicId]);
    }

    public function recordFavoriteRemoved(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationFavoriteRemoved, 'Personalization favorite removed');
    }

    public function recordRecentRecorded(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationRecentRecorded, 'Personalization recent item recorded');
    }

    public function recordShortcutCreated(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationShortcutUpdated, 'Personalization shortcut created');
        $this->platformEventBridge->dispatchBestEffort('personalization.shortcut.created', ['public_id' => $publicId]);
    }

    public function recordShortcutUpdated(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationShortcutUpdated, 'Personalization shortcut updated');
    }

    public function recordShortcutDeleted(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationShortcutUpdated, 'Personalization shortcut deleted');
    }

    public function recordOnboardingStarted(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationOnboardingUpdated, 'Personalization onboarding started');
    }

    public function recordOnboardingStepCompleted(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationOnboardingUpdated, 'Personalization onboarding step completed');
    }

    public function recordOnboardingCompleted(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationOnboardingUpdated, 'Personalization onboarding completed');
        $this->platformEventBridge->dispatchBestEffort('personalization.onboarding.completed', ['public_id' => $publicId]);
    }

    public function recordTipDismissed(string $publicId): void
    {
        $this->recordEntity($publicId, AuditAction::PersonalizationOnboardingUpdated, 'Personalization tip dismissed');
    }

    private function recordEntity(string $entityPublicId, AuditAction $action, string $summary): void
    {
        try {
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;
            if ($context === null) {
                return;
            }

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context->organization->id,
                workspaceId: $context->workspace?->id,
                entityType: AuditEntityType::PersonalizationProfile,
                entityPublicId: $entityPublicId,
                actorType: AuditActorType::User,
                actorUserId: $context->user->id,
                actorMembershipId: $context->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
            ));

            if (app(PersonalizationTableHealthSupport::class)->isTablePresent('personalization_activity_logs')) {
                PersonalizationActivityLog::query()->create([
                    'id' => (string) Str::uuid7(),
                    'organization_id' => $context->organization->id,
                    'workspace_id' => $context->workspace?->id,
                    'membership_id' => $context->membership->id,
                    'user_id' => $context->user->id,
                    'activity_type' => $action->value,
                    'subject_public_id' => $entityPublicId,
                    'metadata' => ['audit_action' => 'personalization.activity.logged'],
                    'occurred_at' => now(),
                ]);
            }
        } catch (\Throwable) {
        }
    }
}

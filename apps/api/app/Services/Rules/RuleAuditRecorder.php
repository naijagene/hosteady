<?php

namespace App\Services\Rules;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Data\RuleEvaluationResult;
use App\Modules\Sdk\Rules\Data\RuleExecutionResult;
use App\Modules\Sdk\Rules\Data\RuleSetDefinition;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class RuleAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordRuleSetCreated(RuleSetDefinition $ruleSet): void
    {
        $this->record($ruleSet->publicId, AuditAction::RuleSetCreated, 'Rule set created', $ruleSet->toArray());
    }

    public function recordRuleSetUpdated(RuleSetDefinition $before, RuleSetDefinition $after): void
    {
        $this->record($after->publicId, AuditAction::RuleSetUpdated, 'Rule set updated', ['before' => $before->toArray(), 'after' => $after->toArray()]);
    }

    public function recordRuleSetEnabled(RuleSetDefinition $ruleSet): void
    {
        $this->record($ruleSet->publicId, AuditAction::RuleSetEnabled, 'Rule set enabled', $ruleSet->toArray());
    }

    public function recordRuleSetDisabled(RuleSetDefinition $ruleSet): void
    {
        $this->record($ruleSet->publicId, AuditAction::RuleSetDisabled, 'Rule set disabled', $ruleSet->toArray());
    }

    public function recordRuleDefinitionCreated(RuleDefinition $rule): void
    {
        $this->record($rule->publicId, AuditAction::RuleDefinitionCreated, 'Rule definition created', $rule->toArray());
    }

    public function recordRuleDefinitionUpdated(RuleDefinition $before, RuleDefinition $after): void
    {
        $this->record($after->publicId, AuditAction::RuleDefinitionUpdated, 'Rule definition updated', ['before' => $before->toArray(), 'after' => $after->toArray()]);
    }

    public function recordRuleDefinitionEnabled(RuleDefinition $rule): void
    {
        $this->record($rule->publicId, AuditAction::RuleDefinitionEnabled, 'Rule definition enabled', $rule->toArray());
    }

    public function recordRuleDefinitionDisabled(RuleDefinition $rule): void
    {
        $this->record($rule->publicId, AuditAction::RuleDefinitionDisabled, 'Rule definition disabled', $rule->toArray());
    }

    public function recordEvaluated(RuleEvaluationResult $result, TenantContext $context): void
    {
        $this->record($result->publicId, AuditAction::RuleEvaluated, 'Rules evaluated', $result->toArray(), $context);
    }

    public function recordExecuted(RuleExecutionResult $result, TenantContext $context): void
    {
        $this->record($result->publicId, AuditAction::RuleExecuted, 'Rules executed', $result->toArray(), $context);
    }

    private function record(string $entityPublicId, AuditAction $action, string $summary, array $metadata, ?TenantContext $context = null): void
    {
        try {
            $context ??= app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::EnterpriseRule,
                entityPublicId: $entityPublicId,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: $metadata,
            ));
        } catch (\Throwable) {
        }
    }
}

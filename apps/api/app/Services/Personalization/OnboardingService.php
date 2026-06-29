<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationOnboardingState;
use App\Modules\Sdk\Personalization\Data\OnboardingState;
use App\Modules\Sdk\Personalization\Exceptions\OnboardingException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class OnboardingService
{
    public function __construct(
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
        private readonly PersonalizationAuditRecorder $auditRecorder,
        private readonly DismissedTipService $dismissedTipService,
    ) {
    }

    /** @return list<OnboardingState> */
    public function list(TenantContext $context): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_onboarding_states')) {
            return [];
        }

        $query = PersonalizationOnboardingState::query();
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        $query->where('user_id', $context->user->id);

        return $query->get()->map(fn (PersonalizationOnboardingState $model) => PersonalizationMapper::toOnboarding($model))->all();
    }

    public function start(TenantContext $context, string $flowKey): OnboardingState
    {
        $existing = $this->findModel($context, $flowKey);
        if ($existing !== null && $existing->status === 'completed') {
            throw new OnboardingException('Onboarding is completed. Reset before restarting.');
        }

        return $this->saveState($context, $flowKey, 'started', 'start', [], 'started', null);
    }

    public function step(TenantContext $context, string $flowKey, string $step): OnboardingState
    {
        $existing = $this->findModel($context, $flowKey);
        if ($existing !== null && $existing->status === 'completed') {
            throw new OnboardingException('Onboarding is completed. Reset before modifying steps.');
        }

        $allowedSteps = $this->flowSteps($flowKey);
        if ($allowedSteps !== [] && ! in_array($step, $allowedSteps, true)) {
            throw new OnboardingException(sprintf('Step [%s] is not part of onboarding flow [%s].', $step, $flowKey));
        }

        $completed = is_array($existing?->completed_steps) ? $existing->completed_steps : [];
        if (! in_array($step, $completed, true)) {
            $completed[] = $step;
        }

        return $this->saveState($context, $flowKey, 'in_progress', $step, $completed, 'step.completed');
    }

    public function complete(TenantContext $context, string $flowKey): OnboardingState
    {
        $existing = $this->findModel($context, $flowKey);
        if ($existing !== null && $existing->status === 'completed') {
            return PersonalizationMapper::toOnboarding($existing);
        }

        return $this->saveState($context, $flowKey, 'completed', null, null, 'completed', now());
    }

    public function reset(TenantContext $context, string $flowKey): OnboardingState
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_onboarding_states')) {
            throw new OnboardingException('Personalization onboarding table is not available.');
        }

        $model = $this->findModel($context, $flowKey) ?? $this->createModel($context, $flowKey);
        $model->fill([
            'status' => 'started',
            'current_step' => 'start',
            'completed_steps' => [],
            'dismissed_tips' => [],
            'completed_at' => null,
        ]);
        $model->save();

        return PersonalizationMapper::toOnboarding($model->fresh());
    }

    public function dismissTip(TenantContext $context, string $flowKey, string $tipKey): OnboardingState
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_onboarding_states')) {
            throw new OnboardingException('Personalization onboarding table is not available.');
        }

        $existing = $this->findModel($context, $flowKey) ?? $this->createModel($context, $flowKey);
        $tips = is_array($existing->dismissed_tips) ? $existing->dismissed_tips : [];
        if (! in_array($tipKey, $tips, true)) {
            $tips[] = $tipKey;
        }
        $existing->dismissed_tips = $tips;
        $existing->save();

        $this->dismissedTipService->record($context, $flowKey, $tipKey);
        $this->auditRecorder->recordTipDismissed($existing->public_id);

        return PersonalizationMapper::toOnboarding($existing->fresh());
    }

    /**
     * @return list<string>
     */
    private function flowSteps(string $flowKey): array
    {
        $flows = config('heos.enterprise.personalization.onboarding_flows', []);

        return is_array($flows[$flowKey] ?? null) ? $flows[$flowKey] : [];
    }

    private function saveState(
        TenantContext $context,
        string $flowKey,
        string $status,
        ?string $currentStep,
        ?array $completedSteps,
        string $auditAction,
        ?\DateTimeInterface $completedAt = null,
    ): OnboardingState {
        if (! $this->tableHealthSupport->isTablePresent('personalization_onboarding_states')) {
            throw new OnboardingException('Personalization onboarding table is not available.');
        }

        $model = $this->findModel($context, $flowKey) ?? $this->createModel($context, $flowKey);

        $model->fill([
            'status' => $status,
            'current_step' => $currentStep,
            'completed_steps' => $completedSteps ?? (is_array($model->completed_steps) ? $model->completed_steps : []),
            'completed_at' => $completedAt ?? ($status === 'completed' ? now() : null),
        ]);
        $model->save();

        match ($auditAction) {
            'started' => $this->auditRecorder->recordOnboardingStarted($model->public_id),
            'step.completed' => $this->auditRecorder->recordOnboardingStepCompleted($model->public_id),
            'completed' => $this->auditRecorder->recordOnboardingCompleted($model->public_id),
            default => null,
        };

        return PersonalizationMapper::toOnboarding($model->fresh());
    }

    private function findModel(TenantContext $context, string $flowKey): ?PersonalizationOnboardingState
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_onboarding_states')) {
            return null;
        }

        $query = PersonalizationOnboardingState::query()
            ->where('flow_key', $flowKey);
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        $query->where('user_id', $context->user->id);

        return $query->first();
    }

    private function createModel(TenantContext $context, string $flowKey): PersonalizationOnboardingState
    {
        return PersonalizationOnboardingState::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'membership_id' => $context->membership->id,
            'user_id' => $context->user->id,
            'flow_key' => $flowKey,
            'status' => 'started',
            'completed_steps' => [],
            'dismissed_tips' => [],
        ]);
    }
}

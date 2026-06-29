<?php

namespace App\Modules\Sdk\Personalization\Data;

readonly class OnboardingState implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $onboardingKey,
        public string $status,
        public ?string $currentStep,
        public array $completedSteps,
        public array $dismissedTips,
        public ?string $completedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            onboardingKey: (string) ($data['onboarding_key'] ?? $data['flow_key'] ?? $data['onboardingKey'] ?? ''),
            status: (string) ($data['status'] ?? 'started'),
            currentStep: isset($data['current_step']) ? (string) $data['current_step'] : null,
            completedSteps: is_array($data['completed_steps'] ?? null) ? $data['completed_steps'] : [],
            dismissedTips: is_array($data['dismissed_tips'] ?? null) ? $data['dismissed_tips'] : [],
            completedAt: isset($data['completed_at']) ? (string) $data['completed_at'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'onboarding_key' => $this->onboardingKey,
            'status' => $this->status,
            'current_step' => $this->currentStep,
            'completed_steps' => $this->completedSteps,
            'dismissed_tips' => $this->dismissedTips,
            'completed_at' => $this->completedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

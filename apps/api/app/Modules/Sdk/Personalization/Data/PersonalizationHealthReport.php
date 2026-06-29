<?php

namespace App\Modules\Sdk\Personalization\Data;

readonly class PersonalizationHealthReport implements \JsonSerializable
{
    public function __construct(
        public bool $enabled,
        public bool $healthy,
        public string $status,
        public int $profiles,
        public int $preferences,
        public int $favorites,
        public int $recentItems,
        public int $shortcuts,
        public int $onboardingStates,
        public array $warnings,
        public array $missingTables,
        public array $statistics,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? false),
            healthy: (bool) ($data['healthy'] ?? false),
            status: (string) ($data['status'] ?? 'ok'),
            profiles: (int) ($data['profiles'] ?? 0),
            preferences: (int) ($data['preferences'] ?? 0),
            favorites: (int) ($data['favorites'] ?? 0),
            recentItems: (int) ($data['recent_items'] ?? $data['recentItems'] ?? 0),
            shortcuts: (int) ($data['shortcuts'] ?? 0),
            onboardingStates: (int) ($data['onboarding_states'] ?? $data['onboardingStates'] ?? 0),
            warnings: is_array($data['warnings'] ?? null) ? $data['warnings'] : [],
            missingTables: is_array($data['missing_tables'] ?? $data['missingTables'] ?? null) ? ($data['missing_tables'] ?? $data['missingTables']) : [],
            statistics: is_array($data['statistics'] ?? null) ? $data['statistics'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'healthy' => $this->healthy,
            'status' => $this->status,
            'profiles' => $this->profiles,
            'preferences' => $this->preferences,
            'favorites' => $this->favorites,
            'recent_items' => $this->recentItems,
            'shortcuts' => $this->shortcuts,
            'onboarding_states' => $this->onboardingStates,
            'warnings' => $this->warnings,
            'missing_tables' => $this->missingTables,
            'statistics' => $this->statistics,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

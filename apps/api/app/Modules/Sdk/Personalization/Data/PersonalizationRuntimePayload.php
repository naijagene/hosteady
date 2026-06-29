<?php

namespace App\Modules\Sdk\Personalization\Data;

readonly class PersonalizationRuntimePayload implements \JsonSerializable
{
    public function __construct(
        public array $profile,
        public array $preferences,
        public array $favorites,
        public array $recent,
        public array $shortcuts,
        public array $quickActions,
        public array $onboarding,
        public array $themeOverride,
        public array $navigationOverrides,
        public array $dashboardOverrides,
        public array $tableOverrides,
        public array $notificationPreferencesReference,
        public array $capabilities,
        public array $metadata,
        public array $warnings,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            profile: is_array($data['profile'] ?? null) ? $data['profile'] : [],
            preferences: is_array($data['preferences'] ?? null) ? $data['preferences'] : [],
            favorites: is_array($data['favorites'] ?? null) ? $data['favorites'] : [],
            recent: is_array($data['recent'] ?? null) ? $data['recent'] : [],
            shortcuts: is_array($data['shortcuts'] ?? null) ? $data['shortcuts'] : [],
            quickActions: is_array($data['quick_actions'] ?? $data['quickActions'] ?? null) ? ($data['quick_actions'] ?? $data['quickActions']) : [],
            onboarding: is_array($data['onboarding'] ?? null) ? $data['onboarding'] : [],
            themeOverride: is_array($data['theme_override'] ?? $data['themeOverride'] ?? null) ? ($data['theme_override'] ?? $data['themeOverride']) : [],
            navigationOverrides: is_array($data['navigation_overrides'] ?? $data['navigationOverrides'] ?? null) ? ($data['navigation_overrides'] ?? $data['navigationOverrides']) : [],
            dashboardOverrides: is_array($data['dashboard_overrides'] ?? $data['dashboardOverrides'] ?? null) ? ($data['dashboard_overrides'] ?? $data['dashboardOverrides']) : [],
            tableOverrides: is_array($data['table_overrides'] ?? $data['tableOverrides'] ?? null) ? ($data['table_overrides'] ?? $data['tableOverrides']) : [],
            notificationPreferencesReference: is_array($data['notification_preferences_reference'] ?? $data['notificationPreferencesReference'] ?? null) ? ($data['notification_preferences_reference'] ?? $data['notificationPreferencesReference']) : [],
            capabilities: is_array($data['capabilities'] ?? null) ? $data['capabilities'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            warnings: is_array($data['warnings'] ?? null) ? $data['warnings'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'profile' => $this->profile,
            'preferences' => $this->preferences,
            'favorites' => $this->favorites,
            'recent' => $this->recent,
            'shortcuts' => $this->shortcuts,
            'quick_actions' => $this->quickActions,
            'onboarding' => $this->onboarding,
            'theme_override' => $this->themeOverride,
            'navigation_overrides' => $this->navigationOverrides,
            'dashboard_overrides' => $this->dashboardOverrides,
            'table_overrides' => $this->tableOverrides,
            'notification_preferences_reference' => $this->notificationPreferencesReference,
            'capabilities' => $this->capabilities,
            'metadata' => $this->metadata,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'preferences' => $this->preferences,
            'favorites' => $this->favorites,
            'recent_items' => $this->recent,
            'shortcuts' => $this->shortcuts,
            'quick_actions' => $this->quickActions,
            'onboarding_state' => $this->onboarding,
            'theme_override' => $this->themeOverride,
            'navigation_overrides' => $this->navigationOverrides,
            'dashboard_overrides' => $this->dashboardOverrides,
            'table_overrides' => $this->tableOverrides,
            'notification_preferences_reference' => $this->notificationPreferencesReference,
            'warnings' => $this->warnings,
            'source' => (string) ($this->metadata['source'] ?? 'personalization_framework'),
            'runtime_context' => [
                'organization_public_id' => $this->metadata['organization_public_id'] ?? null,
                'workspace_public_id' => $this->metadata['workspace_public_id'] ?? null,
                'membership_public_id' => $this->metadata['membership_public_id'] ?? null,
                'status' => $this->metadata['status'] ?? 'ok',
                'missing_tables' => $this->metadata['missing_tables'] ?? [],
            ],
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

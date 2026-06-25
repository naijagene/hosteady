<?php

namespace Tests\Feature\Services\Runtime;

use App\Services\Runtime\RuntimeSnapshotSerializer;
use App\Services\WorkspaceApplication\Data\ResolvedWorkspaceApplication;
use App\Services\WorkspaceApplication\Data\RuntimeMembershipSnapshot;
use App\Services\WorkspaceApplication\Data\RuntimeOrganizationSnapshot;
use App\Services\WorkspaceApplication\Data\RuntimeSettingValue;
use App\Services\WorkspaceApplication\Data\RuntimeWorkspaceSnapshot;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;
use Tests\TestCase;

class RuntimeSnapshotSerializerTest extends TestCase
{
    public function test_serializes_runtime_context_payload(): void
    {
        $context = $this->sampleContext();

        $payload = app(RuntimeSnapshotSerializer::class)->serialize($context);

        $this->assertSame('runtime-version', $payload['runtime_version']);
        $this->assertSame(4, $payload['settings_version']);
        $this->assertSame('WorkspaceRuntimeResolver', $payload['runtime_metadata']['generated_by']);
        $this->assertArrayHasKey('feature.enabled', $payload['active_applications'][0]['settings']);
        $this->assertTrue($payload['active_applications'][0]['settings']['feature.enabled']['is_default']);
    }

    public function test_deserializes_runtime_context_payload(): void
    {
        $serializer = app(RuntimeSnapshotSerializer::class);
        $original = $this->sampleContext();

        $restored = $serializer->deserialize($serializer->serialize($original));

        $this->assertSame($original->runtimeVersion, $restored->runtimeVersion);
        $this->assertSame($original->settingsVersion, $restored->settingsVersion);
        $this->assertSame($original->activeApplications[0]->key, $restored->activeApplications[0]->key);
        $this->assertSame(
            $original->activeApplications[0]->settings['feature.enabled']->value,
            $restored->activeApplications[0]->settings['feature.enabled']->value,
        );
        $this->assertTrue($restored->activeApplications[0]->settings['feature.enabled']->isDefault);
    }

    public function test_round_trip_preserves_capabilities_and_dependencies(): void
    {
        $serializer = app(RuntimeSnapshotSerializer::class);
        $original = $this->sampleContext(activeApplication: true);

        $restored = $serializer->deserialize($serializer->serialize($original));

        $this->assertEqualsCanonicalizing(['notifications'], $restored->activeApplications[0]->capabilities);
        $this->assertEqualsCanonicalizing(['core'], $restored->activeApplications[0]->dependencies);
        $this->assertSame('demo', $restored->activeApplication?->key);
    }

    public function test_round_trip_preserves_sensitive_setting_metadata(): void
    {
        $serializer = app(RuntimeSnapshotSerializer::class);
        $context = $this->sampleContext(includeSensitive: true);

        $restored = $serializer->deserialize($serializer->serialize($context));
        $secret = $restored->activeApplications[0]->settings['secret.token'];

        $this->assertSame('***', $secret->value);
        $this->assertTrue($secret->valueRedacted);
        $this->assertTrue($secret->isSensitive);
        $this->assertFalse($secret->isDefault);
    }

    private function sampleContext(bool $activeApplication = false, bool $includeSensitive = false): WorkspaceRuntimeContext
    {
        $settings = [
            'feature.enabled' => new RuntimeSettingValue(
                value: false,
                type: 'boolean',
                version: 0,
                isSensitive: false,
                valueRedacted: false,
                isDefault: true,
                definitionPublicId: '01999999-9999-7999-8999-999999999991',
                label: 'Feature Enabled',
                category: 'features',
            ),
        ];

        if ($includeSensitive) {
            $settings['secret.token'] = new RuntimeSettingValue(
                value: '***',
                type: 'string',
                version: 1,
                isSensitive: true,
                valueRedacted: true,
                isDefault: false,
            );
        }

        $application = new ResolvedWorkspaceApplication(
            workspaceApplicationPublicId: '01999999-9999-7999-8999-999999999998',
            organizationApplicationPublicId: '01999999-9999-7999-8999-999999999997',
            applicationPublicId: '01999999-9999-7999-8999-999999999996',
            key: 'demo',
            name: 'Demo Application',
            catalogVersion: '1.0.0',
            enabledVersion: '1.0.0',
            isBootstrap: false,
            settings: $settings,
            capabilities: ['notifications'],
            dependencies: ['core'],
        );

        return new WorkspaceRuntimeContext(
            organization: new RuntimeOrganizationSnapshot('org-public-id', 'Org', 'org', 'active'),
            workspace: new RuntimeWorkspaceSnapshot('workspace-public-id', 'Default', 'default', true, 'active'),
            membership: new RuntimeMembershipSnapshot('membership-public-id', 'active'),
            activeApplications: [$application],
            activeApplication: $activeApplication ? $application : null,
            runtimeVersion: 'runtime-version',
            settingsVersion: 4,
            runtimeMetadata: [
                'generated_at' => now()->toIso8601String(),
                'generated_by' => 'WorkspaceRuntimeResolver',
                'schema_version' => 1,
            ],
            capabilities: [
                'audit' => true,
                'settings' => true,
                'workspace' => true,
                'notifications' => false,
                'automation' => false,
            ],
        );
    }
}

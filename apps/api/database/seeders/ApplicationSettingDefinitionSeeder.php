<?php

namespace Database\Seeders;

use App\Enums\SettingDefinitionScope;
use App\Enums\SettingDefinitionStatus;
use App\Enums\WorkspaceSettingType;
use App\Models\Application;
use App\Models\ApplicationSettingDefinition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApplicationSettingDefinitionSeeder extends Seeder
{
    /**
     * @var list<array<string, mixed>>
     */
    private const DEMO_DEFINITIONS = [
        [
            'setting_key' => 'feature.enabled',
            'label' => 'Feature Enabled',
            'description' => 'Toggle the demo feature module.',
            'setting_type' => WorkspaceSettingType::Boolean,
            'default_value' => false,
            'is_required' => false,
            'is_sensitive' => false,
            'is_encrypted' => false,
            'category' => 'features',
            'sort_order' => 10,
            'validation_rules' => null,
        ],
        [
            'setting_key' => 'notification.email',
            'label' => 'Notification Email',
            'description' => 'Email address used for demo notifications.',
            'setting_type' => WorkspaceSettingType::String,
            'default_value' => null,
            'is_required' => false,
            'is_sensitive' => false,
            'is_encrypted' => false,
            'category' => 'notifications',
            'sort_order' => 20,
            'validation_rules' => [
                'pattern' => '^[^@]+@[^@]+\.[^@]+$',
            ],
        ],
        [
            'setting_key' => 'secret.token',
            'label' => 'Secret Token',
            'description' => 'Sensitive token used by the demo integration.',
            'setting_type' => WorkspaceSettingType::String,
            'default_value' => null,
            'is_required' => false,
            'is_sensitive' => true,
            'is_encrypted' => false,
            'category' => 'secrets',
            'sort_order' => 30,
            'validation_rules' => [
                'min_length' => 8,
            ],
        ],
    ];

    public function run(): void
    {
        $demoApplication = Application::query()->where('key', 'demo')->first();

        if ($demoApplication === null) {
            return;
        }

        foreach (self::DEMO_DEFINITIONS as $definition) {
            $existing = ApplicationSettingDefinition::query()
                ->where('application_id', $demoApplication->id)
                ->where('setting_key', $definition['setting_key'])
                ->where('scope', SettingDefinitionScope::Workspace)
                ->whereNull('deleted_at')
                ->first();

            $payload = [
                'label' => $definition['label'],
                'description' => $definition['description'],
                'setting_type' => $definition['setting_type'],
                'default_value' => $definition['default_value'],
                'is_required' => $definition['is_required'],
                'is_sensitive' => $definition['is_sensitive'],
                'is_encrypted' => $definition['is_encrypted'],
                'scope' => SettingDefinitionScope::Workspace,
                'category' => $definition['category'],
                'sort_order' => $definition['sort_order'],
                'validation_rules' => $definition['validation_rules'],
                'status' => SettingDefinitionStatus::Active,
            ];

            if ($existing !== null) {
                $existing->update($payload);

                continue;
            }

            ApplicationSettingDefinition::query()->create([
                'id' => (string) Str::uuid7(),
                'public_id' => (string) Str::uuid7(),
                'application_id' => $demoApplication->id,
                'setting_key' => $definition['setting_key'],
                ...$payload,
            ]);
        }
    }
}

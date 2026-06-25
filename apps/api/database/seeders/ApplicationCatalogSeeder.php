<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationSettingDefinition;
use App\Modules\Sdk\Data\ModuleSyncOptions;
use App\Modules\Sdk\ModuleRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApplicationCatalogSeeder extends Seeder
{
    /**
     * @var list<array<string, mixed>>
     */
    private const APPLICATIONS = [
        [
            'key' => 'core',
            'name' => 'HEOS Core',
            'description' => null,
            'version' => '1.0.0',
            'is_core' => true,
            'icon' => null,
            'category' => 'platform',
            'capabilities' => null,
            'dependencies' => null,
        ],
        [
            'key' => 'workspace',
            'name' => 'Workspace Module',
            'description' => null,
            'version' => '1.0.0',
            'is_core' => true,
            'icon' => null,
            'category' => 'platform',
            'capabilities' => null,
            'dependencies' => null,
        ],
        [
            'key' => 'demo',
            'name' => 'Demo Application',
            'description' => null,
            'version' => '1.0.0',
            'is_core' => false,
            'icon' => null,
            'category' => 'platform',
            'capabilities' => ['notifications', 'reporting'],
            'dependencies' => ['core', 'workspace'],
        ],
    ];

    public function run(): void
    {
        if (config('heos.sync.on_seed', true)) {
            Application::withoutEvents(function () {
                app(ModuleRegistry::class)->syncToDatabase(new ModuleSyncOptions);
            });

            return;
        }

        Application::withoutEvents(function () {
            foreach (self::APPLICATIONS as $application) {
                $existingApplication = Application::query()
                    ->where('key', $application['key'])
                    ->first();

                if ($existingApplication) {
                    $existingApplication->update([
                        'name' => $application['name'],
                        'description' => $application['description'],
                        'version' => $application['version'],
                        'status' => ApplicationStatus::Active,
                        'is_core' => $application['is_core'],
                        'icon' => $application['icon'],
                        'category' => $application['category'],
                        'capabilities' => $application['capabilities'],
                        'dependencies' => $application['dependencies'],
                    ]);

                    continue;
                }

                Application::query()->create([
                    'id' => (string) Str::uuid7(),
                    'public_id' => (string) Str::uuid7(),
                    'key' => $application['key'],
                    'name' => $application['name'],
                    'description' => $application['description'],
                    'version' => $application['version'],
                    'status' => ApplicationStatus::Active,
                    'is_core' => $application['is_core'],
                    'icon' => $application['icon'],
                    'category' => $application['category'],
                    'capabilities' => $application['capabilities'],
                    'dependencies' => $application['dependencies'],
                ]);
            }
        });

        ApplicationSettingDefinition::withoutEvents(function () {
            $this->call(ApplicationSettingDefinitionSeeder::class);
        });
    }
}

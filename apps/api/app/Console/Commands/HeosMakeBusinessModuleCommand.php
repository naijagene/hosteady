<?php

namespace App\Console\Commands;

use App\Modules\Sdk\Development\Data\BusinessModuleScaffoldRequest;
use App\Modules\Sdk\Development\Enums\BusinessModuleScaffoldTarget;
use App\Modules\Sdk\Development\Enums\BusinessModuleType;
use App\Services\Module\Development\BusinessModuleScaffolderService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class HeosMakeBusinessModuleCommand extends Command
{
    protected $signature = 'heos:make-business-module
        {key : The business module key in kebab-case}
        {--name= : Display name}
        {--type=business : Module type}
        {--with-entities : Scaffold domain entities}
        {--with-api : Scaffold API layer}
        {--with-tests : Scaffold module tests}
        {--with-workflows : Scaffold workflow placeholders}
        {--with-seeders : Scaffold database seeders}
        {--force : Overwrite existing module files}';

    protected $description = 'Scaffold a new HEOS business module';

    public function handle(BusinessModuleScaffolderService $scaffolder): int
    {
        $targets = [BusinessModuleScaffoldTarget::Module->value];

        if ($this->option('with-entities')) {
            $targets[] = BusinessModuleScaffoldTarget::Entity->value;
        }

        if ($this->option('with-api')) {
            $targets[] = BusinessModuleScaffoldTarget::Api->value;
        }

        if ($this->option('with-tests')) {
            $targets[] = BusinessModuleScaffoldTarget::Test->value;
        }

        if ($this->option('with-workflows')) {
            $targets[] = BusinessModuleScaffoldTarget::Workflow->value;
        }

        if ($this->option('with-seeders')) {
            $targets[] = BusinessModuleScaffoldTarget::Seeder->value;
        }

        $key = Str::kebab($this->argument('key'));
        $name = (string) ($this->option('name') ?: Str::headline(str_replace('.', ' ', $key)));

        try {
            $result = $scaffolder->scaffold(new BusinessModuleScaffoldRequest(
                moduleKey: $key,
                name: $name,
                type: (string) ($this->option('type') ?: BusinessModuleType::Business->value),
                targets: $targets,
                force: (bool) $this->option('force'),
            ));
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Business module [%s] scaffolded at %s', $result->moduleKey, $result->modulePath));
        $this->line('Created files:');
        foreach ($result->createdFiles as $file) {
            $this->line('  - '.$file);
        }

        if ($result->skippedFiles !== []) {
            $this->line('Skipped files:');
            foreach ($result->skippedFiles as $file) {
                $this->line('  - '.$file);
            }
        }

        return self::SUCCESS;
    }
}

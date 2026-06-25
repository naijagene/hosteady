<?php

namespace App\Console\Commands;

use App\Modules\Sdk\Data\ModuleSyncOptions;
use App\Modules\Sdk\ModuleRegistry;
use Illuminate\Console\Command;

class HeosSyncModulesCommand extends Command
{
    protected $signature = 'heos:sync-modules
        {--dry-run : Validate and compute changes without writing}
        {--module= : Sync only the requested module key}
        {--json : Output the sync result as JSON}';

    protected $description = 'Synchronize registered HEOS modules into the application catalog';

    public function handle(ModuleRegistry $registry): int
    {
        $options = new ModuleSyncOptions(
            dryRun: (bool) $this->option('dry-run'),
            moduleKey: $this->option('module') ?: null,
        );

        $result = $registry->syncToDatabase($options);

        if ($this->option('json')) {
            $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $result->success ? self::SUCCESS : self::FAILURE;
        }

        $this->components->info(sprintf(
            'Modules scanned: %d | Created: %d | Updated: %d | Unchanged: %d | Skipped: %d',
            $result->modulesScanned,
            $result->created,
            $result->updated,
            $result->unchanged,
            $result->skipped,
        ));

        foreach ($result->errors as $error) {
            $this->components->error(sprintf('[%s] %s', $error->code, $error->message));
        }

        foreach ($result->notes as $note) {
            $this->components->warn($note);
        }

        if (! $result->success) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Modules\Sdk;

use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Contracts\ModuleRegistryEventDispatcher;
use App\Modules\Sdk\Contracts\ModuleRegistryReader;
use App\Modules\Sdk\Contracts\ModuleSyncPort;
use App\Modules\Sdk\Data\ModuleSyncOptions;
use App\Modules\Sdk\Data\ModuleSyncResult;
use App\Modules\Sdk\Data\ModuleValidationReport;
use App\Modules\Sdk\Events\ModuleRegistryEvent;
use App\Modules\Sdk\Exceptions\DuplicateModuleKeyException;
use App\Modules\Sdk\Exceptions\InvalidModuleManifestException;

class ModuleRegistry implements ModuleRegistryReader
{
    /**
     * @var array<string, ApplicationModule>
     */
    private array $modules = [];

    public function __construct(
        private readonly ModuleManifestValidator $validator,
        private readonly ModuleRegistryEventDispatcher $events,
        private readonly ?ModuleSyncPort $syncPort = null,
    ) {
    }

    public function register(ApplicationModule $module): void
    {
        $key = $module->key();

        $this->events->dispatch(ModuleRegistryEvent::BEFORE_REGISTER, [
            'module' => $module,
            'key' => $key,
        ]);

        if (isset($this->modules[$key])) {
            throw new DuplicateModuleKeyException(sprintf('Module key "%s" is already registered.', $key));
        }

        $report = $this->validator->validateModule($module, array_keys($this->modules));

        if (! $report->isValid()) {
            $firstIssue = $report->issues[0];

            throw new InvalidModuleManifestException($firstIssue->message);
        }

        $this->modules[$key] = $module;

        $this->events->dispatch(ModuleRegistryEvent::AFTER_REGISTER, [
            'module' => $module,
            'key' => $key,
        ]);
    }

    /**
     * @return list<ApplicationModule>
     */
    public function all(): array
    {
        return array_values($this->modules);
    }

    public function findByKey(string $key): ?ApplicationModule
    {
        return $this->modules[$key] ?? null;
    }

    public function validate(): ModuleValidationReport
    {
        $this->events->dispatch(ModuleRegistryEvent::BEFORE_VALIDATE, [
            'modules' => $this->all(),
        ]);

        $report = $this->validator->validateRegistry($this->all());

        $this->events->dispatch(ModuleRegistryEvent::AFTER_VALIDATE, [
            'modules' => $this->all(),
            'report' => $report,
        ]);

        return $report;
    }

    public function syncToDatabase(?ModuleSyncOptions $options = null): ModuleSyncResult
    {
        if ($this->syncPort === null) {
            throw new \App\Modules\Sdk\Exceptions\ModuleSyncNotAvailableException;
        }

        $options ??= new ModuleSyncOptions;

        $this->events->dispatch(ModuleRegistryEvent::BEFORE_SYNC, [
            'modules' => $this->all(),
            'options' => $options,
        ]);

        $result = $this->syncPort->sync($this, $options);

        $this->events->dispatch(ModuleRegistryEvent::AFTER_SYNC, [
            'modules' => $this->all(),
            'options' => $options,
            'result' => $result,
        ]);

        return $result;
    }
}

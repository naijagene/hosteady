<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleSyncResult
{
    /**
     * @param  list<ModuleSyncChange>  $changes
     * @param  list<ModuleSyncError>  $errors
     * @param  list<string>  $notes
     */
    public function __construct(
        public int $modulesScanned,
        public int $created,
        public int $updated,
        public int $unchanged,
        public int $skipped,
        public array $changes,
        public array $errors,
        public array $notes,
        public bool $success,
    ) {
    }

    /**
     * @param  list<ModuleSyncError>  $errors
     */
    public static function failed(array $errors, int $modulesScanned = 0): self
    {
        return new self(
            modulesScanned: $modulesScanned,
            created: 0,
            updated: 0,
            unchanged: 0,
            skipped: 0,
            changes: [],
            errors: $errors,
            notes: [],
            success: false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'modules_scanned' => $this->modulesScanned,
            'created' => $this->created,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'skipped' => $this->skipped,
            'success' => $this->success,
            'changes' => array_map(
                fn (ModuleSyncChange $change) => [
                    'entity' => $change->entity,
                    'action' => $change->action,
                    'key' => $change->key,
                    'module_key' => $change->moduleKey,
                ],
                $this->changes,
            ),
            'errors' => array_map(
                fn (ModuleSyncError $error) => [
                    'code' => $error->code,
                    'message' => $error->message,
                    'module_key' => $error->moduleKey,
                ],
                $this->errors,
            ),
            'notes' => $this->notes,
        ];
    }
}

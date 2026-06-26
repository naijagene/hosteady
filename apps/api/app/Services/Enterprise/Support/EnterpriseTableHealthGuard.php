<?php

namespace App\Services\Enterprise\Support;

use Illuminate\Support\Facades\Schema;

class EnterpriseTableHealthGuard
{
    /**
     * @param  list<string>  $tables
     * @return list<string>
     */
    public function missingTables(array $tables, ?string $connection = null): array
    {
        $missing = [];

        foreach ($tables as $table) {
            if (! Schema::connection($connection)->hasTable($table)) {
                $missing[] = $table;
            }
        }

        return $missing;
    }

    public function missingTableWarning(string $tableName): string
    {
        return sprintf('Required table [%s] is missing. Run php artisan migrate.', $tableName);
    }

    /**
     * @param  list<string>  $requiredTables
     * @param  callable(): array<string, mixed>  $assess
     * @param  array<string, mixed>  $fallbackAssessment
     * @return array<string, mixed>
     */
    public function assessWhenTablesPresent(
        array $requiredTables,
        callable $assess,
        array $fallbackAssessment = [],
    ): array {
        $missing = $this->missingTables($requiredTables);

        if ($missing === []) {
            return $assess();
        }

        $warnings = array_map(fn (string $table): string => $this->missingTableWarning($table), $missing);

        return array_merge($fallbackAssessment, [
            'warnings' => array_values(array_merge(
                is_array($fallbackAssessment['warnings'] ?? null) ? $fallbackAssessment['warnings'] : [],
                $warnings,
            )),
            'status' => 'warning',
            'missing_tables' => $missing,
        ]);
    }
}

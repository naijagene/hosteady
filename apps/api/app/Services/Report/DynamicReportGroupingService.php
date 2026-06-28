<?php

namespace App\Services\Report;

use App\Modules\Sdk\Report\Data\ReportGroup;

class DynamicReportGroupingService
{
    /**
     * @param  list<ReportGroup>  $groups
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, list<array<string, mixed>>>
     */
    public function group(array $groups, array $rows): array
    {
        if ($groups === []) {
            return ['default' => $rows];
        }

        $grouped = [];

        foreach ($rows as $row) {
            $keyParts = [];
            foreach ($groups as $group) {
                $keyParts[] = (string) ($row[$group->fieldKey] ?? 'unknown');
            }
            $key = implode('::', $keyParts);
            $grouped[$key] ??= [];
            $grouped[$key][] = $row;
        }

        return $grouped;
    }
}

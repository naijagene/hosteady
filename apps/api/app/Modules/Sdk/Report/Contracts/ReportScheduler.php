<?php

namespace App\Modules\Sdk\Report\Contracts;

use App\Modules\Sdk\Report\Data\ReportScheduleDefinition;

interface ReportScheduler
{
    public function create(ReportScheduleDefinition $schedule): ReportScheduleDefinition;

    public function pause(string $schedulePublicId): ReportScheduleDefinition;

    public function resume(string $schedulePublicId): ReportScheduleDefinition;

    public function delete(string $schedulePublicId): void;

    /**
     * @return list<ReportScheduleDefinition>
     */
    public function list(string $moduleKey, string $reportKey): array;
}

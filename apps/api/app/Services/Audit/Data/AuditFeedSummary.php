<?php

namespace App\Services\Audit\Data;

readonly class AuditFeedSummary
{
    /**
     * @param  array<string, int>  $byCategory
     * @param  array<string, int>  $bySeverity
     * @param  list<array{action: string, count: int}>  $recentActions
     * @param  list<array{membership_public_id: string|null, user_public_id: string|null, display_name: string|null, count: int}>  $topActors
     */
    public function __construct(
        public string $occurredFrom,
        public string $occurredTo,
        public int $totalEvents,
        public array $byCategory,
        public array $bySeverity,
        public array $recentActions,
        public array $topActors,
    ) {
    }
}

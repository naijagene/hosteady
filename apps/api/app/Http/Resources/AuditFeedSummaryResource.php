<?php

namespace App\Http\Resources;

use App\Services\Audit\Data\AuditFeedSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditFeedSummary
 */
class AuditFeedSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AuditFeedSummary $summary */
        $summary = $this->resource;

        return [
            'occurred_from' => $summary->occurredFrom,
            'occurred_to' => $summary->occurredTo,
            'total_events' => $summary->totalEvents,
            'by_category' => $summary->byCategory,
            'by_severity' => $summary->bySeverity,
            'recent_actions' => $summary->recentActions,
            'top_actors' => $summary->topActors,
        ];
    }
}

<?php

namespace App\Services\Audit\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

readonly class AuditFeedPage
{
    /**
     * @param  Collection<int, \App\Models\AuditLog>|LengthAwarePaginator<int, \App\Models\AuditLog>  $items
     */
    public function __construct(
        public Collection|LengthAwarePaginator $items,
        public int $perPage,
        public bool $usesOffsetPagination,
        public ?string $nextCursor = null,
        public ?string $prevCursor = null,
        public bool $hasMore = false,
        public ?LengthAwarePaginator $offsetPaginator = null,
    ) {
    }
}

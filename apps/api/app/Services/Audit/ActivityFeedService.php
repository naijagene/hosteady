<?php

namespace App\Services\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditCategory;
use App\Enums\AuditRetentionClass;
use App\Enums\RoleStatus;
use App\Exceptions\Audit\AuditEventNotFoundException;
use App\Models\AuditLog;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Audit\Data\AuditFeedPage;
use App\Services\Audit\Data\AuditFeedSummary;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivityFeedService
{
    public function __construct(
        private readonly AuditCursorCodec $cursorCodec,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listEvents(TenantContext $context, array $filters = []): AuditFeedPage
    {
        if (array_key_exists('page', $filters)) {
            return $this->listEventsWithOffset($context, $filters);
        }

        return $this->listEventsWithCursor($context, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function summarize(TenantContext $context, array $filters = []): AuditFeedSummary
    {
        $occurredFrom = isset($filters['occurred_from'])
            ? Carbon::parse($filters['occurred_from'])->startOfDay()
            : now()->subDays(30)->startOfDay();

        $occurredTo = isset($filters['occurred_to'])
            ? Carbon::parse($filters['occurred_to'])->endOfDay()
            : now()->endOfDay();

        $summaryFilters = array_merge($filters, [
            'occurred_from' => $occurredFrom,
            'occurred_to' => $occurredTo,
        ]);

        $baseQuery = function () use ($context, $summaryFilters): Builder {
            $query = AuditLog::query()
                ->where('organization_id', $context->organization->id);

            $this->applyVisibilityScope($query, $context);
            $this->applyFilters($query, $context, $summaryFilters);

            return $query;
        };

        $totalEvents = (clone $baseQuery())->count();

        /** @var array<string, int> $byCategory */
        $byCategory = (clone $baseQuery())
            ->select('category', DB::raw('count(*) as aggregate'))
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->map(fn ($count) => (int) $count)
            ->all();

        /** @var array<string, int> $bySeverity */
        $bySeverity = (clone $baseQuery())
            ->select('severity', DB::raw('count(*) as aggregate'))
            ->groupBy('severity')
            ->pluck('aggregate', 'severity')
            ->map(fn ($count) => (int) $count)
            ->all();

        $recentActions = (clone $baseQuery())
            ->select('action', DB::raw('count(*) as aggregate'))
            ->groupBy('action')
            ->orderByDesc('aggregate')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'action' => $row->action instanceof AuditAction
                    ? $row->action->value
                    : (string) $row->getAttributes()['action'],
                'count' => (int) $row->aggregate,
            ])
            ->values()
            ->all();

        $topActors = (clone $baseQuery())
            ->select('actor_membership_id', DB::raw('count(*) as aggregate'))
            ->whereNotNull('actor_membership_id')
            ->groupBy('actor_membership_id')
            ->orderByDesc('aggregate')
            ->limit(10)
            ->get();

        $memberships = OrganizationMembership::query()
            ->with('user')
            ->whereIn('id', $topActors->pluck('actor_membership_id'))
            ->get()
            ->keyBy('id');

        $topActorsPayload = $topActors->map(function ($row) use ($memberships) {
            $membership = $memberships->get($row->actor_membership_id);

            return [
                'membership_public_id' => $membership?->public_id,
                'user_public_id' => $membership?->user?->public_id,
                'display_name' => $membership?->user?->display_name ?? $membership?->user?->name,
                'count' => (int) $row->aggregate,
            ];
        })->values()->all();

        return new AuditFeedSummary(
            occurredFrom: $occurredFrom->toIso8601String(),
            occurredTo: $occurredTo->toIso8601String(),
            totalEvents: $totalEvents,
            byCategory: $byCategory,
            bySeverity: $bySeverity,
            recentActions: $recentActions,
            topActors: $topActorsPayload,
        );
    }

    public function findEvent(TenantContext $context, string $eventPublicId): AuditLog
    {
        $query = AuditLog::query()
            ->with(['actorUser', 'actorMembership', 'organization', 'workspace'])
            ->where('public_id', $eventPublicId)
            ->where('organization_id', $context->organization->id);

        $this->applyVisibilityScope($query, $context);

        $event = $query->first();

        if ($event === null) {
            throw new AuditEventNotFoundException;
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function listEventsWithOffset(TenantContext $context, array $filters): AuditFeedPage
    {
        $query = $this->buildBaseQuery($context, $filters);
        $perPage = $this->resolvePerPage($filters);

        /** @var LengthAwarePaginator<int, AuditLog> $paginator */
        $paginator = $query->paginate($perPage);

        return new AuditFeedPage(
            items: collect($paginator->items()),
            perPage: $perPage,
            usesOffsetPagination: true,
            offsetPaginator: $paginator,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function listEventsWithCursor(TenantContext $context, array $filters): AuditFeedPage
    {
        $perPage = $this->resolvePerPage($filters);
        $ascending = ($filters['sort'] ?? 'occurred_at_desc') === 'occurred_at_asc';

        $query = $this->buildBaseQuery($context, $filters, applySort: false);

        if (! empty($filters['cursor'])) {
            $decoded = $this->cursorCodec->decode($context, (string) $filters['cursor']);
            $this->applyCursorConstraint($query, $decoded, $ascending);
        }

        if ($ascending) {
            $query->orderBy('occurred_at')->orderBy('id');
        } else {
            $query->orderByDesc('occurred_at')->orderByDesc('id');
        }

        /** @var Collection<int, AuditLog> $items */
        $items = $query->limit($perPage + 1)->get();
        $hasMore = $items->count() > $perPage;

        if ($hasMore) {
            $items = $items->take($perPage);
        }

        $nextCursor = null;

        if ($hasMore && $items->isNotEmpty()) {
            $last = $items->last();
            $nextCursor = $this->cursorCodec->encode(
                $context,
                $last->id,
                $last->occurred_at->toIso8601String(),
            );
        }

        return new AuditFeedPage(
            items: $items,
            perPage: $perPage,
            usesOffsetPagination: false,
            nextCursor: $nextCursor,
            hasMore: $hasMore,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildBaseQuery(
        TenantContext $context,
        array $filters,
        bool $applySort = true,
    ): Builder {
        $query = AuditLog::query()
            ->with(['actorUser', 'actorMembership', 'organization', 'workspace'])
            ->where('organization_id', $context->organization->id);

        $this->applyVisibilityScope($query, $context);
        $this->applyFilters($query, $context, $filters);

        if ($applySort) {
            $this->applySort($query, $filters);
        }

        return $query;
    }

    /**
     * @param  array{id: string, occurred_at: string}  $cursor
     */
    private function applyCursorConstraint(Builder $query, array $cursor, bool $ascending): void
    {
        $occurredAt = Carbon::parse($cursor['occurred_at']);

        if ($ascending) {
            $query->where(function (Builder $constraint) use ($cursor, $occurredAt) {
                $constraint->where('occurred_at', '>', $occurredAt)
                    ->orWhere(function (Builder $tieBreaker) use ($cursor, $occurredAt) {
                        $tieBreaker->where('occurred_at', '=', $occurredAt)
                            ->where('id', '>', $cursor['id']);
                    });
            });

            return;
        }

        $query->where(function (Builder $constraint) use ($cursor, $occurredAt) {
            $constraint->where('occurred_at', '<', $occurredAt)
                ->orWhere(function (Builder $tieBreaker) use ($cursor, $occurredAt) {
                    $tieBreaker->where('occurred_at', '=', $occurredAt)
                        ->where('id', '<', $cursor['id']);
                });
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applySort(Builder $query, array $filters): void
    {
        if (($filters['sort'] ?? 'occurred_at_desc') === 'occurred_at_asc') {
            $query->orderBy('occurred_at')->orderBy('id');

            return;
        }

        $query->orderByDesc('occurred_at')->orderByDesc('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolvePerPage(array $filters): int
    {
        $limit = $filters['limit'] ?? $filters['per_page'] ?? 25;

        return min(max((int) $limit, 1), 100);
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, TenantContext $context, array $filters): void
    {
        if (! empty($filters['category'])) {
            $categories = is_array($filters['category'])
                ? $filters['category']
                : [(string) $filters['category']];

            $query->whereIn('category', $categories);
        }

        if (! empty($filters['severity'])) {
            $severities = is_array($filters['severity'])
                ? $filters['severity']
                : [(string) $filters['severity']];

            $query->whereIn('severity', $severities);
        }

        if (! empty($filters['action'])) {
            $actions = is_array($filters['action'])
                ? $filters['action']
                : [(string) $filters['action']];

            $query->whereIn('action', $actions);
        }

        if (! empty($filters['actor_user_public_id'])) {
            $actorUserId = User::query()
                ->where('public_id', (string) $filters['actor_user_public_id'])
                ->value('id');

            if ($actorUserId !== null) {
                $query->where('actor_user_id', $actorUserId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($filters['actor_membership_public_id'])) {
            $actorMembershipId = OrganizationMembership::query()
                ->where('public_id', (string) $filters['actor_membership_public_id'])
                ->where('organization_id', $context->organization->id)
                ->value('id');

            if ($actorMembershipId !== null) {
                $query->where('actor_membership_id', $actorMembershipId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', (string) $filters['entity_type']);
        }

        if (! empty($filters['entity_public_id'])) {
            $query->where('entity_public_id', (string) $filters['entity_public_id']);
        }

        if (! empty($filters['workspace_public_id'])) {
            $workspaceId = Workspace::query()
                ->where('public_id', (string) $filters['workspace_public_id'])
                ->where('organization_id', $context->organization->id)
                ->value('id');

            if ($workspaceId !== null) {
                $query->where('workspace_id', $workspaceId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($filters['request_id'])) {
            $query->where('request_id', (string) $filters['request_id']);
        }

        if (! empty($filters['retention_class'])) {
            $retentionClasses = is_array($filters['retention_class'])
                ? $filters['retention_class']
                : [(string) $filters['retention_class']];

            $query->whereIn('retention_class', $retentionClasses);
        }

        if (! empty($filters['occurred_from'])) {
            $query->where('occurred_at', '>=', $filters['occurred_from']);
        }

        if (! empty($filters['occurred_to'])) {
            $query->where('occurred_at', '<=', $filters['occurred_to']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery->where('summary', 'like', '%'.$search.'%')
                    ->orWhere('entity_label', 'like', '%'.$search.'%');
            });
        }
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    private function applyVisibilityScope(Builder $query, TenantContext $context): void
    {
        if ($this->canViewEphemeralSecurityEvents($context)) {
            return;
        }

        $query->where(function (Builder $visibilityQuery) {
            $visibilityQuery->where('category', '!=', AuditCategory::Security->value)
                ->orWhere('retention_class', '!=', AuditRetentionClass::Ephemeral->value);
        });
    }

    private function canViewEphemeralSecurityEvents(TenantContext $context): bool
    {
        $roleIds = $context->membership->memberRoles()->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return false;
        }

        return Role::query()
            ->whereIn('id', $roleIds)
            ->where('organization_id', $context->organization->id)
            ->where('status', RoleStatus::Active)
            ->whereNull('deleted_at')
            ->whereIn('key', ['owner', 'administrator'])
            ->exists();
    }
}

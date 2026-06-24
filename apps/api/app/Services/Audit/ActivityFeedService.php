<?php

namespace App\Services\Audit;

use App\Enums\AuditCategory;
use App\Enums\AuditRetentionClass;
use App\Enums\RoleStatus;
use App\Exceptions\Audit\AuditEventNotFoundException;
use App\Models\AuditLog;
use App\Models\Role;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ActivityFeedService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function listEvents(TenantContext $context, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->with(['actorUser', 'actorMembership', 'organization', 'workspace'])
            ->where('organization_id', $context->organization->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        $this->applyVisibilityScope($query, $context);
        $this->applyFilters($query, $filters);

        $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), 100);

        return $query->paginate($perPage);
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
     * @param  Builder<AuditLog>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['category'])) {
            $categories = is_array($filters['category'])
                ? $filters['category']
                : explode(',', (string) $filters['category']);

            $query->whereIn('category', array_map('trim', $categories));
        }

        if (! empty($filters['action'])) {
            $query->where('action', (string) $filters['action']);
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', (string) $filters['severity']);
        }

        if (! empty($filters['actor_user_public_id'])) {
            $query->whereHas('actorUser', function (Builder $actorQuery) use ($filters) {
                $actorQuery->where('public_id', (string) $filters['actor_user_public_id']);
            });
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', (string) $filters['entity_type']);
        }

        if (! empty($filters['entity_public_id'])) {
            $query->where('entity_public_id', (string) $filters['entity_public_id']);
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

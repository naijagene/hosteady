<?php

namespace App\Services\DataRepository;

use App\Models\EnterpriseEntityRecord;
use App\Modules\Sdk\DataRepository\Contracts\EntityRecordQueryProvider;
use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordFilter;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryResult;
use App\Modules\Sdk\DataRepository\Data\EntityRecordSort;
use App\Modules\Sdk\DataRepository\Enums\EntityRecordFilterOperator;

class EnterpriseEntityRecordQueryService implements EntityRecordQueryProvider
{
    public function __construct(
        private readonly EnterpriseEntityRecordAuditRecorder $auditRecorder,
    ) {
    }

    public function query(
        string $organizationId,
        ?string $workspaceId,
        EntityRecordQueryRequest $request,
    ): EntityRecordQueryResult {
        $query = EnterpriseEntityRecord::query()
            ->where('organization_id', $organizationId)
            ->where('module_key', $request->moduleKey)
            ->where('entity_key', $request->entityKey);

        if ($workspaceId !== null) {
            $query->where(function ($q) use ($workspaceId) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        if ($request->includeDeleted) {
            $query->withTrashed();
        }

        $models = $query->get();
        $records = $models->map(fn (EnterpriseEntityRecord $model) => EnterpriseEntityRecordMapper::toRecord($model))->all();
        $records = $this->applyFilters($records, $request->filters);

        if ($request->search !== null && $request->search !== '') {
            $records = $this->applySearch($records, $request->search);
        }

        $records = $this->applySorts($records, $request->sorts);

        $total = count($records);
        $totalPages = (int) ceil($total / max(1, $request->perPage));
        $offset = ($request->page - 1) * $request->perPage;
        $pageRecords = array_slice($records, $offset, $request->perPage);

        $result = new EntityRecordQueryResult(
            moduleKey: $request->moduleKey,
            entityKey: $request->entityKey,
            records: $pageRecords,
            total: $total,
            page: $request->page,
            perPage: $request->perPage,
            totalPages: $totalPages,
            appliedFilters: array_map(fn (EntityRecordFilter $filter) => $filter->toArray(), $request->filters),
            appliedSorts: array_map(fn (EntityRecordSort $sort) => $sort->toArray(), $request->sorts),
            metadata: ['source' => 'php_query'],
        );

        $this->auditRecorder->recordQueried($request->moduleKey, $request->entityKey, $total);

        return $result;
    }

    /**
     * @param  list<EntityRecord>  $records
     * @param  list<EntityRecordFilter>  $filters
     * @return list<EntityRecord>
     */
    private function applyFilters(array $records, array $filters): array
    {
        if ($filters === []) {
            return $records;
        }

        return array_values(array_filter(
            $records,
            fn (EntityRecord $record) => $this->matchesAllFilters($record, $filters),
        ));
    }

    /**
     * @param  list<EntityRecordFilter>  $filters
     */
    private function matchesAllFilters(EntityRecord $record, array $filters): bool
    {
        foreach ($filters as $filter) {
            if (! $this->matchesFilter($record, $filter)) {
                return false;
            }
        }

        return true;
    }

    private function matchesFilter(EntityRecord $record, EntityRecordFilter $filter): bool
    {
        $value = $record->recordData->values[$filter->field] ?? null;
        $operator = EntityRecordFilterOperator::tryFrom($filter->operator) ?? EntityRecordFilterOperator::Equals;

        return match ($operator) {
            EntityRecordFilterOperator::Equals => $value == $filter->value,
            EntityRecordFilterOperator::NotEquals => $value != $filter->value,
            EntityRecordFilterOperator::Contains => is_string($value) && is_string($filter->value) && str_contains(strtolower($value), strtolower($filter->value)),
            EntityRecordFilterOperator::StartsWith => is_string($value) && is_string($filter->value) && str_starts_with(strtolower($value), strtolower($filter->value)),
            EntityRecordFilterOperator::EndsWith => is_string($value) && is_string($filter->value) && str_ends_with(strtolower($value), strtolower($filter->value)),
            EntityRecordFilterOperator::GreaterThan => is_numeric($value) && is_numeric($filter->value) && (float) $value > (float) $filter->value,
            EntityRecordFilterOperator::GreaterThanOrEqual => is_numeric($value) && is_numeric($filter->value) && (float) $value >= (float) $filter->value,
            EntityRecordFilterOperator::LessThan => is_numeric($value) && is_numeric($filter->value) && (float) $value < (float) $filter->value,
            EntityRecordFilterOperator::LessThanOrEqual => is_numeric($value) && is_numeric($filter->value) && (float) $value <= (float) $filter->value,
            EntityRecordFilterOperator::In => is_array($filter->value) && in_array($value, $filter->value, true),
            EntityRecordFilterOperator::NotIn => is_array($filter->value) && ! in_array($value, $filter->value, true),
            EntityRecordFilterOperator::IsNull => $value === null,
            EntityRecordFilterOperator::IsNotNull => $value !== null,
        };
    }

    /**
     * @param  list<EntityRecord>  $records
     * @return list<EntityRecord>
     */
    private function applySearch(array $records, string $search): array
    {
        $needle = mb_strtolower($search);

        return array_values(array_filter($records, function (EntityRecord $record) use ($needle): bool {
            if ($record->searchText !== null && str_contains($record->searchText, $needle)) {
                return true;
            }

            foreach ($record->recordData->values as $value) {
                if ($value !== null && str_contains(mb_strtolower((string) $value), $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @param  list<EntityRecord>  $records
     * @param  list<EntityRecordSort>  $sorts
     * @return list<EntityRecord>
     */
    private function applySorts(array $records, array $sorts): array
    {
        if ($sorts === []) {
            usort($records, fn (EntityRecord $a, EntityRecord $b) => strcmp((string) $a->createdAt, (string) $b->createdAt));

            return $records;
        }

        usort($records, function (EntityRecord $a, EntityRecord $b) use ($sorts): int {
            foreach ($sorts as $sort) {
                $left = $a->recordData->values[$sort->field] ?? $a->{$sort->field} ?? null;
                $right = $b->recordData->values[$sort->field] ?? $b->{$sort->field} ?? null;
                $comparison = $left <=> $right;

                if ($comparison !== 0) {
                    return $sort->direction === 'desc' ? -$comparison : $comparison;
                }
            }

            return 0;
        });

        return $records;
    }
}

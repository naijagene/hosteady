<?php

namespace App\Services\Enterprise\ReferenceData;

use App\Models\ReferenceCatalog;
use App\Models\ReferenceItem;
use App\Modules\Sdk\Enterprise\Contracts\ReferenceDataPort;
use App\Modules\Sdk\Enterprise\Data\ReferenceCatalogData;
use App\Modules\Sdk\Enterprise\Data\ReferenceItemData;
use App\Services\Enterprise\Audit\EnterpriseReferenceAuditRecorder;
use Illuminate\Support\Str;

class LaravelReferenceDataAdapter implements ReferenceDataPort
{
    public function __construct(
        private readonly ReferenceCatalogRegistry $registry,
        private readonly EnterpriseReferenceAuditRecorder $auditRecorder,
    ) {
    }

    public function catalog(string $catalogKey): ?ReferenceCatalogData
    {
        $model = ReferenceCatalog::query()->where('key', $catalogKey)->first();

        if ($model !== null) {
            return new ReferenceCatalogData(
                key: $model->key,
                name: $model->name,
                version: $model->version,
                moduleKey: $model->module_key,
                description: $model->description,
            );
        }

        return $this->registry->catalog($catalogKey);
    }

    public function listItems(string $catalogKey, bool $activeOnly = true): array
    {
        $catalog = ReferenceCatalog::query()->where('key', $catalogKey)->first();

        if ($catalog !== null) {
            $query = ReferenceItem::query()
                ->where('reference_catalog_id', $catalog->id)
                ->orderBy('sort_order')
                ->orderBy('code');

            if ($activeOnly) {
                $query->where('active', true);
            }

            return $query->get()->map(fn (ReferenceItem $item) => new ReferenceItemData(
                catalogKey: $catalogKey,
                code: $item->code,
                label: $item->label,
                metadata: $item->metadata ?? [],
                sortOrder: $item->sort_order,
                active: $item->active,
            ))->all();
        }

        $items = $this->registry->items($catalogKey);

        if ($activeOnly) {
            $items = array_values(array_filter($items, fn (ReferenceItemData $item) => $item->active));
        }

        return $items;
    }

    public function findItem(string $catalogKey, string $code): ?ReferenceItemData
    {
        foreach ($this->listItems($catalogKey, activeOnly: false) as $item) {
            if ($item->code === $code) {
                return $item;
            }
        }

        return null;
    }

    public function registerCatalog(ReferenceCatalogData $catalog): void
    {
        $this->registry->register($catalog, []);
        $this->auditRecorder->recordCatalogRegistered($catalog);
    }
}

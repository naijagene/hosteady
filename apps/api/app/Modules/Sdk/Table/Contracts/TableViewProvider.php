<?php

namespace App\Modules\Sdk\Table\Contracts;

use App\Modules\Sdk\Table\Data\TableView;

interface TableViewProvider
{
    /**
     * @return list<TableView>
     */
    public function listViews(string $moduleKey, string $tableKey, string $organizationId, ?string $workspaceId = null): array;

    public function saveView(TableView $view): TableView;

    public function deleteView(string $viewPublicId, string $organizationId, ?string $workspaceId = null): void;
}

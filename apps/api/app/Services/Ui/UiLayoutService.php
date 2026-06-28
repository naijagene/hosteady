<?php

namespace App\Services\Ui;

use App\Models\UiLayout;
use App\Modules\Sdk\Ui\Contracts\UiLayoutProvider;
use App\Modules\Sdk\Ui\Data\UiLayout as UiLayoutDto;
use App\Modules\Sdk\Ui\Enums\UiLayoutType;
use App\Modules\Sdk\Ui\Enums\UiPageStatus;
use App\Modules\Sdk\Ui\Exceptions\UiRegistryException;
use Illuminate\Support\Str;

class UiLayoutService implements UiLayoutProvider
{
    public function __construct(
        private readonly UiAuditRecorder $auditRecorder,
        private readonly UiTableHealthSupport $tableHealthSupport,
    ) {
    }

    /** @return list<UiLayoutDto> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        if (! $this->tableHealthSupport->isTablePresent('ui_layouts')) {
            return [];
        }

        $query = UiLayout::query()
            ->orderBy('name')
            ->limit($limit);

        UiMapper::applyOrganizationScope($query, $organizationId);
        UiMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (UiLayout $model) => UiMapper::toLayout($model))->all();
    }

    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, UiLayoutDto $layout): UiLayoutDto
    {
        $layout = $this->resolveLayoutSource($layout);
        $this->assertNotDuplicate($organizationId, $workspaceId, $layout);

        $model = UiLayout::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'application_id' => $applicationId,
            'module_key' => $layout->moduleKey,
            'layout_key' => $layout->layoutKey,
            'name' => $layout->name !== '' ? $layout->name : $layout->layoutKey,
            'description' => $layout->description,
            'layout_type' => $layout->layoutType !== '' ? $layout->layoutType : UiLayoutType::SingleColumn->value,
            'status' => $layout->status !== '' ? $layout->status : UiPageStatus::Published->value,
            'regions_json' => $layout->regions,
            'breakpoints_json' => $layout->breakpoints,
            'metadata' => $layout->metadata,
        ]);

        $created = UiMapper::toLayout($model);
        $this->auditRecorder->recordLayoutRegistered($created);

        return $created;
    }

    /**
     * @param  UiLayoutDto|array<string, mixed>  $source
     */
    public function registerFromSource(string $organizationId, ?string $workspaceId, ?string $applicationId, mixed $source): UiLayoutDto
    {
        return $this->register($organizationId, $workspaceId, $applicationId, $this->resolveLayoutSource($source));
    }

    private function assertNotDuplicate(string $organizationId, ?string $workspaceId, UiLayoutDto $layout): void
    {
        $query = UiLayout::query()->where('layout_key', $layout->layoutKey);

        if ($layout->moduleKey !== null && $layout->moduleKey !== '') {
            $query->where('module_key', $layout->moduleKey);
        }

        UiMapper::applyOrganizationScope($query, $organizationId);
        UiMapper::applyWorkspaceScope($query, $workspaceId);

        if ($query->exists()) {
            throw new UiRegistryException(sprintf('UI layout [%s] is already registered.', $layout->layoutKey));
        }
    }

    /**
     * @param  UiLayoutDto|array<string, mixed>  $source
     */
    private function resolveLayoutSource(mixed $source): UiLayoutDto
    {
        if ($source instanceof UiLayoutDto) {
            return $source;
        }

        if (is_array($source)) {
            return UiLayoutDto::fromArray($source);
        }

        throw new UiRegistryException('Unsupported UI layout source.');
    }
}

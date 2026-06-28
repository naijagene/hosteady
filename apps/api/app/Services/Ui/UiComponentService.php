<?php

namespace App\Services\Ui;

use App\Models\UiComponent;
use App\Modules\Sdk\Ui\Contracts\UiComponentProvider;
use App\Modules\Sdk\Ui\Data\UiComponent as UiComponentDto;
use App\Modules\Sdk\Ui\Enums\UiComponentType;
use App\Modules\Sdk\Ui\Enums\UiPageStatus;
use App\Modules\Sdk\Ui\Exceptions\UiRegistryException;
use Illuminate\Support\Str;

class UiComponentService implements UiComponentProvider
{
    public function __construct(
        private readonly UiAuditRecorder $auditRecorder,
        private readonly UiTableHealthSupport $tableHealthSupport,
    ) {
    }

    /** @return list<UiComponentDto> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        if (! $this->tableHealthSupport->isTablePresent('ui_components')) {
            return [];
        }

        $query = UiComponent::query()
            ->orderBy('name')
            ->limit($limit);

        UiMapper::applyOrganizationScope($query, $organizationId);
        UiMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (UiComponent $model) => UiMapper::toComponent($model))->all();
    }

    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, UiComponentDto $component): UiComponentDto
    {
        $component = $this->resolveComponentSource($component);
        $this->assertNotDuplicate($organizationId, $workspaceId, $component);

        $model = UiComponent::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'application_id' => $applicationId,
            'module_key' => $component->moduleKey,
            'component_key' => $component->componentKey,
            'name' => $component->name !== '' ? $component->name : $component->componentKey,
            'description' => $component->description,
            'component_type' => $component->componentType !== '' ? $component->componentType : UiComponentType::Custom->value,
            'status' => $component->status !== '' ? $component->status : UiPageStatus::Published->value,
            'binding_type' => $component->bindingType,
            'binding_config' => $component->bindingConfig,
            'actions_json' => $component->actions,
            'conditions_json' => $component->conditions,
            'metadata' => $component->metadata,
        ]);

        $created = UiMapper::toComponent($model);
        $this->auditRecorder->recordComponentRegistered($created);

        return $created;
    }

    /**
     * @param  UiComponentDto|array<string, mixed>  $source
     */
    public function registerFromSource(string $organizationId, ?string $workspaceId, ?string $applicationId, mixed $source): UiComponentDto
    {
        return $this->register($organizationId, $workspaceId, $applicationId, $this->resolveComponentSource($source));
    }

    private function assertNotDuplicate(string $organizationId, ?string $workspaceId, UiComponentDto $component): void
    {
        $query = UiComponent::query()->where('component_key', $component->componentKey);

        if ($component->moduleKey !== null && $component->moduleKey !== '') {
            $query->where('module_key', $component->moduleKey);
        }

        UiMapper::applyOrganizationScope($query, $organizationId);
        UiMapper::applyWorkspaceScope($query, $workspaceId);

        if ($query->exists()) {
            throw new UiRegistryException(sprintf('UI component [%s] is already registered.', $component->componentKey));
        }
    }

    /**
     * @param  UiComponentDto|array<string, mixed>  $source
     */
    private function resolveComponentSource(mixed $source): UiComponentDto
    {
        if ($source instanceof UiComponentDto) {
            return $source;
        }

        if (is_array($source)) {
            return UiComponentDto::fromArray($source);
        }

        throw new UiRegistryException('Unsupported UI component source.');
    }
}

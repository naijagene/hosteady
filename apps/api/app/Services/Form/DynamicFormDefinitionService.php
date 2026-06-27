<?php

namespace App\Services\Form;

use App\Models\FormDefinition as FormDefinitionModel;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Exceptions\FormNotFoundException;
use App\Support\Tenant\TenantContext;

class DynamicFormDefinitionService
{
    public function __construct(
        private readonly DynamicFormRegistryService $registryService,
    ) {
    }

    public function create(FormDefinition $definition): FormDefinition
    {
        return $this->registryService->register($definition);
    }

    public function update(FormDefinition $definition): FormDefinition
    {
        return $this->registryService->update($definition);
    }

    public function delete(string $moduleKey, string $formKey): void
    {
        $model = $this->resolveModel($moduleKey, $formKey);
        $model->delete();
    }

    public function find(string $moduleKey, string $formKey): FormDefinition
    {
        $definition = $this->registryService->find($moduleKey, $formKey);

        if ($definition === null) {
            throw new FormNotFoundException(sprintf('Form [%s.%s] was not found.', $moduleKey, $formKey));
        }

        return $definition;
    }

    public function findByPublicId(string $publicId): FormDefinition
    {
        $definition = $this->registryService->findByPublicId($publicId);

        if ($definition === null) {
            throw new FormNotFoundException(sprintf('Form [%s] was not found.', $publicId));
        }

        return $definition;
    }

    /**
     * @return list<FormDefinition>
     */
    public function list(?string $moduleKey = null): array
    {
        return $this->registryService->list($moduleKey);
    }

    /**
     * @return list<FormDefinition>
     */
    public function listByEntity(string $moduleKey, string $entityKey): array
    {
        return $this->registryService->findByEntity($moduleKey, $entityKey);
    }

    /**
     * @param  list<array<string, mixed>>  $forms
     * @return list<FormDefinition>
     */
    public function registerFromManifest(array $forms, string $moduleKey): array
    {
        return $this->registryService->registerFromManifestForms($forms, $moduleKey);
    }

    private function resolveModel(string $moduleKey, string $formKey): FormDefinitionModel
    {
        $definition = $this->registryService->find($moduleKey, $formKey);

        if ($definition === null) {
            throw new FormNotFoundException(sprintf('Form [%s.%s] was not found.', $moduleKey, $formKey));
        }

        $query = FormDefinitionModel::query()->where('public_id', $definition->publicId);

        if (app()->bound(TenantContext::class)) {
            $context = app(TenantContext::class);
            $query->where('organization_id', $context->organization->id);

            if ($context->workspace !== null) {
                $query->where('workspace_id', $context->workspace->id);
            } else {
                $query->whereNull('workspace_id');
            }
        }

        $model = $query->first();

        if ($model === null) {
            throw new FormNotFoundException(sprintf('Form [%s.%s] was not found.', $moduleKey, $formKey));
        }

        return $model;
    }
}

<?php

namespace App\Services\Form;

use App\Models\FormDefinition as FormDefinitionModel;
use App\Modules\Sdk\Form\Contracts\FormRegistry;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Exceptions\FormNotFoundException;
use App\Modules\Sdk\Form\Exceptions\FormRegistryException;
use Illuminate\Support\Facades\DB;

class DynamicFormRegistryService implements FormRegistry
{
    public function __construct(
        private readonly DynamicFormValidationService $validator,
        private readonly DynamicFormAuditRecorder $auditRecorder,
    ) {
    }

    public function register(mixed $source): FormDefinition
    {
        $definition = $this->resolveDefinitionSource($source);
        $this->validator->assertValid($definition);

        if (FormDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->where('form_key', $definition->formKey)
            ->whereNull('organization_id')
            ->whereNull('workspace_id')
            ->exists()) {
            throw new FormRegistryException(sprintf(
                'Form definition [%s.%s] is already registered.',
                $definition->moduleKey,
                $definition->formKey,
            ));
        }

        return DB::transaction(function () use ($definition) {
            $model = new FormDefinitionModel;
            DynamicFormMapper::applyDefinition($model, $definition);
            $model->save();

            $this->auditRecorder->recordDefinitionRegistered($model);

            return DynamicFormMapper::toDefinition($model);
        });
    }

    public function update(FormDefinition $definition): FormDefinition
    {
        $this->validator->assertValid($definition);

        $model = FormDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->where('form_key', $definition->formKey)
            ->first();

        if ($model === null) {
            throw new FormNotFoundException(sprintf(
                'Form definition [%s.%s] was not found.',
                $definition->moduleKey,
                $definition->formKey,
            ));
        }

        return DB::transaction(function () use ($model, $definition) {
            DynamicFormMapper::applyDefinition($model, $definition);
            $model->save();

            $this->auditRecorder->recordDefinitionUpdated($model);

            return DynamicFormMapper::toDefinition($model);
        });
    }

    public function find(string $moduleKey, string $formKey): ?FormDefinition
    {
        $model = FormDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('form_key', $formKey)
            ->first();

        return $model === null ? null : DynamicFormMapper::toDefinition($model);
    }

    public function findByPublicId(string $publicId): ?FormDefinition
    {
        $model = FormDefinitionModel::query()->where('public_id', $publicId)->first();

        return $model === null ? null : DynamicFormMapper::toDefinition($model);
    }

    /**
     * @return list<FormDefinition>
     */
    public function list(?string $moduleKey = null): array
    {
        $query = FormDefinitionModel::query()->orderBy('module_key')->orderBy('form_key');

        if ($moduleKey !== null) {
            $query->where('module_key', $moduleKey);
        }

        return $query->get()
            ->map(fn (FormDefinitionModel $model) => DynamicFormMapper::toDefinition($model))
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $forms
     * @return list<FormDefinition>
     */
    public function registerFromManifestForms(array $forms, string $moduleKey): array
    {
        $registered = [];

        foreach ($forms as $form) {
            if (! is_array($form)) {
                continue;
            }

            $payload = array_merge($form, ['module_key' => $moduleKey]);
            $formKey = (string) ($payload['form_key'] ?? $payload['key'] ?? '');

            if ($formKey === '') {
                continue;
            }

            if ($this->find($moduleKey, $formKey) !== null) {
                continue;
            }

            $registered[] = $this->register(FormDefinition::fromArray($payload));
        }

        return $registered;
    }

    public function findModel(string $moduleKey, string $formKey): ?FormDefinitionModel
    {
        return FormDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('form_key', $formKey)
            ->first();
    }

    private function resolveDefinitionSource(mixed $source): FormDefinition
    {
        if ($source instanceof FormDefinition) {
            return $source;
        }

        if (is_array($source)) {
            return FormDefinition::fromArray($source);
        }

        throw new FormRegistryException('Unsupported form definition source.');
    }
}

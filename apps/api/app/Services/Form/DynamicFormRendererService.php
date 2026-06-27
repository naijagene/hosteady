<?php

namespace App\Services\Form;

use App\Modules\Sdk\Form\Contracts\FormConditionEvaluator;
use App\Modules\Sdk\Form\Contracts\FormFieldResolver;
use App\Modules\Sdk\Form\Contracts\FormRenderer;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormReference;
use App\Modules\Sdk\Form\Data\FormTab;
use App\Modules\Sdk\Form\Enums\FormLayoutType;
use App\Support\Tenant\TenantContext;

class DynamicFormRendererService implements FormRenderer
{
    public function __construct(
        private readonly DynamicFormFieldResolver $fieldResolver,
        private readonly DynamicFormActionService $actionService,
        private readonly FormConditionEvaluator $conditionEvaluator,
    ) {
    }

    public function render(FormDefinition $definition, array $context = []): array
    {
        $values = is_array($context['values'] ?? null) ? $context['values'] : [];
        $permissions = is_array($context['permissions'] ?? null) ? $context['permissions'] : $this->defaultPermissions();
        $runtimeContext = $this->buildRuntimeContext($context);

        $fields = $this->fieldResolver->resolve($definition, $context);
        $actions = $this->actionService->resolve($definition, $values, $context);
        $tabs = $this->resolveTabs($definition);
        $visibleConditions = $this->filterVisibleConditions($definition, $values, $context);

        return [
            'metadata' => array_merge($definition->metadata, [
                'module_key' => $definition->moduleKey,
                'form_key' => $definition->formKey,
                'public_id' => $definition->publicId,
                'name' => $definition->name,
                'description' => $definition->description,
                'type' => $definition->type,
                'status' => $definition->status,
                'visibility' => $definition->visibility,
            ]),
            'layout' => $definition->layout?->toArray(),
            'sections' => array_map(fn ($section) => $section->toArray(), $definition->sections),
            'tabs' => array_map(fn (FormTab $tab) => $tab->toArray(), $tabs),
            'fields' => array_map(fn ($field) => $field->toArray(), $fields),
            'actions' => array_map(fn ($action) => $action->toArray(), $actions),
            'conditions' => array_map(fn ($condition) => $condition->toArray(), $visibleConditions),
            'validation_rules' => array_map(fn ($rule) => $rule->toArray(), $definition->validationRules),
            'runtime_context' => $runtimeContext,
            'permissions' => $permissions,
            'entity_reference' => $this->entityReference($definition),
        ];
    }

    /**
     * @return list<FormTab>
     */
    private function resolveTabs(FormDefinition $definition): array
    {
        $layoutConfig = $definition->layout?->config ?? [];

        if ($definition->layout?->type !== FormLayoutType::Tabs->value) {
            return [];
        }

        $tabs = [];
        foreach (is_array($layoutConfig['tabs'] ?? null) ? $layoutConfig['tabs'] : [] as $tab) {
            if (is_array($tab)) {
                $tabs[] = FormTab::fromArray($tab);
            }
        }

        return $tabs;
    }

    /**
     * @return list<\App\Modules\Sdk\Form\Data\FormCondition>
     */
    private function filterVisibleConditions(FormDefinition $definition, array $values, array $context): array
    {
        $visible = [];

        foreach ($definition->conditions as $condition) {
            if ($this->conditionEvaluator->evaluate($condition, $values, $context)) {
                $visible[] = $condition;
            }
        }

        return $visible;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRuntimeContext(array $context): array
    {
        $runtime = [
            'values' => is_array($context['values'] ?? null) ? $context['values'] : [],
            'entity_public_id' => $context['entity_public_id'] ?? null,
            'draft_id' => $context['draft_id'] ?? null,
            'mode' => $context['mode'] ?? 'default',
        ];

        if (app()->bound(TenantContext::class)) {
            $tenant = app(TenantContext::class);
            $runtime['organization_public_id'] = $tenant->organizationPublicId;
            $runtime['workspace_public_id'] = $tenant->workspacePublicId;
            $runtime['user_public_id'] = $tenant->user->public_id ?? null;
        }

        return array_merge($runtime, is_array($context['runtime'] ?? null) ? $context['runtime'] : []);
    }

    /**
     * @return array<string, bool>
     */
    private function defaultPermissions(): array
    {
        return [
            'read' => true,
            'submit' => true,
            'draft' => true,
            'manage' => false,
        ];
    }

    private function entityReference(FormDefinition $definition): ?array
    {
        if ($definition->entityKey === null) {
            return null;
        }

        return (new FormReference(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            publicId: $definition->publicId,
            entityKey: $definition->entityKey,
            label: $definition->name,
            organizationId: $definition->organizationId,
            workspaceId: $definition->workspaceId,
        ))->toArray();
    }
}

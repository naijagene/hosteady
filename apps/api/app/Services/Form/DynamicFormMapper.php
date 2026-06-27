<?php

namespace App\Services\Form;

use App\Models\FormDefinition as FormDefinitionModel;
use App\Models\FormDraft;
use App\Models\FormSubmission;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormDraftReference;
use App\Modules\Sdk\Form\Data\FormField;
use App\Modules\Sdk\Form\Data\FormFieldValidationRule;
use App\Modules\Sdk\Form\Data\FormAction;
use App\Modules\Sdk\Form\Data\FormCondition;
use App\Modules\Sdk\Form\Data\FormLayout;
use App\Modules\Sdk\Form\Data\FormSection;

class DynamicFormMapper
{
    public static function toDefinition(FormDefinitionModel $model): FormDefinition
    {
        $sections = [];
        foreach (is_array($model->sections_json) ? $model->sections_json : [] as $section) {
            if (is_array($section)) {
                $sections[] = FormSection::fromArray($section);
            }
        }

        $fields = [];
        foreach (is_array($model->fields_json) ? $model->fields_json : [] as $field) {
            if (is_array($field)) {
                $fields[] = FormField::fromArray($field);
            }
        }

        $actions = [];
        foreach (is_array($model->actions_json) ? $model->actions_json : [] as $action) {
            if (is_array($action)) {
                $actions[] = FormAction::fromArray($action);
            }
        }

        $conditions = [];
        foreach (is_array($model->conditions_json) ? $model->conditions_json : [] as $condition) {
            if (is_array($condition)) {
                $conditions[] = FormCondition::fromArray($condition);
            }
        }

        $validationRules = [];
        foreach (is_array($model->validation_rules_json) ? $model->validation_rules_json : [] as $rule) {
            if (is_array($rule)) {
                $validationRules[] = FormFieldValidationRule::fromArray($rule);
            }
        }

        $layout = null;
        if (is_array($model->layout_json) && $model->layout_json !== []) {
            $layout = FormLayout::fromArray($model->layout_json);
        }

        return new FormDefinition(
            moduleKey: $model->module_key,
            formKey: $model->form_key,
            name: $model->name,
            publicId: $model->public_id,
            organizationId: $model->organization_id,
            workspaceId: $model->workspace_id,
            entityKey: $model->entity_key,
            description: $model->description,
            type: (string) $model->type,
            status: (string) $model->status,
            visibility: (string) $model->visibility,
            layout: $layout,
            sections: $sections,
            fields: $fields,
            actions: $actions,
            conditions: $conditions,
            validationRules: $validationRules,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function applyDefinition(FormDefinitionModel $model, FormDefinition $definition): void
    {
        $model->fill([
            'module_key' => $definition->moduleKey,
            'form_key' => $definition->formKey,
            'name' => $definition->name,
            'entity_key' => $definition->entityKey,
            'description' => $definition->description,
            'type' => $definition->type,
            'status' => $definition->status,
            'visibility' => $definition->visibility,
            'layout_json' => $definition->layout?->toArray() ?? [],
            'sections_json' => array_map(fn (FormSection $s) => $s->toArray(), $definition->sections),
            'fields_json' => array_map(fn (FormField $f) => $f->toArray(), $definition->fields),
            'actions_json' => array_map(fn (FormAction $a) => $a->toArray(), $definition->actions),
            'conditions_json' => array_map(fn (FormCondition $c) => $c->toArray(), $definition->conditions),
            'validation_rules_json' => array_map(fn (FormFieldValidationRule $r) => $r->toArray(), $definition->validationRules),
            'metadata' => $definition->metadata,
        ]);
    }

    /**
     * @return array{public_id: string}
     */
    public static function toReference(FormDefinitionModel $model): array
    {
        return [
            'public_id' => $model->public_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toSubmissionReference(FormSubmission $model): array
    {
        return [
            'public_id' => $model->public_id,
            'module_key' => $model->module_key,
            'form_key' => $model->formDefinition?->form_key,
            'entity_key' => $model->entity_key,
            'entity_public_id' => $model->entity_public_id,
            'status' => $model->status,
            'submission_data' => is_array($model->submission_data) ? $model->submission_data : [],
            'validation_report' => is_array($model->validation_report) ? $model->validation_report : null,
            'submitted_at' => $model->submitted_at?->toIso8601String(),
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toDraftReference(FormDraft $model): array
    {
        return [
            'public_id' => $model->public_id,
            'module_key' => $model->module_key,
            'form_key' => $model->formDefinition?->form_key,
            'entity_key' => $model->entity_key,
            'entity_public_id' => $model->entity_public_id,
            'draft_data' => is_array($model->draft_data) ? $model->draft_data : [],
            'expires_at' => $model->expires_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toActivityReference(\App\Models\FormActivityLog $model): array
    {
        return [
            'public_id' => $model->public_id,
            'action' => $model->action,
            'before_state' => is_array($model->before_state) ? $model->before_state : [],
            'after_state' => is_array($model->after_state) ? $model->after_state : [],
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}

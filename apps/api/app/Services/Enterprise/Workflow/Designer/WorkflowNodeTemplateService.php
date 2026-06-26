<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Models\WorkflowNodeTemplate;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Designer\Contracts\WorkflowTemplateProvider;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowNodeTemplate as WorkflowNodeTemplateData;
use App\Modules\Sdk\Workflow\Enums\WorkflowNodeType;

class WorkflowNodeTemplateService implements WorkflowTemplateProvider
{
    /**
     * @return list<WorkflowNodeTemplateData>
     */
    public function listTemplates(EnterpriseScope $scope): array
    {
        $this->ensureSystemTemplates();

        return WorkflowNodeTemplate::query()
            ->where(function ($query) use ($scope) {
                $query->where('is_system', true)
                    ->orWhere(function ($q) use ($scope) {
                        $q->whereHas('organization', fn ($org) => $org->where('public_id', $scope->organizationPublicId));
                    });
            })
            ->orderBy('node_type')
            ->get()
            ->map(fn (WorkflowNodeTemplate $template) => $this->toData($template))
            ->all();
    }

    public function ensureSystemTemplates(): void
    {
        if (WorkflowNodeTemplate::query()->where('is_system', true)->exists()) {
            return;
        }

        $templates = [
            ['type' => WorkflowNodeType::Start, 'label' => 'Start', 'width' => 80, 'height' => 80, 'category' => 'flow'],
            ['type' => WorkflowNodeType::End, 'label' => 'End', 'width' => 80, 'height' => 80, 'category' => 'flow'],
            ['type' => WorkflowNodeType::Task, 'label' => 'Task', 'width' => 140, 'height' => 60, 'category' => 'activity'],
            ['type' => WorkflowNodeType::Approval, 'label' => 'Approval', 'width' => 140, 'height' => 60, 'category' => 'activity'],
            ['type' => WorkflowNodeType::Condition, 'label' => 'Condition', 'width' => 100, 'height' => 100, 'category' => 'gateway'],
            ['type' => WorkflowNodeType::Parallel, 'label' => 'Parallel', 'width' => 100, 'height' => 100, 'category' => 'gateway'],
            ['type' => WorkflowNodeType::Merge, 'label' => 'Merge', 'width' => 100, 'height' => 100, 'category' => 'gateway'],
            ['type' => WorkflowNodeType::Event, 'label' => 'Event', 'width' => 120, 'height' => 60, 'category' => 'event'],
            ['type' => WorkflowNodeType::Subprocess, 'label' => 'Subprocess', 'width' => 160, 'height' => 80, 'category' => 'activity'],
            ['type' => WorkflowNodeType::Wait, 'label' => 'Wait', 'width' => 120, 'height' => 60, 'category' => 'activity'],
        ];

        foreach ($templates as $template) {
            WorkflowNodeTemplate::query()->create([
                'node_type' => $template['type']->value,
                'label' => $template['label'],
                'category' => $template['category'],
                'default_width' => $template['width'],
                'default_height' => $template['height'],
                'default_config' => [],
                'metadata' => ['icon' => $template['type']->value],
                'is_system' => true,
            ]);
        }
    }

    private function toData(WorkflowNodeTemplate $template): WorkflowNodeTemplateData
    {
        return new WorkflowNodeTemplateData(
            publicId: $template->public_id,
            nodeType: $template->node_type,
            label: $template->label,
            defaultWidth: $template->default_width,
            defaultHeight: $template->default_height,
            category: $template->category,
            defaultConfig: $template->default_config ?? [],
            metadata: $template->metadata ?? [],
            isSystem: $template->is_system,
        );
    }
}

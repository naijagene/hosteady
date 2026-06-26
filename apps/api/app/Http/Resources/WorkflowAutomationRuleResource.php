<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowAutomationRule */
class WorkflowAutomationRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowAutomationRule $rule */
        $rule = $this->resource;

        return $rule->toArray();
    }
}

<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowRollbackResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowUpgradeResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowInstallResult|WorkflowUpgradeResult|WorkflowRollbackResult */
class WorkflowPackageInstallResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowInstallResult|WorkflowUpgradeResult|WorkflowRollbackResult $result */
        $result = $this->resource;

        return $result->toArray();
    }
}

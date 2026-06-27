<?php

namespace App\Services\Form;

use App\Models\FormDefinition as FormDefinitionModel;
use App\Models\FormSubmission;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Support\Tenant\TenantContext;

class DynamicFormWorkflowBridge
{
    public function triggerBestEffort(
        TenantContext $context,
        string $eventName,
        array $payload = [],
        ?EnterpriseScope $scope = null,
    ): void {
        try {
            if (! app()->bound(EventBusService::class)) {
                return;
            }

            $scope ??= new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: is_string($payload['module_key'] ?? null) ? $payload['module_key'] : null,
            );

            app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
                scope: $scope,
                eventName: $eventName,
                payload: $payload,
            ));
        } catch (\Throwable) {
        }
    }

    public function triggerDefinitionRegisteredBestEffort(FormDefinitionModel $definition): void
    {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $this->triggerBestEffort(
            app(TenantContext::class),
            'form.definition.registered',
            [
                'module_key' => $definition->module_key,
                'form_key' => $definition->form_key,
                'entity_key' => $definition->entity_key,
                'public_id' => $definition->public_id,
            ],
        );
    }

    public function triggerDefinitionUpdatedBestEffort(FormDefinitionModel $definition): void
    {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $this->triggerBestEffort(
            app(TenantContext::class),
            'form.definition.updated',
            [
                'module_key' => $definition->module_key,
                'form_key' => $definition->form_key,
                'entity_key' => $definition->entity_key,
                'public_id' => $definition->public_id,
            ],
        );
    }

    public function triggerSubmissionBestEffort(FormSubmission $submission, FormDefinition $definition): void
    {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $this->triggerBestEffort(
            app(TenantContext::class),
            'form.submission.accepted',
            [
                'module_key' => $definition->moduleKey,
                'form_key' => $definition->formKey,
                'entity_key' => $definition->entityKey,
                'form_public_id' => $definition->publicId,
                'submission_public_id' => $submission->public_id,
                'entity_public_id' => $submission->entity_public_id,
                'status' => $submission->status,
            ],
        );
    }
}

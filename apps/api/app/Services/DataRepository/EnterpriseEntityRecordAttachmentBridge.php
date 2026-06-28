<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\Document\Data\AttachmentRequest;
use App\Modules\Sdk\Document\Enums\AttachmentSubjectType;
use App\Services\Document\EnterpriseAttachmentService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Services\Entity\EnterpriseEntityAttachmentBridge;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityRecordAttachmentBridge
{
    public function __construct(
        private readonly EnterpriseEntityAttachmentBridge $attachmentBridge,
        private readonly EnterpriseAttachmentService $documentAttachmentService,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    public function attachBestEffort(
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
        string $filePublicId,
    ): void {
        try {
            if (app()->bound(TenantContext::class)) {
                $context = app(TenantContext::class);

                if ($this->runtimeBridge->resolve($context)->capabilityEnabled('documents')) {
                    $this->documentAttachmentService->attachBestEffort(
                        $context->organization->id,
                        $context->workspace?->id,
                        new AttachmentRequest(
                            documentPublicId: $filePublicId,
                            subjectType: AttachmentSubjectType::EntityRecord->value,
                            subjectPublicId: $recordPublicId,
                            subjectModuleKey: $moduleKey,
                            subjectEntityKey: $entityKey,
                            metadata: [],
                        ),
                    );

                    return;
                }
            }

            $this->attachmentBridge->attachBestEffort(
                $moduleKey,
                $entityKey,
                $recordPublicId,
                $filePublicId,
            );
        } catch (\Throwable) {
        }
    }
}

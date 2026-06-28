<?php

namespace App\Services\DataRepository;

use App\Services\Entity\EnterpriseEntityAttachmentBridge;

class EnterpriseEntityRecordAttachmentBridge
{
    public function __construct(
        private readonly EnterpriseEntityAttachmentBridge $attachmentBridge,
    ) {
    }

    public function attachBestEffort(
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
        string $filePublicId,
    ): void {
        try {
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

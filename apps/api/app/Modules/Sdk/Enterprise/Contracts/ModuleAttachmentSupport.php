<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

interface ModuleAttachmentSupport
{
    public function attachmentEntityType(): string;

    /**
     * @return list<string>
     */
    public function supportedMimeTypes(): array;
}

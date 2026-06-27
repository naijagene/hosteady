<?php

namespace App\Modules\Sdk\Entity\Contracts;

interface EntityAttachmentProvider
{
    public function moduleKey(): string;

    public function entityKey(): string;

    public function attach(string $entityPublicId, string $filePublicId): void;

    public function detach(string $entityPublicId, string $filePublicId): void;

    /**
     * @return list<string>
     */
    public function supportedMimeTypes(): array;
}

<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\FileDownloadResult;
use App\Modules\Sdk\Enterprise\Data\FileReference;
use App\Modules\Sdk\Enterprise\Data\FileUpdateRequest;
use App\Modules\Sdk\Enterprise\Data\FileUploadRequest;

interface FileServicePort
{
    public function upload(FileUploadRequest $request): FileReference;

    public function update(FileUpdateRequest $request): FileReference;

    public function delete(EnterpriseScope $scope, string $filePublicId): void;

    public function find(EnterpriseScope $scope, string $filePublicId): ?FileReference;

    /**
     * @return list<FileReference>
     */
    public function listForEntity(EnterpriseScope $scope, EntityReference $entityReference): array;

    /**
     * @return list<FileReference>
     */
    public function listForScope(EnterpriseScope $scope, int $limit = 50): array;

    public function download(EnterpriseScope $scope, string $filePublicId): FileDownloadResult;
}

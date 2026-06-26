<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class FileDownloadResult
{
    public function __construct(
        public FileReference $file,
        public string $contents,
    ) {
    }
}

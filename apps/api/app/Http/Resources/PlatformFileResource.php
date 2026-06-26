<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Enterprise\Data\FileReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FileReference */
class PlatformFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var FileReference $file */
        $file = $this->resource;

        return [
            'public_id' => $file->publicId,
            'filename' => $file->filename,
            'original_filename' => $file->originalFilename,
            'extension' => $file->extension,
            'mime_type' => $file->mimeType,
            'size_bytes' => $file->sizeBytes,
            'visibility' => $file->visibility,
            'category' => $file->category,
            'module_key' => $file->moduleKey,
            'entity_reference' => $file->entityReference?->toArray(),
            'display_name' => $file->displayName,
            'metadata' => $file->metadata,
            'checksum' => $file->checksum,
            'uploaded_membership_public_id' => $file->uploadedMembershipPublicId,
            'created_at' => $file->createdAt,
        ];
    }
}

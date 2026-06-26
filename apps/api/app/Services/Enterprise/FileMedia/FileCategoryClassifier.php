<?php

namespace App\Services\Enterprise\FileMedia;

use App\Enums\FileCategory;

class FileCategoryClassifier
{
    public function classify(?string $mimeType, ?string $extension): FileCategory
    {
        $mimeType = strtolower(trim($mimeType ?? ''));

        if ($mimeType !== '') {
            return $this->classifyByMime($mimeType);
        }

        return $this->classifyByExtension($extension);
    }

    private function classifyByMime(string $mimeType): FileCategory
    {
        if (str_starts_with($mimeType, 'image/')) {
            return FileCategory::Image;
        }

        if (str_starts_with($mimeType, 'video/')) {
            return FileCategory::Video;
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return FileCategory::Audio;
        }

        if ($mimeType === 'application/pdf'
            || $mimeType === 'application/msword'
            || str_starts_with($mimeType, 'application/vnd.')
            || in_array($mimeType, ['text/plain', 'text/markdown', 'text/html'], true)) {
            return FileCategory::Document;
        }

        if (in_array($mimeType, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-rar',
            'application/x-7z-compressed',
            'application/gzip',
            'application/x-gzip',
            'application/x-tar',
        ], true)) {
            return FileCategory::Archive;
        }

        if (in_array($mimeType, [
            'text/csv',
            'application/json',
            'application/xml',
            'text/xml',
            'application/sql',
        ], true)) {
            return FileCategory::Data;
        }

        return FileCategory::Other;
    }

    private function classifyByExtension(?string $extension): FileCategory
    {
        $extension = strtolower(trim(ltrim($extension ?? '', '.')));

        if ($extension === '') {
            return FileCategory::Other;
        }

        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'ico'], true)) {
            return FileCategory::Image;
        }

        if (in_array($extension, ['mp4', 'avi', 'mov', 'webm', 'mkv', 'mpeg', 'mpg'], true)) {
            return FileCategory::Video;
        }

        if (in_array($extension, ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a'], true)) {
            return FileCategory::Audio;
        }

        if (in_array($extension, ['pdf', 'doc', 'docx', 'txt', 'md', 'markdown', 'html', 'htm', 'rtf'], true)) {
            return FileCategory::Document;
        }

        if (in_array($extension, ['zip', 'rar', '7z', 'gz', 'tar', 'tgz'], true)) {
            return FileCategory::Archive;
        }

        if (in_array($extension, ['csv', 'json', 'xml', 'sql'], true)) {
            return FileCategory::Data;
        }

        return FileCategory::Other;
    }
}

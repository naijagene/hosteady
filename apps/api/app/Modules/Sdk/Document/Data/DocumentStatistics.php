<?php

namespace App\Modules\Sdk\Document\Data;

readonly class DocumentStatistics implements \JsonSerializable
{
    public function __construct(
        public int $documents,
        public int $versions,
        public int $attachments,
        public int $previews,
        public int $scans,
        public int $ocrResults,
        public int $activityLogs
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            documents: (int) ($data['documents'] ?? $data['documents'] ?? 0),
            versions: (int) ($data['versions'] ?? $data['versions'] ?? 0),
            attachments: (int) ($data['attachments'] ?? $data['attachments'] ?? 0),
            previews: (int) ($data['previews'] ?? $data['previews'] ?? 0),
            scans: (int) ($data['scans'] ?? $data['scans'] ?? 0),
            ocrResults: (int) ($data['ocr_results'] ?? $data['ocrResults'] ?? 0),
            activityLogs: (int) ($data['activity_logs'] ?? $data['activityLogs'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'documents' => $this->documents,
            'versions' => $this->versions,
            'attachments' => $this->attachments,
            'previews' => $this->previews,
            'scans' => $this->scans,
            'ocr_results' => $this->ocrResults,
            'activity_logs' => $this->activityLogs
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
}

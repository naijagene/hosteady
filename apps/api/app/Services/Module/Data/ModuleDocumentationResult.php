<?php

namespace App\Services\Module\Data;

readonly class ModuleDocumentationResult
{
    /**
     * @param  list<string>  $generatedFiles
     */
    public function __construct(
        public string $outputDirectory,
        public array $generatedFiles,
        public int $moduleCount,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'output_directory' => $this->outputDirectory,
            'generated_files' => $this->generatedFiles,
            'module_count' => $this->moduleCount,
        ];
    }
}

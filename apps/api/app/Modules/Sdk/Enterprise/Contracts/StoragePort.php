<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

interface StoragePort
{
    public function store(string $disk, string $path, string $contents): bool;

    public function delete(string $disk, string $path): bool;

    public function exists(string $disk, string $path): bool;

    public function get(string $disk, string $path): string;

    public function size(string $disk, string $path): int;

    public function isWritable(string $disk): bool;

    /**
     * @return list<string>
     */
    public function configuredDisks(): array;
}

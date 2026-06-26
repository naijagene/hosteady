<?php

namespace App\Services\Enterprise\FileMedia;

use App\Modules\Sdk\Enterprise\Contracts\StoragePort;
use Illuminate\Support\Facades\Storage;

class LaravelStorageAdapter implements StoragePort
{
    public function store(string $disk, string $path, string $contents): bool
    {
        return Storage::disk($disk)->put($path, $contents) !== false;
    }

    public function delete(string $disk, string $path): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    public function exists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    public function get(string $disk, string $path): string
    {
        return Storage::disk($disk)->get($path);
    }

    public function size(string $disk, string $path): int
    {
        return Storage::disk($disk)->size($path);
    }

    public function isWritable(string $disk): bool
    {
        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            return false;
        }

        try {
            $probePath = '.heos-storage-probe-'.uniqid('', true);

            if (! $this->store($disk, $probePath, 'probe')) {
                return false;
            }

            $this->delete($disk, $probePath);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    public function configuredDisks(): array
    {
        return array_keys(config('filesystems.disks', []));
    }
}

<?php

namespace App\Services\Runtime;

interface RuntimeCacheStore
{
    public function get(string $key): mixed;

    public function put(string $key, mixed $value, int $ttlSeconds): void;

    public function incrementGeneration(string $organizationPublicId, string $workspacePublicId): int;

    public function currentGeneration(string $organizationPublicId, string $workspacePublicId): int;
}

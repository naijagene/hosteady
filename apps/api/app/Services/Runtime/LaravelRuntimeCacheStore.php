<?php

namespace App\Services\Runtime;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class LaravelRuntimeCacheStore implements RuntimeCacheStore
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->cache->put($key, $value, $ttlSeconds);
    }

    public function incrementGeneration(string $organizationPublicId, string $workspacePublicId): int
    {
        $generationKey = $this->generationKey($organizationPublicId, $workspacePublicId);

        if (! $this->cache->has($generationKey)) {
            $this->cache->forever($generationKey, 1);

            return 1;
        }

        return (int) $this->cache->increment($generationKey);
    }

    public function currentGeneration(string $organizationPublicId, string $workspacePublicId): int
    {
        return (int) ($this->cache->get($this->generationKey($organizationPublicId, $workspacePublicId)) ?? 1);
    }

    private function generationKey(string $organizationPublicId, string $workspacePublicId): string
    {
        return sprintf('heos:runtime:gen:%s:%s', $organizationPublicId, $workspacePublicId);
    }
}

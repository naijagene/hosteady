<?php

namespace App\Modules\Sdk\Development\Support;

use App\Modules\Sdk\Development\Exceptions\BusinessModuleValidationException;
use InvalidArgumentException;

class BusinessModulePathResolver
{
    public function __construct(
        private readonly BusinessModuleConventionResolver $conventionResolver,
    ) {
    }

    public function moduleRoot(string $moduleKey): string
    {
        return $this->normalize($moduleKey, '');
    }

    public function normalize(string $moduleKey, string $relativePath): string
    {
        $root = realpath($this->conventionResolver->resolveFromKey($moduleKey)['base_path'])
            ?: $this->conventionResolver->resolveFromKey($moduleKey)['base_path'];

        if ($relativePath === '' || $relativePath === '.') {
            return $this->assertWithinModuleRoot($moduleKey, $root);
        }

        if ($this->containsTraversal($relativePath)) {
            throw new InvalidArgumentException(sprintf('Path traversal is not allowed for module [%s].', $moduleKey));
        }

        $candidate = $root.'/'.ltrim(str_replace('\\', '/', $relativePath), '/');
        $resolved = realpath($candidate);

        if ($resolved === false) {
            $resolved = $this->resolveWithoutExistingTarget($root, $relativePath);
        }

        return $this->assertWithinModuleRoot($moduleKey, $resolved);
    }

    public function isWithinModuleRoot(string $moduleKey, string $path): bool
    {
        $root = $this->conventionResolver->resolveFromKey($moduleKey)['base_path'];
        $normalizedRoot = str_replace('\\', '/', realpath($root) ?: $root);
        $normalizedPath = str_replace('\\', '/', realpath($path) ?: $path);

        return str_starts_with(rtrim($normalizedPath, '/').'/', rtrim($normalizedRoot, '/').'/');
    }

    private function assertWithinModuleRoot(string $moduleKey, string $path): string
    {
        if (! $this->isWithinModuleRoot($moduleKey, $path)) {
            throw new BusinessModuleValidationException(sprintf(
                'Resolved path [%s] is outside module root for [%s].',
                $path,
                $moduleKey,
            ));
        }

        return $path;
    }

    private function containsTraversal(string $relativePath): bool
    {
        $segments = array_filter(explode('/', str_replace('\\', '/', $relativePath)), fn (string $segment) => $segment !== '');

        return in_array('..', $segments, true)
            || str_starts_with($relativePath, '/')
            || preg_match('/^[A-Za-z]:/', $relativePath) === 1;
    }

    private function resolveWithoutExistingTarget(string $root, string $relativePath): string
    {
        $segments = array_filter(explode('/', str_replace('\\', '/', ltrim($relativePath, '/'))), fn (string $segment) => $segment !== '' && $segment !== '.');

        if (in_array('..', $segments, true)) {
            throw new InvalidArgumentException('Path traversal is not allowed.');
        }

        return $root.'/'.implode('/', $segments);
    }
}

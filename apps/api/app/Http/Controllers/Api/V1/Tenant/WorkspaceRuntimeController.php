<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Exceptions\Tenant\InvalidWorkspaceApplicationHeaderException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveTenantContext;
use App\Http\Resources\WorkspaceRuntimeResource;
use App\Models\WorkspaceApplication;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class WorkspaceRuntimeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkspaceRuntimeProvider $workspaceRuntimeProvider,
    ) {
    }

    public function __invoke(Request $request): WorkspaceRuntimeResource
    {
        $this->authorize('viewAny', WorkspaceApplication::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $activeApplicationPublicId = $this->resolveActiveApplicationHeader($request);

        return new WorkspaceRuntimeResource(
            $this->workspaceRuntimeProvider->resolve($context, $activeApplicationPublicId),
        );
    }

    private function resolveActiveApplicationHeader(Request $request): ?string
    {
        $headerValue = $request->header(ResolveTenantContext::APPLICATION_HEADER);

        if (! is_string($headerValue) || $headerValue === '') {
            return null;
        }

        if (! $this->isUuid($headerValue)) {
            throw new InvalidWorkspaceApplicationHeaderException;
        }

        return $headerValue;
    }

    private function isUuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value,
        ) === 1;
    }
}

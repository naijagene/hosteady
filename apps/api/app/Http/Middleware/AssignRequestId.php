<?php

namespace App\Http\Middleware;

use App\Support\Http\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header(self::HEADER);

        if (! is_string($requestId) || ! $this->isUuid($requestId)) {
            $requestId = (string) Str::uuid7();
        }

        $request->attributes->set('requestId', $requestId);

        app()->instance(RequestContext::class, new RequestContext(
            requestId: $requestId,
            ipAddress: $request->ip(),
            userAgent: Str::limit($request->userAgent() ?? '', 512, ''),
        ));

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }

    private function isUuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value,
        ) === 1;
    }
}

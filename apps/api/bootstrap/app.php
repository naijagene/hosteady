<?php

use App\Exceptions\Application\ApplicationException;
use App\Exceptions\Audit\AuditException;
use App\Exceptions\Tenant\TenantContextException;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\ResolveTenantContext;
use App\Services\Audit\DomainAuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.context' => ResolveTenantContext::class,
        ]);

        $middleware->api(prepend: [
            AssignRequestId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TenantContextException $exception, Request $request) {
            if ($request->is('api/*')) {
                try {
                    app(DomainAuditRecorder::class)->recordTenantRejected($request, $exception);
                } catch (\Throwable) {
                    // Audit failures must never break error responses.
                }

                return response()->json([
                    'message' => $exception->getMessage(),
                ], $exception->statusCode);
            }

            return null;
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $previous = $exception->getPrevious();

            if ($previous instanceof AuthorizationException) {
                try {
                    app(DomainAuditRecorder::class)->recordPermissionDenied($request, $previous);
                } catch (\Throwable) {
                    // Audit failures must never break error responses.
                }
            }

            return null;
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                try {
                    app(DomainAuditRecorder::class)->recordInvalidToken();
                } catch (\Throwable) {
                    // Audit failures must never break error responses.
                }
            }

            return null;
        });

        $exceptions->render(function (ApplicationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], $exception->statusCode);
            }

            return null;
        });

        $exceptions->render(function (AuditException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], $exception->statusCode);
            }

            return null;
        });
    })->create();

<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthTokenResource;
use App\Services\Audit\DomainAuditRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, DomainAuditRecorder $domainAuditRecorder): JsonResponse
    {
        $email = $request->string('email')->lower()->value();

        $user = Auth::getProvider()->retrieveByCredentials([
            'email' => $email,
        ]);

        if ($user === null || ! Hash::check($request->string('password')->value(), $user->password)) {
            $domainAuditRecorder->recordLoginFailed($user, $email);

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($user->trashed() || $user->status !== 'active') {
            $domainAuditRecorder->recordLoginFailed($user, $email);

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $expiresAt = now()->addDays(90);

        $accessToken = $user->createToken('heos-api', ['*'], $expiresAt);

        $domainAuditRecorder->recordLoginSucceeded($user);

        return (new AuthTokenResource([
            'token' => $accessToken->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => $user,
        ]))->response();
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Audit\DomainAuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutController extends Controller
{
    public function __invoke(Request $request, DomainAuditRecorder $domainAuditRecorder): Response
    {
        $user = $request->user();
        $accessToken = $user?->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }

        if ($user !== null) {
            $domainAuditRecorder->recordLogoutSucceeded($user);
        }

        return response()->noContent();
    }
}

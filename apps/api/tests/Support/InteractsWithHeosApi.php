<?php

namespace Tests\Support;

use App\Http\Middleware\ResolveTenantContext;
use App\Models\User;

trait InteractsWithHeosApi
{
    protected function issueToken(User $user): string
    {
        return $user->createToken('test', ['*'], now()->addDays(90))->plainTextToken;
    }

    protected function withBearerToken(string $token): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    protected function withTenantHeaders(string $organizationPublicId, ?string $workspacePublicId = null): static
    {
        $headers = [
            ResolveTenantContext::ORGANIZATION_HEADER => $organizationPublicId,
        ];

        if ($workspacePublicId !== null) {
            $headers[ResolveTenantContext::WORKSPACE_HEADER] = $workspacePublicId;
        }

        return $this->withHeaders($headers);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function assertResponseUsesPublicIdsOnly(array $payload): void
    {
        array_walk_recursive($payload, function (mixed $value, string|int $key) {
            if ($key === 'id') {
                $this->fail('API response exposed an internal bigint id field.');
            }
        });
    }
}

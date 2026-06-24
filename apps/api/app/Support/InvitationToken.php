<?php

namespace App\Support;

use Illuminate\Support\Str;

class InvitationToken
{
    public function generate(): string
    {
        return Str::random(64);
    }

    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function verify(string $plainToken, string $tokenHash): bool
    {
        return hash_equals($tokenHash, $this->hash($plainToken));
    }
}

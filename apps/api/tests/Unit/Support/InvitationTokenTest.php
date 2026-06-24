<?php

namespace Tests\Unit\Support;

use App\Support\InvitationToken;
use PHPUnit\Framework\TestCase;

class InvitationTokenTest extends TestCase
{
    private InvitationToken $invitationToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invitationToken = new InvitationToken;
    }

    public function test_generate_returns_64_character_string(): void
    {
        $token = $this->invitationToken->generate();

        $this->assertSame(64, strlen($token));
    }

    public function test_hash_is_deterministic_sha256(): void
    {
        $plainToken = 'known-test-token';

        $this->assertSame(
            hash('sha256', $plainToken),
            $this->invitationToken->hash($plainToken),
        );
    }

    public function test_verify_succeeds_for_matching_token(): void
    {
        $plainToken = $this->invitationToken->generate();
        $tokenHash = $this->invitationToken->hash($plainToken);

        $this->assertTrue($this->invitationToken->verify($plainToken, $tokenHash));
    }

    public function test_verify_fails_for_non_matching_token(): void
    {
        $tokenHash = $this->invitationToken->hash('original-token');

        $this->assertFalse($this->invitationToken->verify('different-token', $tokenHash));
    }
}

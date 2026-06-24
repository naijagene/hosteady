<?php

namespace App\Support\Http;

readonly class RequestContext
{
    public function __construct(
        public string $requestId,
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {
    }
}

<?php

namespace App\Services\Personalization;

class PersonalizationPlatformEventBridge
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatchBestEffort(string $event, array $payload = []): void
    {
        // Best-effort no-op until personalization events are subscribed downstream.
    }
}

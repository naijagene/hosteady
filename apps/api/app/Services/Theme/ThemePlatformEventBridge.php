<?php

namespace App\Services\Theme;

class ThemePlatformEventBridge
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatchBestEffort(string $event, array $payload = []): void
    {
        // Intentionally no-op until theme events are subscribed by downstream modules.
    }
}

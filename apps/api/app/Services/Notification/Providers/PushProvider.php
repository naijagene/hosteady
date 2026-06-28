<?php

namespace App\Services\Notification\Providers;

class PushProvider extends AbstractMetadataNotificationProvider
{
    protected function providerName(): string
    {
        return 'push';
    }

    protected function supportedChannel(): string
    {
        return 'push';
    }
}

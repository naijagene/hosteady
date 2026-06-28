<?php

namespace App\Services\Notification\Providers;

class SlackProvider extends AbstractMetadataNotificationProvider
{
    protected function providerName(): string
    {
        return 'slack';
    }

    protected function supportedChannel(): string
    {
        return 'slack';
    }
}

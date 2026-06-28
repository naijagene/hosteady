<?php

namespace App\Services\Notification\Providers;

class TeamsProvider extends AbstractMetadataNotificationProvider
{
    protected function providerName(): string
    {
        return 'teams';
    }

    protected function supportedChannel(): string
    {
        return 'teams';
    }
}

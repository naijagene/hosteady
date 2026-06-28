<?php

namespace App\Services\Notification\Providers;

class WhatsappProvider extends AbstractMetadataNotificationProvider
{
    protected function providerName(): string
    {
        return 'whatsapp';
    }

    protected function supportedChannel(): string
    {
        return 'whatsapp';
    }
}

<?php

namespace App\Modules\Sdk\Integration\Enums;

enum IntegrationAuthType: string
{
    case None = 'none';
    case SharedSecret = 'shared_secret';
    case ApiKey = 'api_key';
    case Oauth2 = 'oauth2';
    case HmacSha256 = 'hmac_sha256';
    case Basic = 'basic';
}

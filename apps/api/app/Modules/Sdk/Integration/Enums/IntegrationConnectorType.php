<?php

namespace App\Modules\Sdk\Integration\Enums;

enum IntegrationConnectorType: string
{
    case Internal = 'internal';
    case Webhook = 'webhook';
    case Email = 'email';
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Slack = 'slack';
    case Teams = 'teams';
    case Accounting = 'accounting';
    case Crm = 'crm';
    case Erp = 'erp';
    case Storage = 'storage';
    case Payment = 'payment';
    case Custom = 'custom';
}

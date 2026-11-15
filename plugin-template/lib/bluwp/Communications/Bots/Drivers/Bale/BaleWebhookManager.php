<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Drivers\Bale;

use Forouzandea\Bluwp\Communications\Bots\Webhooks\WebhookManager;

final class BaleWebhookManager extends WebhookManager
{
    public function __construct(BaleAPIClient $api)
    {
        parent::__construct($api, [443, 88]);
    }
}

<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Drivers\Telegram;

use Forouzandea\Bluwp\Communications\Bots\Webhooks\WebhookManager;

final class TelegramWebhookManager extends WebhookManager
{
    public function __construct(TelegramAPIClient $api)
    {
        parent::__construct($api, [443, 80, 88, 8443], true);
    }
}

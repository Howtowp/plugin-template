<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Webhooks;

final class WebhookPayload
{
    public static function decode(string $body, int $maxBytes = 1048576): array
    {
        if (strlen($body) > $maxBytes) {
            throw new \DomainException(__('Webhook payload too large.', BLUWP), 413);
        }
        $update = json_decode($body, true);
        if (!is_array($update) || !isset($update['update_id']) || !is_int($update['update_id'])) {
            throw new \DomainException(__('Invalid bot update.', BLUWP), 400);
        }
        return $update;
    }
}

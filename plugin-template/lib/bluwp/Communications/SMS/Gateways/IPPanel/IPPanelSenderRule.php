<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Gateways\IPPanel;

use Forouzandea\Bluwp\Communications\SMS\Gateways\IPPanel\IPPanel;
use Forouzandea\Bluwp\Support\Validation\Rules\TypeRule;

final class IPPanelSenderRule extends TypeRule
{
    public function name(): string
    {
        return 'ip_panel_sender';
    }

    protected function passes(mixed $value): bool
    {
        return is_string($value) && ($value === '' || IPPanel::isValidSender($value));
    }
}

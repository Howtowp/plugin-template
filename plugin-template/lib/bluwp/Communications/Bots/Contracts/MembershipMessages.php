<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Contracts;

use Forouzandea\Bluwp\Communications\Bots\Enums\MembershipPrompt;

interface MembershipMessages
{
    public function forPrompt(MembershipPrompt $prompt): string;
}

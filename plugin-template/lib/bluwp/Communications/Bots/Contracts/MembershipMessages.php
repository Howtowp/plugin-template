<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Contracts;

use Howtowp\Bluwp\Communications\Bots\Enums\MembershipPrompt;

interface MembershipMessages
{
    public function forPrompt(MembershipPrompt $prompt): string;
}

<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Enums;

enum MembershipPrompt
{
    case ContactButton;
    case JoinPrompt;
    case OwnContactPrompt;
    case InvalidNumber;
    case NumberConflict;
    case JoinSuccess;
    case AlreadyMember;
}

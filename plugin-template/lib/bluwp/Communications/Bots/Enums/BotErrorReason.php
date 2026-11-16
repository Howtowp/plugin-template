<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Enums;

enum BotErrorReason: string
{
    case Network = 'network';
    case Remote = 'remote';
    case InvalidResponse = 'invalid_response';
    case InvalidStatus = 'invalid_status';
    case RegistrationFailed = 'registration_failed';
    case RemovalFailed = 'removal_failed';
}

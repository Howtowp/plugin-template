<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Enums;

/** Provider acceptance is not a delivery receipt; unavailable means no usable channel. */
enum MessageStatus: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Unknown = 'unknown';
    case Unavailable = 'unavailable';
}

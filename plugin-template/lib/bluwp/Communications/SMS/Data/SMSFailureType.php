<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Data;

/** Only a documented temporary rejection qualifies for automatic retry. */
enum SMSFailureType: string
{
    case Temporary = 'temporary';
    case Permanent = 'permanent';
    case Uncertain = 'uncertain';
    case Unclassified = 'unclassified';
}

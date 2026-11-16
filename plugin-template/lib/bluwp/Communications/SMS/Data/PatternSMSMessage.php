<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Data;

final class PatternSMSMessage extends SMSMessage
{
    public function __construct(
        string $recipient,
        public readonly string $template,
        public readonly array $variables
    ) {
        parent::__construct($recipient);
        if (!preg_match('/^[a-zA-Z0-9_-]+$/D', $template)) {
            throw new \InvalidArgumentException(__('Invalid SMS template identifier.', BLUWP));
        }
        foreach ($variables as $name => $value) {
            if (!is_string($name) || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $name)
                || !is_string($value) || trim($value) === '') {
                throw new \InvalidArgumentException(__('Pattern variables require named, non-empty string values.', BLUWP));
            }
            if (!preg_match('//u', $value)) {
                throw new \InvalidArgumentException(__('SMS content must be valid UTF-8.', BLUWP));
            }
        }
    }
}

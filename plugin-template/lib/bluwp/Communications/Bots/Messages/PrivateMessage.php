<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Messages;

final class PrivateMessage
{
    private function __construct(
        public readonly string $chatId,
        public readonly string $userId,
        private array $message
    ) {
    }

    public static function fromUpdate(array $update): ?self
    {
        $message = $update['message'] ?? null;
        if (!is_array($message) || !is_array($message['chat'] ?? null)
            || !is_array($message['from'] ?? null) || ($message['chat']['type'] ?? '') !== 'private'
            || ($message['from']['is_bot'] ?? false) === true) {
            return null;
        }
        $chatId = self::identifier($message['chat']['id'] ?? null);
        $userId = self::identifier($message['from']['id'] ?? null);
        return $chatId !== null && $userId !== null ? new self($chatId, $userId, $message) : null;
    }

    public function isStart(): bool
    {
        $text = $this->message['text'] ?? null;
        return is_string($text) && preg_match('/^\/start(?:@[A-Za-z0-9_]+)?(?:\s|$)/D', $text) === 1;
    }

    public function hasContact(): bool
    {
        return isset($this->message['contact']);
    }

    public function ownPhoneNumber(): ?string
    {
        $contact = $this->message['contact'] ?? null;
        if (!is_array($contact) || isset($this->message['forward_origin'])
            || isset($this->message['forward_date']) || isset($this->message['forward_from'])
            || self::identifier($contact['user_id'] ?? null) !== $this->userId) {
            return null;
        }
        return is_string($contact['phone_number'] ?? null) ? $contact['phone_number'] : null;
    }

    private static function identifier(mixed $id): ?string
    {
        return (is_int($id) || is_string($id)) && preg_match('/^[1-9][0-9]{0,18}$/D', (string) $id)
            ? (string) $id : null;
    }
}

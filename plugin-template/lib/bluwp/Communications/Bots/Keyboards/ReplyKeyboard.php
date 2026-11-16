<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Keyboards;

final class ReplyKeyboard
{
    public static function requestContact(string $label): array
    {
        return [
            'keyboard' => [[['text' => $label, 'request_contact' => true]]],
            'resize_keyboard' => true,
        ];
    }

    public static function remove(): array
    {
        return ['remove_keyboard' => true];
    }
}

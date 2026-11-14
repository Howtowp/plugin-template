<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots;

use Forouzandea\Bluwp\Communications\Bots\Contracts\BotMessenger;
use Forouzandea\Bluwp\Communications\Bots\Contracts\ContactNumberNormalizer;
use Forouzandea\Bluwp\Communications\Bots\Contracts\MembershipContacts;
use Forouzandea\Bluwp\Communications\Bots\Contracts\MembershipMessages;
use Forouzandea\Bluwp\Communications\Bots\Enums\MembershipPrompt;
use Forouzandea\Bluwp\Communications\Bots\Keyboards\ReplyKeyboard;
use Forouzandea\Bluwp\Communications\Bots\Messages\PrivateMessage;

final class Membership
{
    public function __construct(
        private MembershipContacts $contacts,
        private MembershipMessages $messages,
        private ContactNumberNormalizer $numbers
    ) {
    }

    public function handle(string $botType, BotMessenger $messenger, array $update): void
    {
        $message = PrivateMessage::fromUpdate($update);
        if ($message === null) {
            return;
        }
        $keyboard = ReplyKeyboard::requestContact($this->messages->forPrompt(MembershipPrompt::ContactButton));
        if ($message->hasContact()) {
            $phone = $message->ownPhoneNumber();
            if ($phone === null) {
                $messenger->send($message->chatId, $this->messages->forPrompt(MembershipPrompt::OwnContactPrompt), $keyboard);
                return;
            }
            $number = $this->numbers->normalize($phone);
            if ($number === null) {
                $messenger->send($message->chatId, $this->messages->forPrompt(MembershipPrompt::InvalidNumber), $keyboard);
                return;
            }
            if (!$this->contacts->save($botType, $message->userId, $number)) {
                $messenger->send($message->chatId, $this->messages->forPrompt(MembershipPrompt::NumberConflict), $keyboard);
                return;
            }
            $messenger->send($message->chatId, $this->messages->forPrompt(MembershipPrompt::JoinSuccess), ReplyKeyboard::remove());
            return;
        }
        if ($this->contacts->exists($botType, $message->userId)) {
            if ($message->isStart()) {
                $messenger->send($message->chatId, $this->messages->forPrompt(MembershipPrompt::AlreadyMember), ReplyKeyboard::remove());
            }
            return;
        }
        $messenger->send($message->chatId, $this->messages->forPrompt(MembershipPrompt::JoinPrompt), $keyboard);
    }
}

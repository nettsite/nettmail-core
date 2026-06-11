<?php

namespace Nettsite\NettMail\Core\Tests\Fakes;

use Nettsite\NettMail\Core\Contracts\MailboxContract;
use Nettsite\NettMail\Core\Domain\Bounces\MailboxMessage;

final class FakeMailbox implements MailboxContract
{
    /** @var array<string, string> */
    public array $movedTo = [];

    /**
     * @param array<int, MailboxMessage> $messages
     */
    public function __construct(
        private readonly array $messages = [],
    ) {
    }

    public function fetchUnseenMessages(): array
    {
        return $this->messages;
    }

    public function moveMessage(string $id, string $folder): void
    {
        $this->movedTo[$id] = $folder;
    }
}

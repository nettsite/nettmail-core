<?php

namespace Nettsite\NettMail\Core\Contracts;

use Nettsite\NettMail\Core\Domain\Bounces\MailboxMessage;

/**
 * Framework-agnostic mailbox access for bounce polling. Adapters provide
 * the IMAP/POP3 connection (e.g. via php-imap); core only needs to fetch
 * unseen messages and move them to a folder afterwards.
 */
interface MailboxContract
{
    /**
     * @return array<int, MailboxMessage>
     */
    public function fetchUnseenMessages(): array;

    public function moveMessage(string $id, string $folder): void;
}

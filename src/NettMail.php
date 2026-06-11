<?php

namespace Nettsite\NettMail\Core;

use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;
use Nettsite\NettMail\Core\Domain\Contacts\ContactEraser;
use Nettsite\NettMail\Core\Domain\Contacts\SuppressionExporter;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Mail\SendResult;

final class NettMail
{
    public function __construct(
        private readonly MailDriverContract $driver,
        private readonly StorageAdapterContract $storage,
    ) {
    }

    public function send(EmailMessage $message): SendResult
    {
        return $this->driver->send($message);
    }

    /**
     * POPIA right-to-erasure. Anonymises the contact's PII while
     * retaining its id (and therefore aggregate send statistics).
     * Returns false if no contact matches the given email.
     */
    public function eraseContact(string $email): bool
    {
        $contact = $this->storage->findContactByEmail($email);

        if ($contact === null) {
            return false;
        }

        (new ContactEraser())->erase($contact);
        $this->storage->saveContact($contact);

        return true;
    }

    /**
     * CSV export of globally suppressed contacts (hard bounce, complaint,
     * global unsubscribe) for upload to external provider suppression lists.
     */
    public function exportSuppressions(): string
    {
        return (new SuppressionExporter())->export($this->storage->findSuppressedContacts());
    }
}

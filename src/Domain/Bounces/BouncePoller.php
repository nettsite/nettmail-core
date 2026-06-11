<?php

namespace Nettsite\NettMail\Core\Domain\Bounces;

use DateTimeImmutable;
use Nettsite\NettMail\Core\Contracts\BounceParserContract;
use Nettsite\NettMail\Core\Contracts\MailboxContract;
use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;

/**
 * Polls a mailbox for bounce/DSN messages, classifies them, and applies
 * the same suppression logic as webhook-driven bounces. Processed
 * messages are moved to the configured "processed" folder for audit;
 * unparseable messages are moved to "unrecognised" for manual review.
 */
final class BouncePoller
{
    public function __construct(
        private readonly MailboxContract $mailbox,
        private readonly BounceParserContract $parser,
        private readonly BounceClassifier $classifier,
        private readonly StorageAdapterContract $storage,
        private readonly string $processedFolder = 'Processed',
        private readonly string $unrecognisedFolder = 'Unrecognised',
    ) {
    }

    public function poll(): BouncePollResult
    {
        $processed = 0;
        $unrecognised = 0;

        foreach ($this->mailbox->fetchUnseenMessages() as $message) {
            $bounce = $this->parser->parse($message->rawContent);

            if ($bounce === null) {
                $this->mailbox->moveMessage($message->id, $this->unrecognisedFolder);
                $unrecognised++;

                continue;
            }

            $contact = $this->storage->findContactByEmail($bounce->recipient);

            if ($contact !== null) {
                $this->classifier->recordEvent($contact, $bounce->bounceType, new DateTimeImmutable());
                $this->storage->saveContact($contact);
            }

            $this->mailbox->moveMessage($message->id, $this->processedFolder);
            $processed++;
        }

        return new BouncePollResult($processed, $unrecognised);
    }
}

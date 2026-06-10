<?php

namespace Nettsite\NettMail\Core\Mail;

final readonly class EmailMessage
{
    /**
     * @param array<int, EmailAddress> $to
     * @param array<int, EmailAddress> $cc
     * @param array<int, EmailAddress> $bcc
     * @param array<int, array{path: string, name: string}> $attachments
     */
    public function __construct(
        public EmailAddress $from,
        public array $to,
        public string $subject,
        public ?string $html = null,
        public ?string $text = null,
        public array $cc = [],
        public array $bcc = [],
        public ?EmailAddress $replyTo = null,
        public array $attachments = [],
    ) {
    }
}

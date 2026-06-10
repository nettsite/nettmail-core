<?php

namespace Nettsite\NettMail\Core\Mail;

final readonly class SendResult
{
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?string $error = null,
    ) {
    }

    public static function success(string $messageId): self
    {
        return new self(success: true, messageId: $messageId);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }
}

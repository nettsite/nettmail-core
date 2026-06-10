<?php

namespace Nettsite\NettMail\Core\Contracts;

use Nettsite\NettMail\Core\Domain\Webhooks\NormalizedEvent;

interface WebhookHandlerContract
{
    /**
     * Verifies the request signature before any processing. Header keys
     * must be lowercased by the caller.
     *
     * @param array<string, string> $headers
     */
    public function verify(string $rawBody, array $headers, string $secret): bool;

    /**
     * Maps a provider's payload shape to normalized events. Returns an
     * empty array for event types this handler does not recognise.
     *
     * @param array<string, mixed> $payload
     * @return array<int, NormalizedEvent>
     */
    public function parse(array $payload): array;
}

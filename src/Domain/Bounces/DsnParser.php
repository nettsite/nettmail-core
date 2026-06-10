<?php

namespace Nettsite\NettMail\Core\Domain\Bounces;

use Nettsite\NettMail\Core\Contracts\BounceParserContract;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;

/**
 * Parses RFC 3464 delivery-status notifications, falling back to
 * heuristic subject/body matching for non-standard bounce formats.
 */
final class DsnParser implements BounceParserContract
{
    public function parse(string $rawMessage): ?ParsedBounce
    {
        $statusCode = $this->extractStatusCode($rawMessage);
        $recipient = $this->extractRecipient($rawMessage);

        if ($statusCode !== null && $recipient !== null) {
            return new ParsedBounce(
                recipient: $recipient,
                bounceType: str_starts_with($statusCode, '5.') ? BounceType::Hard : BounceType::Soft,
                statusCode: $statusCode,
            );
        }

        return $this->parseHeuristically($rawMessage, $recipient);
    }

    private function extractStatusCode(string $rawMessage): ?string
    {
        if (preg_match('/^Status:\s*(\d\.\d+\.\d+)/mi', $rawMessage, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractRecipient(string $rawMessage): ?string
    {
        if (preg_match('/^Final-Recipient:\s*rfc822;\s*(\S+)/mi', $rawMessage, $matches)) {
            return strtolower(trim($matches[1]));
        }

        if (preg_match('/^Original-Recipient:\s*rfc822;\s*(\S+)/mi', $rawMessage, $matches)) {
            return strtolower(trim($matches[1]));
        }

        return null;
    }

    private function parseHeuristically(string $rawMessage, ?string $recipient): ?ParsedBounce
    {
        if (! preg_match('/undeliver|delivery (status|failed)|mail delivery|returned to sender|delayed|could not be delivered/i', $rawMessage)) {
            return null;
        }

        $recipient ??= $this->guessRecipient($rawMessage);

        if ($recipient === null) {
            return null;
        }

        $bounceType = preg_match('/temporar|deferred|mailbox\s+full|try again/i', $rawMessage)
            ? BounceType::Soft
            : BounceType::Hard;

        return new ParsedBounce(recipient: $recipient, bounceType: $bounceType);
    }

    private function guessRecipient(string $rawMessage): ?string
    {
        preg_match_all('/[\w.+-]+@[\w.-]+\.\w+/i', $rawMessage, $matches);

        foreach ($matches[0] as $candidate) {
            $candidate = strtolower($candidate);

            if (preg_match('/^(postmaster|mailer-daemon|bounces)@/', $candidate)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }
}

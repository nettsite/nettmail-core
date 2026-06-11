<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

use DateTimeImmutable;

/**
 * Framework-agnostic double opt-in confirmation tokens. Self-contained
 * (HMAC-signed payload, no storage lookup), so both the Laravel and
 * WordPress adapters can generate and verify the same token format
 * without relying on Laravel's signed URLs.
 */
final class OptInTokenGenerator
{
    public function __construct(
        private readonly string $secret,
    ) {
    }

    public function generate(string $contactId, string $listId, DateTimeImmutable $expiresAt): string
    {
        $payload = $this->encode(json_encode([
            'contact_id' => $contactId,
            'list_id' => $listId,
            'expires_at' => $expiresAt->getTimestamp(),
        ]));

        return $payload.'.'.$this->sign($payload);
    }

    /**
     * Returns null if the token is malformed, has an invalid signature,
     * or has expired.
     */
    public function verify(string $token, ?DateTimeImmutable $now = null): ?OptInToken
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;

        if (! hash_equals($this->sign($payload), $signature)) {
            return null;
        }

        $data = json_decode($this->decode($payload), true);

        if (! is_array($data) || ! isset($data['contact_id'], $data['list_id'], $data['expires_at'])) {
            return null;
        }

        $expiresAt = (new DateTimeImmutable())->setTimestamp($data['expires_at']);

        if ($expiresAt < ($now ?? new DateTimeImmutable())) {
            return null;
        }

        return new OptInToken($data['contact_id'], $data['list_id'], $expiresAt);
    }

    private function sign(string $payload): string
    {
        return $this->encode(hash_hmac('sha256', $payload, $this->secret, true));
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'));
    }
}

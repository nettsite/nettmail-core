<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

/**
 * Framework-agnostic, non-expiring unsubscribe tokens. Unlike
 * {@see OptInTokenGenerator}, these never expire — unsubscribe links in
 * old emails must keep working. A `null` list id scopes the token to
 * "unsubscribe from all".
 */
final class UnsubscribeTokenGenerator
{
    public function __construct(
        private readonly string $secret,
    ) {
    }

    public function generate(string $contactId, ?string $listId = null): string
    {
        $payload = $this->encode(json_encode([
            'contact_id' => $contactId,
            'list_id' => $listId,
        ]));

        return $payload.'.'.$this->sign($payload);
    }

    /**
     * Returns null if the token is malformed or has an invalid signature.
     */
    public function verify(string $token): ?UnsubscribeToken
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

        if (! is_array($data) || ! array_key_exists('contact_id', $data) || ! array_key_exists('list_id', $data)) {
            return null;
        }

        return new UnsubscribeToken($data['contact_id'], $data['list_id']);
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

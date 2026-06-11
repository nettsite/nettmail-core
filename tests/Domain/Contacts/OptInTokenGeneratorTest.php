<?php

use Nettsite\NettMail\Core\Domain\Contacts\OptInTokenGenerator;

it('generates a token that verifies back to the same contact and list', function () {
    $generator = new OptInTokenGenerator('secret');
    $expiresAt = new DateTimeImmutable('2024-01-02T00:00:00+00:00');

    $token = $generator->generate('contact-1', 'list-1', $expiresAt);
    $payload = $generator->verify($token, new DateTimeImmutable('2024-01-01T00:00:00+00:00'));

    expect($payload)->not->toBeNull()
        ->and($payload->contactId)->toBe('contact-1')
        ->and($payload->listId)->toBe('list-1')
        ->and($payload->expiresAt->getTimestamp())->toBe($expiresAt->getTimestamp());
});

it('rejects an expired token', function () {
    $generator = new OptInTokenGenerator('secret');
    $expiresAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');

    $token = $generator->generate('contact-1', 'list-1', $expiresAt);

    expect($generator->verify($token, new DateTimeImmutable('2024-01-02T00:00:00+00:00')))->toBeNull();
});

it('rejects a tampered token', function () {
    $generator = new OptInTokenGenerator('secret');
    $token = $generator->generate('contact-1', 'list-1', new DateTimeImmutable('2024-01-02T00:00:00+00:00'));

    [$payload, $signature] = explode('.', $token);
    $tampered = $payload.'x.'.$signature;

    expect($generator->verify($tampered))->toBeNull();
});

it('rejects a token signed with a different secret', function () {
    $token = (new OptInTokenGenerator('secret-a'))->generate('contact-1', 'list-1', new DateTimeImmutable('2024-01-02T00:00:00+00:00'));

    expect((new OptInTokenGenerator('secret-b'))->verify($token))->toBeNull();
});

it('rejects a malformed token', function () {
    expect((new OptInTokenGenerator('secret'))->verify('not-a-valid-token'))->toBeNull();
});

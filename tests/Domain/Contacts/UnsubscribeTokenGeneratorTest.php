<?php

use Nettsite\NettMail\Core\Domain\Contacts\UnsubscribeTokenGenerator;

it('round-trips a list-scoped token', function () {
    $generator = new UnsubscribeTokenGenerator('secret');

    $token = $generator->generate('contact-1', 'list-1');
    $verified = $generator->verify($token);

    expect($verified)->not->toBeNull()
        ->and($verified->contactId)->toBe('contact-1')
        ->and($verified->listId)->toBe('list-1');
});

it('round-trips an all-scope token with a null list id', function () {
    $generator = new UnsubscribeTokenGenerator('secret');

    $token = $generator->generate('contact-1');
    $verified = $generator->verify($token);

    expect($verified)->not->toBeNull()
        ->and($verified->contactId)->toBe('contact-1')
        ->and($verified->listId)->toBeNull();
});

it('rejects a tampered token', function () {
    $generator = new UnsubscribeTokenGenerator('secret');

    $token = $generator->generate('contact-1', 'list-1');
    $tampered = substr($token, 0, -1).(substr($token, -1) === 'a' ? 'b' : 'a');

    expect($generator->verify($tampered))->toBeNull();
});

it('rejects malformed tokens', function () {
    $generator = new UnsubscribeTokenGenerator('secret');

    expect($generator->verify('not-a-valid-token'))->toBeNull();
});

it('rejects tokens signed with a different (rotated) secret', function () {
    $generator = new UnsubscribeTokenGenerator('secret');
    $rotated = new UnsubscribeTokenGenerator('new-secret');

    $token = $generator->generate('contact-1', 'list-1');

    // Documented expected behaviour: rotating the secret invalidates
    // outstanding unsubscribe links.
    expect($rotated->verify($token))->toBeNull();
});

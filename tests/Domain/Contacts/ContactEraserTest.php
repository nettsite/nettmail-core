<?php

use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Domain\Contacts\ContactEraser;

it('replaces email with a hash and clears pii', function () {
    $contact = new Contact(
        id: '1',
        email: 'jane@example.com',
        firstName: 'Jane',
        lastName: 'Doe',
        phone: '+27123456789',
        metadata: ['source' => 'import'],
        sourceType: 'merlin',
        sourceId: 'abc-123',
    );

    (new ContactEraser())->erase($contact);

    expect($contact->id)->toBe('1')
        ->and($contact->email)->toBe('erased-'.hash('sha256', 'jane@example.com').'@erased.invalid')
        ->and($contact->firstName)->toBeNull()
        ->and($contact->lastName)->toBeNull()
        ->and($contact->phone)->toBeNull()
        ->and($contact->metadata)->toBe([])
        ->and($contact->sourceType)->toBeNull()
        ->and($contact->sourceId)->toBeNull();
});

it('produces a deterministic, collision-free hash per original email', function () {
    $first = new Contact(id: '1', email: 'jane@example.com');
    $second = new Contact(id: '2', email: 'john@example.com');

    $eraser = new ContactEraser();
    $eraser->erase($first);
    $eraser->erase($second);

    expect($first->email)->not->toBe($second->email);
});

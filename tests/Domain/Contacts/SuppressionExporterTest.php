<?php

use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Domain\Contacts\SuppressionExporter;

it('exports hard bounces, complaints, and unsubscribes', function () {
    $hardBounced = new Contact(
        id: '1',
        email: 'hard@example.com',
        bounceType: BounceType::Hard,
        bouncedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
    );

    $complained = new Contact(
        id: '2',
        email: 'complaint@example.com',
        bounceType: BounceType::Complaint,
        bouncedAt: new DateTimeImmutable('2024-01-02T00:00:00+00:00'),
    );

    $unsubscribed = new Contact(
        id: '3',
        email: 'unsub@example.com',
        globalUnsubscribedAt: new DateTimeImmutable('2024-01-03T00:00:00+00:00'),
    );

    $csv = (new SuppressionExporter())->export([$hardBounced, $complained, $unsubscribed]);
    $rows = array_map(fn (string $line): array => str_getcsv($line, escape: ''), array_filter(explode("\n", trim($csv))));

    expect($rows)->toBe([
        ['email', 'reason', 'suppressed_at'],
        ['hard@example.com', 'hard_bounce', '2024-01-01T00:00:00+00:00'],
        ['complaint@example.com', 'complaint', '2024-01-02T00:00:00+00:00'],
        ['unsub@example.com', 'unsubscribed', '2024-01-03T00:00:00+00:00'],
    ]);
});

it('skips contacts that are not suppressed', function () {
    $active = new Contact(id: '1', email: 'active@example.com');
    $softBounced = new Contact(id: '2', email: 'soft@example.com', bounceType: BounceType::Soft);

    $csv = (new SuppressionExporter())->export([$active, $softBounced]);
    $rows = array_filter(explode("\n", trim($csv)));

    expect($rows)->toHaveCount(1);
});

<?php

use Nettsite\NettMail\Core\Domain\Bounces\DsnParser;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;

function bounceFixture(string $name): string
{
    return file_get_contents(__DIR__.'/../../Fixtures/Bounces/'.$name);
}

it('parses an RFC 3464 hard bounce', function () {
    $parsed = (new DsnParser())->parse(bounceFixture('hard-bounce.eml'));

    expect($parsed)->not->toBeNull()
        ->and($parsed->recipient)->toBe('nobody@invalid-domain.test')
        ->and($parsed->statusCode)->toBe('5.1.1')
        ->and($parsed->bounceType)->toBe(BounceType::Hard);
});

it('parses an RFC 3464 soft bounce', function () {
    $parsed = (new DsnParser())->parse(bounceFixture('soft-bounce.eml'));

    expect($parsed)->not->toBeNull()
        ->and($parsed->recipient)->toBe('fullmailbox@example.test')
        ->and($parsed->statusCode)->toBe('4.2.2')
        ->and($parsed->bounceType)->toBe(BounceType::Soft);
});

it('falls back to heuristic parsing for a non-standard hard bounce', function () {
    $parsed = (new DsnParser())->parse(bounceFixture('heuristic-hard-bounce.eml'));

    expect($parsed)->not->toBeNull()
        ->and($parsed->recipient)->toBe('someone@example.test')
        ->and($parsed->statusCode)->toBeNull()
        ->and($parsed->bounceType)->toBe(BounceType::Hard);
});

it('falls back to heuristic parsing for a non-standard soft bounce', function () {
    $parsed = (new DsnParser())->parse(bounceFixture('heuristic-soft-bounce.eml'));

    expect($parsed)->not->toBeNull()
        ->and($parsed->recipient)->toBe('someone@example.test')
        ->and($parsed->bounceType)->toBe(BounceType::Soft);
});

it('returns null for messages that are not bounces', function () {
    $parsed = (new DsnParser())->parse(bounceFixture('unrecognised.eml'));

    expect($parsed)->toBeNull();
});

<?php

use Nettsite\NettMail\Core\Drivers\Support\MessageIdNormalizer;

it('strips angle brackets from a message id', function () {
    expect(MessageIdNormalizer::strip('<abc@domain.com>'))->toBe('abc@domain.com');
});

it('returns null unchanged', function () {
    expect(MessageIdNormalizer::strip(null))->toBeNull();
});

it('leaves an id without angle brackets unchanged', function () {
    expect(MessageIdNormalizer::strip('abc@domain.com'))->toBe('abc@domain.com');
});

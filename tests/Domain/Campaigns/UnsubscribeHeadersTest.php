<?php

use Nettsite\NettMail\Core\Domain\Campaigns\UnsubscribeHeaders;

it('builds headers with both https and mailto', function () {
    $headers = (new UnsubscribeHeaders())->build('https://example.com/unsubscribe/abc', 'unsubscribe@example.com');

    expect($headers)->toBe([
        'List-Unsubscribe' => '<https://example.com/unsubscribe/abc>, <mailto:unsubscribe@example.com>',
        'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
    ]);
});

it('builds headers with only the https url', function () {
    $headers = (new UnsubscribeHeaders())->build('https://example.com/unsubscribe/abc');

    expect($headers)->toBe([
        'List-Unsubscribe' => '<https://example.com/unsubscribe/abc>',
        'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
    ]);
});

<?php

use Nettsite\NettMail\Core\Mail\SendResult;

it('builds a successful result', function () {
    $result = SendResult::success('msg-123');

    expect($result->success)->toBeTrue()
        ->and($result->messageId)->toBe('msg-123')
        ->and($result->error)->toBeNull();
});

it('builds a failure result', function () {
    $result = SendResult::failure('connection refused');

    expect($result->success)->toBeFalse()
        ->and($result->messageId)->toBeNull()
        ->and($result->error)->toBe('connection refused');
});

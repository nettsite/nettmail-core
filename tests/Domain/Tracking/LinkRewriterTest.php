<?php

use Nettsite\NettMail\Core\Domain\Tracking\LinkRewriter;

it('rewrites links to a click-tracking redirect', function () {
    $rewriter = new LinkRewriter('https://yourapp.com');

    $html = $rewriter->rewrite('<p><a href="https://example.com/sale">Shop now</a></p>', 'send-token');

    $hash = $rewriter->linkHash('https://example.com/sale');

    expect($html)->toContain('href="https://yourapp.com/nettmail/track/click/send-token/'.$hash.'"')
        ->and($html)->toContain('Shop now');
});

it('does not rewrite the unsubscribe merge tag link', function () {
    $rewriter = new LinkRewriter('https://yourapp.com');

    $html = $rewriter->rewrite('<p><a href="{{unsubscribe_url}}">Unsubscribe</a></p>', 'send-token');

    expect($html)->not->toContain('track/click');
});

it('does not rewrite explicitly skipped urls', function () {
    $rewriter = new LinkRewriter('https://yourapp.com');

    $html = $rewriter->rewrite(
        '<p><a href="https://example.com/keep">Keep</a> <a href="https://example.com/wrap">Wrap</a></p>',
        'send-token',
        skipUrls: ['https://example.com/keep'],
    );

    expect($html)->toContain('href="https://example.com/keep"')
        ->and($html)->toContain('href="https://yourapp.com/nettmail/track/click/send-token/'.$rewriter->linkHash('https://example.com/wrap').'"');
});

it('leaves links without an href untouched', function () {
    $rewriter = new LinkRewriter('https://yourapp.com');

    $html = $rewriter->rewrite('<p><a name="anchor">Anchor</a></p>', 'send-token');

    expect($html)->not->toContain('track/click');
});

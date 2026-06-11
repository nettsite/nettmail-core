<?php

use Nettsite\NettMail\Core\Domain\Tracking\LinkRewriter;

it('rewrites links to a click-tracking redirect', function () {
    $rewriter = new LinkRewriter('https://yourapp.com');

    $result = $rewriter->rewrite('<p><a href="https://example.com/sale">Shop now</a></p>', 'send-token');

    $hash = $rewriter->linkHash('https://example.com/sale');

    expect($result->html)->toContain('href="https://yourapp.com/nettmail/track/click/send-token/'.$hash.'"')
        ->and($result->html)->toContain('Shop now')
        ->and($result->links)->toBe([$hash => 'https://example.com/sale']);
});

it('does not rewrite the unsubscribe merge tag link', function () {
    $rewriter = new LinkRewriter('https://yourapp.com');

    $result = $rewriter->rewrite('<p><a href="{{unsubscribe_url}}">Unsubscribe</a></p>', 'send-token');

    expect($result->html)->not->toContain('track/click')
        ->and($result->links)->toBe([]);
});

it('does not rewrite explicitly skipped urls', function () {
    $rewriter = new LinkRewriter('https://yourapp.com');

    $result = $rewriter->rewrite(
        '<p><a href="https://example.com/keep">Keep</a> <a href="https://example.com/wrap">Wrap</a></p>',
        'send-token',
        skipUrls: ['https://example.com/keep'],
    );

    $wrapHash = $rewriter->linkHash('https://example.com/wrap');

    expect($result->html)->toContain('href="https://example.com/keep"')
        ->and($result->html)->toContain('href="https://yourapp.com/nettmail/track/click/send-token/'.$wrapHash.'"')
        ->and($result->links)->toBe([$wrapHash => 'https://example.com/wrap']);
});

it('leaves links without an href untouched', function () {
    $rewriter = new LinkRewriter('https://yourapp.com');

    $result = $rewriter->rewrite('<p><a name="anchor">Anchor</a></p>', 'send-token');

    expect($result->html)->not->toContain('track/click')
        ->and($result->links)->toBe([]);
});

it('preserves the full document including doctype, head and styles', function () {
    $rewriter = new LinkRewriter('https://yourapp.com');

    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head><style>body { color: red; }</style></head>
    <body><p><a href="https://example.com/sale">Shop now</a></p></body>
    </html>
    HTML;

    $result = $rewriter->rewrite($html, 'send-token');

    $hash = $rewriter->linkHash('https://example.com/sale');

    expect($result->html)->toContain('<!DOCTYPE html')
        ->and($result->html)->toContain('<head>')
        ->and($result->html)->toContain('<style>body { color: red; }</style>')
        ->and($result->html)->toContain('href="https://yourapp.com/nettmail/track/click/send-token/'.$hash.'"')
        ->and($result->links)->toBe([$hash => 'https://example.com/sale']);
});

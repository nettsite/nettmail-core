<?php

use Nettsite\NettMail\Core\Domain\Templates\PlainTextConverter;

it('converts headings, paragraphs and line breaks to newlines', function () {
    $html = '<h1>Welcome</h1><p>Hello<br>World</p>';

    expect((new PlainTextConverter())->convert($html))->toBe("Welcome\n\nHello\nWorld");
});

it('renders links with their url', function () {
    $html = '<p>Visit <a href="https://example.com">our site</a></p>';

    expect((new PlainTextConverter())->convert($html))->toBe('Visit our site (https://example.com)');
});

it('does not repeat the url when the link text is the url itself', function () {
    $html = '<p><a href="https://example.com">https://example.com</a></p>';

    expect((new PlainTextConverter())->convert($html))->toBe('https://example.com');
});

it('decodes html entities', function () {
    $html = '<p>Caf&eacute; &amp; Bar</p>';

    expect((new PlainTextConverter())->convert($html))->toBe('Café & Bar');
});

it('collapses excess blank lines', function () {
    $html = '<p>One</p><div></div><p>Two</p>';

    expect((new PlainTextConverter())->convert($html))->toBe("One\n\nTwo");
});

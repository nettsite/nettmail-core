<?php

use Nettsite\NettMail\Core\Domain\Tracking\PixelGenerator;

it('builds a pixel url for a send token', function () {
    $generator = new PixelGenerator('https://yourapp.com');

    expect($generator->pixelUrl('abc123'))->toBe('https://yourapp.com/nettmail/track/open/abc123');
});

it('strips a trailing slash from the base url', function () {
    $generator = new PixelGenerator('https://yourapp.com/');

    expect($generator->pixelUrl('abc123'))->toBe('https://yourapp.com/nettmail/track/open/abc123');
});

it('appends the pixel before the closing body tag', function () {
    $generator = new PixelGenerator('https://yourapp.com');

    $html = $generator->appendToHtml('<html><body><p>Hello</p></body></html>', 'abc123');

    expect($html)->toBe('<html><body><p>Hello</p><img src="https://yourapp.com/nettmail/track/open/abc123" width="1" height="1" alt="" style="display:none;" /></body></html>');
});

it('appends the pixel to the end when there is no body tag', function () {
    $generator = new PixelGenerator('https://yourapp.com');

    $html = $generator->appendToHtml('<p>Hello</p>', 'abc123');

    expect($html)->toBe('<p>Hello</p><img src="https://yourapp.com/nettmail/track/open/abc123" width="1" height="1" alt="" style="display:none;" />');
});

it('inserts the pixel literally even when the base url contains backreference-like sequences', function () {
    $generator = new PixelGenerator('https://yourapp.com/$0/\\1');

    $html = $generator->appendToHtml('<html><body><p>Hello</p></body></html>', 'abc123');

    expect($html)->toBe('<html><body><p>Hello</p><img src="https://yourapp.com/$0/\\1/nettmail/track/open/abc123" width="1" height="1" alt="" style="display:none;" /></body></html>');
});

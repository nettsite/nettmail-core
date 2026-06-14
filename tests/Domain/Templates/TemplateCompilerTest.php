<?php

use Nettsite\NettMail\Core\Domain\Templates\MissingUnsubscribeLinkException;
use Nettsite\NettMail\Core\Domain\Templates\TemplateCompiler;
use Nettsite\NettMail\Core\Domain\Templates\TemplateType;

it('compiles a transactional template without requiring an unsubscribe link', function () {
    $compiled = (new TemplateCompiler())->compile('<p>Hello {{first_name}}</p>', TemplateType::Transactional);

    expect($compiled->html)->toBe('<p>Hello {{first_name}}</p>')
        ->and($compiled->plainText)->toBe('Hello {{first_name}}');
});

it('throws when a broadcast template is missing the unsubscribe link', function () {
    (new TemplateCompiler())->compile('<p>Hello</p>', TemplateType::Broadcast);
})->throws(MissingUnsubscribeLinkException::class);

it('compiles a broadcast template containing the unsubscribe link', function () {
    $html = '<p>Hello</p><p><a href="{{unsubscribe_url}}">Unsubscribe</a></p>';

    $compiled = (new TemplateCompiler())->compile($html, TemplateType::Broadcast);

    expect($compiled->html)->toBe($html)
        ->and($compiled->plainText)->toContain('Unsubscribe (')
        ->and((new TemplateCompiler())->hasUnsubscribeLink($html))->toBeTrue();
});

it('inlines style block rules into element style attributes', function () {
    $html = '<style>p { color: red; }</style><p>Hello {{first_name}}</p>';

    $compiled = (new TemplateCompiler())->compile($html, TemplateType::Transactional);

    expect($compiled->html)->toContain('style="color: red;"');
});

it('leaves templates without a style block unchanged', function () {
    $compiled = (new TemplateCompiler())->compile('<p>Hello {{first_name}}</p>', TemplateType::Transactional);

    expect($compiled->html)->toBe('<p>Hello {{first_name}}</p>');
});

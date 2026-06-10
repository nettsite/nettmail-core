<?php

use Nettsite\NettMail\Core\Domain\Templates\MergeTagRenderer;

it('replaces known merge tags', function () {
    $renderer = new MergeTagRenderer();

    expect($renderer->render('Hello {{first_name}}!', ['first_name' => 'Jane']))->toBe('Hello Jane!');
});

it('leaves unknown merge tags untouched', function () {
    $renderer = new MergeTagRenderer();

    expect($renderer->render('Hello {{first_name}}!', []))->toBe('Hello {{first_name}}!');
});

it('tolerates whitespace inside braces', function () {
    $renderer = new MergeTagRenderer();

    expect($renderer->render('Hi {{ first_name }}', ['first_name' => 'Jane']))->toBe('Hi Jane');
});

it('replaces multiple and repeated tags', function () {
    $renderer = new MergeTagRenderer();

    expect($renderer->render('{{first_name}} {{company}}, {{first_name}}!', [
        'first_name' => 'Jane',
        'company' => 'Acme',
    ]))->toBe('Jane Acme, Jane!');
});

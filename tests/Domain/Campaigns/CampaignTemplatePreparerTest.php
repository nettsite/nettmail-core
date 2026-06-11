<?php

use Nettsite\NettMail\Core\Domain\Campaigns\CampaignTemplatePreparer;
use Nettsite\NettMail\Core\Domain\Templates\CompiledTemplate;
use Nettsite\NettMail\Core\Domain\Tracking\LinkRewriter;
use Nettsite\NettMail\Core\Domain\Tracking\PixelGenerator;

it('prepares a campaign template with tracking links and a pixel', function () {
    $preparer = new CampaignTemplatePreparer(
        new LinkRewriter('https://example.com'),
        new PixelGenerator('https://example.com'),
    );

    $template = new CompiledTemplate(
        html: '<html><body><p>Hi</p><a href="https://example.com/page">Visit</a></body></html>',
        plainText: 'Hi - https://example.com/page',
    );

    $prepared = $preparer->prepare($template);

    expect($prepared->html)->toContain($prepared->sendTokenPlaceholder)
        ->and($prepared->html)->toContain('/nettmail/track/click/'.urlencode($prepared->sendTokenPlaceholder).'/')
        ->and($prepared->html)->toContain('/nettmail/track/open/'.$prepared->sendTokenPlaceholder)
        ->and($prepared->links)->not->toBe([])
        ->and($prepared->text)->toBe('Hi - https://example.com/page');
});

it('generates a different placeholder on each call', function () {
    $preparer = new CampaignTemplatePreparer(
        new LinkRewriter('https://example.com'),
        new PixelGenerator('https://example.com'),
    );

    $template = new CompiledTemplate(
        html: '<html><body><a href="https://example.com/page">Visit</a></body></html>',
        plainText: 'Hi',
    );

    $first = $preparer->prepare($template);
    $second = $preparer->prepare($template);

    expect($first->sendTokenPlaceholder)->not->toBe($second->sendTokenPlaceholder);
});

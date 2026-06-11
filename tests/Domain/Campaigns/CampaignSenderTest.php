<?php

use Nettsite\NettMail\Core\Domain\Campaigns\CampaignSender;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignTemplatePreparer;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Domain\Templates\CompiledTemplate;
use Nettsite\NettMail\Core\Domain\Tracking\LinkRewriter;
use Nettsite\NettMail\Core\Domain\Tracking\PixelGenerator;

it('skips suppressed contacts', function () {
    $sender = new CampaignSender();

    $active = new Contact(id: '1', email: 'jane@example.com');
    $hardBounced = new Contact(id: '2', email: 'bounced@example.com', bounceType: BounceType::Hard);

    expect($sender->shouldSend($active))->toBeTrue()
        ->and($sender->shouldSend($hardBounced))->toBeFalse();
});

it('renders merge tags in the subject and template for a contact', function () {
    $sender = new CampaignSender();
    $preparer = new CampaignTemplatePreparer(
        new LinkRewriter('https://example.com'),
        new PixelGenerator('https://example.com'),
    );
    $template = new CompiledTemplate(
        html: '<p>Hi {{first_name}}</p>',
        plainText: 'Hi {{first_name}}',
    );
    $prepared = $preparer->prepare($template);

    $rendered = $sender->renderForContact('Hello {{first_name}}', $prepared, 'tok123', ['first_name' => 'Jane']);

    expect($rendered['subject'])->toBe('Hello Jane')
        ->and($rendered['html'])->toContain('<p>Hi Jane</p>')
        ->and($rendered['html'])->toContain('tok123')
        ->and($rendered['html'])->not->toContain($prepared->sendTokenPlaceholder)
        ->and($rendered['text'])->toBe('Hi Jane');
});

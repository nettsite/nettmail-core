<?php

use Nettsite\NettMail\Core\Domain\Campaigns\CampaignSender;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Domain\Templates\CompiledTemplate;

it('skips suppressed contacts', function () {
    $sender = new CampaignSender();

    $active = new Contact(id: '1', email: 'jane@example.com');
    $hardBounced = new Contact(id: '2', email: 'bounced@example.com', bounceType: BounceType::Hard);

    expect($sender->shouldSend($active))->toBeTrue()
        ->and($sender->shouldSend($hardBounced))->toBeFalse();
});

it('renders merge tags in the subject and template for a contact', function () {
    $sender = new CampaignSender();
    $template = new CompiledTemplate(
        html: '<p>Hi {{first_name}}</p>',
        plainText: 'Hi {{first_name}}',
    );

    $rendered = $sender->renderForContact('Hello {{first_name}}', $template, ['first_name' => 'Jane']);

    expect($rendered['subject'])->toBe('Hello Jane')
        ->and($rendered['html'])->toBe('<p>Hi Jane</p>')
        ->and($rendered['text'])->toBe('Hi Jane');
});

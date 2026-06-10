<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns;

use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Domain\Templates\CompiledTemplate;
use Nettsite\NettMail\Core\Domain\Templates\MergeTagRenderer;

final class CampaignSender
{
    public function __construct(
        private readonly MergeTagRenderer $mergeTagRenderer = new MergeTagRenderer(),
    ) {
    }

    /**
     * Broadcast sends respect the global suppression list — bounced,
     * globally unsubscribed, and complaint-flagged contacts are skipped.
     */
    public function shouldSend(Contact $contact): bool
    {
        return ! $contact->isSuppressed();
    }

    /**
     * Renders the subject and compiled template for a single recipient,
     * substituting merge tags (first name, custom fields, etc.).
     *
     * @param array<string, string|int|float> $mergeFields
     * @return array{subject: string, html: string, text: string}
     */
    public function renderForContact(string $subject, CompiledTemplate $template, array $mergeFields): array
    {
        return [
            'subject' => $this->mergeTagRenderer->render($subject, $mergeFields),
            'html' => $this->mergeTagRenderer->render($template->html, $mergeFields),
            'text' => $this->mergeTagRenderer->render($template->plainText, $mergeFields),
        ];
    }
}

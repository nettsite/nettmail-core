<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns;

use Nettsite\NettMail\Core\Domain\Contacts\Contact;
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
     * Renders the subject and prepared template for a single recipient,
     * substituting merge tags (first name, custom fields, etc.) and
     * swapping in the recipient's send token.
     *
     * @param array<string, string|int|float> $mergeFields
     * @return array{subject: string, html: string, text: string}
     */
    public function renderForContact(string $subject, PreparedCampaignTemplate $prepared, string $sendToken, array $mergeFields): array
    {
        $html = $this->mergeTagRenderer->render($prepared->html, $mergeFields);
        $html = str_replace($prepared->sendTokenPlaceholder, $sendToken, $html);

        return [
            'subject' => $this->mergeTagRenderer->render($subject, $mergeFields),
            'html' => $html,
            'text' => $this->mergeTagRenderer->render($prepared->text, $mergeFields),
        ];
    }
}

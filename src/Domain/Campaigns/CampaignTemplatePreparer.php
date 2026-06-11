<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns;

use Nettsite\NettMail\Core\Domain\Templates\CompiledTemplate;
use Nettsite\NettMail\Core\Domain\Tracking\LinkRewriter;
use Nettsite\NettMail\Core\Domain\Tracking\PixelGenerator;

/**
 * Rewrites links and inserts the open-tracking pixel once per campaign,
 * using a random placeholder for the send token. Per-recipient rendering
 * then only needs to substitute merge tags and swap the placeholder for
 * the recipient's send token.
 */
final class CampaignTemplatePreparer
{
    public function __construct(
        private readonly LinkRewriter $linkRewriter,
        private readonly PixelGenerator $pixelGenerator,
    ) {
    }

    public function prepare(CompiledTemplate $template): PreparedCampaignTemplate
    {
        $placeholder = '{{__send_token_'.bin2hex(random_bytes(8)).'}}';

        $rewritten = $this->linkRewriter->rewrite($template->html, $placeholder);
        $html = $this->pixelGenerator->appendToHtml($rewritten->html, $placeholder);

        return new PreparedCampaignTemplate($html, $template->plainText, $rewritten->links, $placeholder);
    }
}

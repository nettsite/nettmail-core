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

    /**
     * @param ?string $physicalAddress CAN-SPAM footer text. Appended before
     *                                  `</body>` unless already present in the
     *                                  HTML (template author placed it manually).
     */
    public function prepare(CompiledTemplate $template, ?string $physicalAddress = null): PreparedCampaignTemplate
    {
        $placeholder = '{{__send_token_'.bin2hex(random_bytes(8)).'}}';

        $rewritten = $this->linkRewriter->rewrite($template->html, $placeholder);
        $html = $this->pixelGenerator->appendToHtml($rewritten->html, $placeholder);
        $text = $template->plainText;

        if ($physicalAddress !== null && ! str_contains($html, $physicalAddress)) {
            $html = $this->appendFooter($html, $physicalAddress);
            $text .= "\n\n".$physicalAddress;
        }

        return new PreparedCampaignTemplate($html, $text, $rewritten->links, $placeholder);
    }

    private function appendFooter(string $html, string $physicalAddress): string
    {
        $footer = '<p>'.htmlspecialchars($physicalAddress, ENT_QUOTES).'</p>';

        if (preg_match('/<\/body>/i', $html) === 1) {
            return preg_replace_callback('/<\/body>/i', fn (array $m): string => $footer.$m[0], $html, 1);
        }

        return $html.$footer;
    }
}

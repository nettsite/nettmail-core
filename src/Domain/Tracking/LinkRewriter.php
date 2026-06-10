<?php

namespace Nettsite\NettMail\Core\Domain\Tracking;

use DOMDocument;
use Nettsite\NettMail\Core\Domain\Templates\TemplateCompiler;

/**
 * Rewrites `<a href>` links to pass through a click-tracking redirect.
 * Unsubscribe links are left untouched so they keep working without
 * tracking redirects (per compliance requirements).
 */
final class LinkRewriter
{
    public function __construct(
        private readonly string $baseUrl,
    ) {
    }

    /**
     * @param array<int, string> $skipUrls additional href values to leave unrewritten
     */
    public function rewrite(string $html, string $sendToken, array $skipUrls = []): string
    {
        $skipUrls[] = '{{'.TemplateCompiler::UNSUBSCRIBE_MERGE_TAG.'}}';

        $document = new DOMDocument();

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        foreach ($document->getElementsByTagName('a') as $link) {
            $href = $link->getAttribute('href');

            if ($href === '' || in_array($href, $skipUrls, true)) {
                continue;
            }

            $link->setAttribute('href', $this->trackingUrl($sendToken, $href));
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $html;
        }

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    public function linkHash(string $url): string
    {
        return substr(hash('sha256', $url), 0, 16);
    }

    private function trackingUrl(string $sendToken, string $href): string
    {
        return rtrim($this->baseUrl, '/').'/nettmail/track/click/'.$sendToken.'/'.$this->linkHash($href);
    }
}

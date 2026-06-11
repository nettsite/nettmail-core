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
    public function rewrite(string $html, string $sendToken, array $skipUrls = []): LinkRewriteResult
    {
        $skipUrls[] = '{{'.TemplateCompiler::UNSUBSCRIBE_MERGE_TAG.'}}';

        $isFullDocument = stripos($html, '<html') !== false;

        $document = new DOMDocument();

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $links = [];

        foreach ($document->getElementsByTagName('a') as $link) {
            $href = $link->getAttribute('href');

            if ($href === '' || in_array($href, $skipUrls, true)) {
                continue;
            }

            $hash = $this->linkHash($href);
            $links[$hash] = $href;

            $link->setAttribute('href', $this->trackingUrl($sendToken, $hash));
        }

        if ($isFullDocument) {
            foreach ($document->childNodes as $node) {
                if ($node->nodeType === XML_PI_NODE) {
                    $document->removeChild($node);

                    break;
                }
            }

            return new LinkRewriteResult($document->saveHTML(), $links);
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return new LinkRewriteResult($html, $links);
        }

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return new LinkRewriteResult($output, $links);
    }

    public function linkHash(string $url): string
    {
        return substr(hash('sha256', $url), 0, 16);
    }

    private function trackingUrl(string $sendToken, string $hash): string
    {
        return rtrim($this->baseUrl, '/').'/nettmail/track/click/'.$sendToken.'/'.$hash;
    }
}

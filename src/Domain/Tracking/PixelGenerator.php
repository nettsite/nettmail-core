<?php

namespace Nettsite\NettMail\Core\Domain\Tracking;

final class PixelGenerator
{
    public function __construct(
        private readonly string $baseUrl,
    ) {
    }

    public function pixelUrl(string $sendToken): string
    {
        return rtrim($this->baseUrl, '/').'/nettmail/track/open/'.$sendToken;
    }

    public function imgTag(string $sendToken): string
    {
        $url = $this->pixelUrl($sendToken);

        return '<img src="'.$url.'" width="1" height="1" alt="" style="display:none;" />';
    }

    /**
     * Appends the tracking pixel just before `</body>`, or to the end of
     * the document if no body tag is present.
     */
    public function appendToHtml(string $html, string $sendToken): string
    {
        $tag = $this->imgTag($sendToken);

        if (preg_match('/<\/body>/i', $html) === 1) {
            return preg_replace('/<\/body>/i', $tag.'</body>', $html, 1);
        }

        return $html.$tag;
    }
}

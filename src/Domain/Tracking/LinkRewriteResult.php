<?php

namespace Nettsite\NettMail\Core\Domain\Tracking;

final readonly class LinkRewriteResult
{
    /**
     * @param array<string, string> $links link hash => original URL
     */
    public function __construct(
        public string $html,
        public array $links,
    ) {
    }
}

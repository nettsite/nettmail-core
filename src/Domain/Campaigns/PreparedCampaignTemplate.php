<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns;

final readonly class PreparedCampaignTemplate
{
    /**
     * @param array<string, string> $links link hash => original URL
     */
    public function __construct(
        public string $html,
        public string $text,
        public array $links,
        public string $sendTokenPlaceholder,
    ) {
    }
}

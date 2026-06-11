<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns;

/**
 * Builds RFC 8058 one-click unsubscribe headers (`List-Unsubscribe` +
 * `List-Unsubscribe-Post`) for merging into `EmailMessage::headers`.
 */
final class UnsubscribeHeaders
{
    /**
     * @return array<string, string>
     */
    public function build(string $httpsUrl, ?string $mailto = null): array
    {
        $value = '<'.$httpsUrl.'>';

        if ($mailto !== null) {
            $value .= ', <mailto:'.$mailto.'>';
        }

        return [
            'List-Unsubscribe' => $value,
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ];
    }
}

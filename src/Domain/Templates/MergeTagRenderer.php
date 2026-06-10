<?php

namespace Nettsite\NettMail\Core\Domain\Templates;

final class MergeTagRenderer
{
    /**
     * Replaces `{{tag}}` placeholders with the matching value. Unknown
     * tags are left untouched so missing data is visible rather than
     * silently dropped.
     *
     * @param array<string, string|int|float> $values
     */
    public function render(string $content, array $values): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            fn (array $matches): string => array_key_exists($matches[1], $values)
                ? (string) $values[$matches[1]]
                : $matches[0],
            $content,
        );
    }
}

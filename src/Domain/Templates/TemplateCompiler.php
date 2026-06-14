<?php

namespace Nettsite\NettMail\Core\Domain\Templates;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

final class TemplateCompiler
{
    public const UNSUBSCRIBE_MERGE_TAG = 'unsubscribe_url';

    public function __construct(
        private readonly PlainTextConverter $plainTextConverter = new PlainTextConverter(),
        private readonly CssToInlineStyles $cssInliner = new CssToInlineStyles(),
    ) {
    }

    /**
     * @throws MissingUnsubscribeLinkException
     */
    public function compile(string $html, TemplateType $type): CompiledTemplate
    {
        if ($type === TemplateType::Broadcast && ! $this->hasUnsubscribeLink($html)) {
            throw new MissingUnsubscribeLinkException();
        }

        $html = $this->inlineStyles($html);

        return new CompiledTemplate(
            html: $html,
            plainText: $this->plainTextConverter->convert($html),
        );
    }

    public function hasUnsubscribeLink(string $html): bool
    {
        return str_contains($html, '{{'.self::UNSUBSCRIBE_MERGE_TAG.'}}');
    }

    /**
     * Inlines <style> block rules into element style attributes for email
     * client compatibility. Templates without a <style> block (e.g. raw
     * HTML) are returned unchanged.
     */
    public function inlineStyles(string $html): string
    {
        if (! str_contains($html, '<style')) {
            return $html;
        }

        return $this->cssInliner->convert($html);
    }
}

<?php

namespace Nettsite\NettMail\Core\Domain\Templates;

final class TemplateCompiler
{
    public const UNSUBSCRIBE_MERGE_TAG = 'unsubscribe_url';

    public function __construct(
        private readonly PlainTextConverter $plainTextConverter = new PlainTextConverter(),
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

        return new CompiledTemplate(
            html: $html,
            plainText: $this->plainTextConverter->convert($html),
        );
    }

    public function hasUnsubscribeLink(string $html): bool
    {
        return str_contains($html, '{{'.self::UNSUBSCRIBE_MERGE_TAG.'}}');
    }
}

<?php

namespace Nettsite\NettMail\Core\Domain\Templates;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class PlainTextConverter
{
    private const BLOCK_TAGS = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'tr', 'table'];

    public function convert(string $html): string
    {
        $document = new DOMDocument();

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $body = $document->getElementsByTagName('body')->item(0) ?? $document;

        $text = $this->renderNode($body);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/ ?\n ?/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function renderNode(DOMNode $node): string
    {
        $output = '';

        foreach ($node->childNodes as $child) {
            $output .= match (true) {
                $child instanceof DOMText => $child->wholeText,
                $child instanceof DOMElement && $child->nodeName === 'br' => "\n",
                $child instanceof DOMElement && $child->nodeName === 'a' => $this->renderLink($child),
                $child instanceof DOMElement && in_array($child->nodeName, self::BLOCK_TAGS, true)
                    => "\n".$this->renderNode($child)."\n",
                default => $this->renderNode($child),
            };
        }

        return html_entity_decode($output, ENT_QUOTES | ENT_HTML5);
    }

    private function renderLink(DOMElement $link): string
    {
        $href = $link->getAttribute('href');
        $text = trim($this->renderNode($link));

        if ($href === '' || $href === $text) {
            return $text;
        }

        return "{$text} ({$href})";
    }
}

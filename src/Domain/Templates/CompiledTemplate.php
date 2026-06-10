<?php

namespace Nettsite\NettMail\Core\Domain\Templates;

final readonly class CompiledTemplate
{
    public function __construct(
        public string $html,
        public string $plainText,
    ) {
    }
}

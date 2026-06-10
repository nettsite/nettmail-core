<?php

namespace Nettsite\NettMail\Core\Domain\Templates;

enum TemplateType: string
{
    case Transactional = 'transactional';
    case Broadcast = 'broadcast';
}

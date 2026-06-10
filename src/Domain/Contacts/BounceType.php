<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

enum BounceType: string
{
    case Hard = 'hard';
    case Soft = 'soft';
    case Complaint = 'complaint';
}

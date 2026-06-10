<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

enum SuppressionReason: string
{
    case HardBounce = 'hard_bounce';
    case Complaint = 'complaint';
    case Unsubscribed = 'unsubscribed';
}

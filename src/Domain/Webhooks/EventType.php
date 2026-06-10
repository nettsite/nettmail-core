<?php

namespace Nettsite\NettMail\Core\Domain\Webhooks;

enum EventType: string
{
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case HardBounced = 'hard_bounced';
    case SoftBounced = 'soft_bounced';
    case Complained = 'complained';
    case Unsubscribed = 'unsubscribed';
}

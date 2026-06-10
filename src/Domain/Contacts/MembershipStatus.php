<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

enum MembershipStatus: string
{
    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';
    case Pending = 'pending';
    case Bounced = 'bounced';
}

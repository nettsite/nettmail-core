<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Paused = 'paused';
}

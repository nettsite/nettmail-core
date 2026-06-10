<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns;

use RuntimeException;

final class InvalidCampaignTransitionException extends RuntimeException
{
    public function __construct(CampaignStatus $from, CampaignStatus $to)
    {
        parent::__construct("Cannot transition campaign from \"{$from->value}\" to \"{$to->value}\".");
    }
}

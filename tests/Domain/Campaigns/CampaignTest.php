<?php

use Nettsite\NettMail\Core\Domain\Campaigns\Campaign;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignStatus;
use Nettsite\NettMail\Core\Domain\Campaigns\InvalidCampaignTransitionException;

function makeCampaign(CampaignStatus $status = CampaignStatus::Draft): Campaign
{
    return new Campaign(
        id: null,
        name: 'Spring Sale',
        subject: 'Spring Sale!',
        templateId: 'template-1',
        listId: 'list-1',
        status: $status,
    );
}

it('moves from draft to scheduled', function () {
    $campaign = makeCampaign();

    $campaign->transitionTo(CampaignStatus::Scheduled);

    expect($campaign->status)->toBe(CampaignStatus::Scheduled);
});

it('moves from draft directly to sending', function () {
    $campaign = makeCampaign();

    $campaign->transitionTo(CampaignStatus::Sending);

    expect($campaign->status)->toBe(CampaignStatus::Sending);
});

it('moves from scheduled back to draft', function () {
    $campaign = makeCampaign(CampaignStatus::Scheduled);

    $campaign->transitionTo(CampaignStatus::Draft);

    expect($campaign->status)->toBe(CampaignStatus::Draft);
});

it('moves from sending to sent, failed, or paused', function (CampaignStatus $target) {
    $campaign = makeCampaign(CampaignStatus::Sending);

    $campaign->transitionTo($target);

    expect($campaign->status)->toBe($target);
})->with([CampaignStatus::Sent, CampaignStatus::Failed, CampaignStatus::Paused]);

it('resumes a paused campaign back to sending', function () {
    $campaign = makeCampaign(CampaignStatus::Paused);

    $campaign->transitionTo(CampaignStatus::Sending);

    expect($campaign->status)->toBe(CampaignStatus::Sending);
});

it('rejects invalid transitions', function (CampaignStatus $from, CampaignStatus $to) {
    $campaign = makeCampaign($from);

    $campaign->transitionTo($to);
})->with([
    [CampaignStatus::Draft, CampaignStatus::Sent],
    [CampaignStatus::Sent, CampaignStatus::Sending],
    [CampaignStatus::Failed, CampaignStatus::Sending],
    [CampaignStatus::Paused, CampaignStatus::Sent],
])->throws(InvalidCampaignTransitionException::class);

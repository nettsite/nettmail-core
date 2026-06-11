<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns;

use DateTimeImmutable;

final class Campaign
{
    public function __construct(
        public ?string $id,
        public string $name,
        public string $subject,
        public string $templateId,
        public string $listId,
        public ?string $segmentId = null,
        public ?string $senderId = null,
        public CampaignStatus $status = CampaignStatus::Draft,
        public ?DateTimeImmutable $scheduledAt = null,
    ) {
    }

    /**
     * @throws InvalidCampaignTransitionException
     */
    public function transitionTo(CampaignStatus $status): void
    {
        if (! $this->canTransitionTo($status)) {
            throw new InvalidCampaignTransitionException($this->status, $status);
        }

        $this->status = $status;
    }

    public function canTransitionTo(CampaignStatus $status): bool
    {
        return match ($this->status) {
            CampaignStatus::Draft => in_array($status, [CampaignStatus::Scheduled, CampaignStatus::Sending], true),
            CampaignStatus::Scheduled => in_array($status, [CampaignStatus::Sending, CampaignStatus::Draft], true),
            CampaignStatus::Sending => in_array($status, [CampaignStatus::Sent, CampaignStatus::Failed, CampaignStatus::Paused], true),
            CampaignStatus::Paused => $status === CampaignStatus::Sending,
            CampaignStatus::Sent, CampaignStatus::Failed => false,
        };
    }
}

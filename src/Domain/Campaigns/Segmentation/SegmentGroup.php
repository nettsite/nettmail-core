<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns\Segmentation;

final readonly class SegmentGroup
{
    /**
     * @param array<int, SegmentCondition|SegmentGroup> $conditions
     */
    public function __construct(
        public SegmentLogic $logic,
        public array $conditions,
    ) {
    }
}

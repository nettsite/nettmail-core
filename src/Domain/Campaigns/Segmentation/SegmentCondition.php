<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns\Segmentation;

final readonly class SegmentCondition
{
    public function __construct(
        public string $field,
        public SegmentOperator $operator,
        public mixed $value = null,
    ) {
    }
}

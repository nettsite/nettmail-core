<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns\Segmentation;

enum SegmentLogic: string
{
    case And = 'and';
    case Or = 'or';
}

<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns\Segmentation;

enum SegmentOperator: string
{
    case Is = 'is';
    case IsNot = 'is_not';
    case Contains = 'contains';
    case DoesNotContain = 'does_not_contain';
    case StartsWith = 'starts_with';
    case IsBlank = 'is_blank';
    case IsNotBlank = 'is_not_blank';
    case GreaterThan = 'greater_than';
    case LessThan = 'less_than';
    case Between = 'between';
    case Before = 'before';
    case After = 'after';
    case WithinLastDays = 'within_last_days';
}

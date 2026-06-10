<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns\Segmentation;

use DateTimeImmutable;

final class SegmentEvaluator
{
    /**
     * @param array<string, mixed> $fields
     */
    public function evaluate(SegmentGroup $group, array $fields): bool
    {
        foreach ($group->conditions as $condition) {
            $result = $condition instanceof SegmentGroup
                ? $this->evaluate($condition, $fields)
                : $this->evaluateCondition($condition, $fields);

            if ($group->logic === SegmentLogic::And && ! $result) {
                return false;
            }

            if ($group->logic === SegmentLogic::Or && $result) {
                return true;
            }
        }

        return $group->logic === SegmentLogic::And;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function evaluateCondition(SegmentCondition $condition, array $fields): bool
    {
        $value = $fields[$condition->field] ?? null;

        return match ($condition->operator) {
            SegmentOperator::Is => $this->normalize($value) === $this->normalize($condition->value),
            SegmentOperator::IsNot => $this->normalize($value) !== $this->normalize($condition->value),
            SegmentOperator::Contains => str_contains((string) $value, (string) $condition->value),
            SegmentOperator::DoesNotContain => ! str_contains((string) $value, (string) $condition->value),
            SegmentOperator::StartsWith => str_starts_with((string) $value, (string) $condition->value),
            SegmentOperator::IsBlank => $value === null || $value === '',
            SegmentOperator::IsNotBlank => $value !== null && $value !== '',
            SegmentOperator::GreaterThan => (float) $value > (float) $condition->value,
            SegmentOperator::LessThan => (float) $value < (float) $condition->value,
            SegmentOperator::Between => (float) $value >= (float) $condition->value[0] && (float) $value <= (float) $condition->value[1],
            SegmentOperator::Before => $this->toDate($value) < $this->toDate($condition->value),
            SegmentOperator::After => $this->toDate($value) > $this->toDate($condition->value),
            SegmentOperator::WithinLastDays => $this->toDate($value) >= new DateTimeImmutable("-{$condition->value} days"),
        };
    }

    private function normalize(mixed $value): mixed
    {
        return is_string($value) ? strtolower($value) : $value;
    }

    private function toDate(mixed $value): DateTimeImmutable
    {
        return $value instanceof DateTimeImmutable ? $value : new DateTimeImmutable((string) $value);
    }
}

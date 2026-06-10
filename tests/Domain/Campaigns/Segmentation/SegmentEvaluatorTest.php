<?php

use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentCondition;
use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentEvaluator;
use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentGroup;
use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentLogic;
use Nettsite\NettMail\Core\Domain\Campaigns\Segmentation\SegmentOperator;

it('matches a simple "is" condition', function () {
    $group = new SegmentGroup(SegmentLogic::And, [
        new SegmentCondition('first_name', SegmentOperator::Is, 'jane'),
    ]);

    $evaluator = new SegmentEvaluator();

    expect($evaluator->evaluate($group, ['first_name' => 'Jane']))->toBeTrue()
        ->and($evaluator->evaluate($group, ['first_name' => 'John']))->toBeFalse();
});

it('combines conditions with AND', function () {
    $group = new SegmentGroup(SegmentLogic::And, [
        new SegmentCondition('company', SegmentOperator::IsNotBlank),
        new SegmentCondition('orders', SegmentOperator::GreaterThan, 1),
    ]);

    $evaluator = new SegmentEvaluator();

    expect($evaluator->evaluate($group, ['company' => 'Acme', 'orders' => 5]))->toBeTrue()
        ->and($evaluator->evaluate($group, ['company' => '', 'orders' => 5]))->toBeFalse()
        ->and($evaluator->evaluate($group, ['company' => 'Acme', 'orders' => 0]))->toBeFalse();
});

it('combines conditions with OR', function () {
    $group = new SegmentGroup(SegmentLogic::Or, [
        new SegmentCondition('company', SegmentOperator::Is, 'acme'),
        new SegmentCondition('orders', SegmentOperator::GreaterThan, 10),
    ]);

    $evaluator = new SegmentEvaluator();

    expect($evaluator->evaluate($group, ['company' => 'Acme', 'orders' => 0]))->toBeTrue()
        ->and($evaluator->evaluate($group, ['company' => 'Other', 'orders' => 20]))->toBeTrue()
        ->and($evaluator->evaluate($group, ['company' => 'Other', 'orders' => 0]))->toBeFalse();
});

it('supports a nested group one level deep', function () {
    $group = new SegmentGroup(SegmentLogic::And, [
        new SegmentCondition('company', SegmentOperator::Is, 'acme'),
        new SegmentGroup(SegmentLogic::Or, [
            new SegmentCondition('orders', SegmentOperator::GreaterThan, 10),
            new SegmentCondition('first_name', SegmentOperator::StartsWith, 'Ja'),
        ]),
    ]);

    $evaluator = new SegmentEvaluator();

    expect($evaluator->evaluate($group, ['company' => 'Acme', 'orders' => 0, 'first_name' => 'Jane']))->toBeTrue()
        ->and($evaluator->evaluate($group, ['company' => 'Acme', 'orders' => 0, 'first_name' => 'John']))->toBeFalse();
});

it('evaluates the between operator', function () {
    $group = new SegmentGroup(SegmentLogic::And, [
        new SegmentCondition('orders', SegmentOperator::Between, [1, 10]),
    ]);

    $evaluator = new SegmentEvaluator();

    expect($evaluator->evaluate($group, ['orders' => 5]))->toBeTrue()
        ->and($evaluator->evaluate($group, ['orders' => 0]))->toBeFalse()
        ->and($evaluator->evaluate($group, ['orders' => 11]))->toBeFalse();
});

it('evaluates date operators', function () {
    $before = new SegmentGroup(SegmentLogic::And, [
        new SegmentCondition('subscribed_at', SegmentOperator::Before, '2024-06-01'),
    ]);
    $after = new SegmentGroup(SegmentLogic::And, [
        new SegmentCondition('subscribed_at', SegmentOperator::After, '2024-06-01'),
    ]);
    $withinLast = new SegmentGroup(SegmentLogic::And, [
        new SegmentCondition('subscribed_at', SegmentOperator::WithinLastDays, 7),
    ]);

    $evaluator = new SegmentEvaluator();

    expect($evaluator->evaluate($before, ['subscribed_at' => '2024-01-01']))->toBeTrue()
        ->and($evaluator->evaluate($after, ['subscribed_at' => '2024-01-01']))->toBeFalse()
        ->and($evaluator->evaluate($withinLast, ['subscribed_at' => (new DateTimeImmutable())->format('Y-m-d')]))->toBeTrue()
        ->and($evaluator->evaluate($withinLast, ['subscribed_at' => '2020-01-01']))->toBeFalse();
});

it('treats a missing field as blank', function () {
    $group = new SegmentGroup(SegmentLogic::And, [
        new SegmentCondition('missing_field', SegmentOperator::IsBlank),
    ]);

    expect((new SegmentEvaluator())->evaluate($group, []))->toBeTrue();
});

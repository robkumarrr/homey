<?php

use App\Enums\RepeatFrequency;
use App\Support\Recurrence;
use Carbon\CarbonImmutable;

function schedule(string $startsOn, RepeatFrequency $frequency, int $interval = 1, ?string $endsOn = null): Recurrence
{
    return new Recurrence(
        CarbonImmutable::parse($startsOn),
        $frequency,
        $interval,
        $endsOn === null ? null : CarbonImmutable::parse($endsOn),
    );
}

/**
 * @return list<string>
 */
function datesBetween(Recurrence $recurrence, string $from, string $to): array
{
    return array_map(
        fn (CarbonImmutable $date) => $date->toDateString(),
        $recurrence->between(CarbonImmutable::parse($from), CarbonImmutable::parse($to)),
    );
}

test('a schedule lists its dates within a window', function (Recurrence $recurrence, string $from, string $to, array $expected) {
    expect(datesBetween($recurrence, $from, $to))->toBe($expected);
})->with([
    'monthly' => [fn () => schedule('2026-01-15', RepeatFrequency::Monthly), '2026-03-01', '2026-05-31', ['2026-03-15', '2026-04-15', '2026-05-15']],
    'every 3 months' => [fn () => schedule('2026-01-10', RepeatFrequency::Monthly, 3), '2026-01-01', '2026-12-31', ['2026-01-10', '2026-04-10', '2026-07-10', '2026-10-10']],
    'every 6 months' => [fn () => schedule('2026-02-01', RepeatFrequency::Monthly, 6), '2026-01-01', '2027-12-31', ['2026-02-01', '2026-08-01', '2027-02-01', '2027-08-01']],
    'yearly' => [fn () => schedule('2024-03-03', RepeatFrequency::Yearly), '2025-01-01', '2027-12-31', ['2025-03-03', '2026-03-03', '2027-03-03']],
    'once' => [fn () => schedule('2026-11-20', RepeatFrequency::Once), '2026-11-01', '2027-12-31', ['2026-11-20']],
    'weekly' => [fn () => schedule('2026-11-06', RepeatFrequency::Weekly), '2026-11-01', '2026-11-30', ['2026-11-06', '2026-11-13', '2026-11-20', '2026-11-27']],
    'every 2 weeks' => [fn () => schedule('2026-10-30', RepeatFrequency::Weekly, 2), '2026-11-01', '2026-11-30', ['2026-11-13', '2026-11-27']],
    'both ends of the window are included' => [fn () => schedule('2026-01-01', RepeatFrequency::Monthly), '2026-02-01', '2026-03-01', ['2026-02-01', '2026-03-01']],
    'nothing before the first date' => [fn () => schedule('2026-12-05', RepeatFrequency::Monthly), '2026-11-01', '2026-11-30', []],
    'far from the first date' => [fn () => schedule('2000-01-31', RepeatFrequency::Monthly), '2026-02-01', '2026-03-31', ['2026-02-28', '2026-03-31']],
]);

test('day 31 clamps to the end of shorter months and returns afterwards', function () {
    expect(datesBetween(schedule('2026-01-31', RepeatFrequency::Monthly), '2026-01-01', '2026-05-31'))
        ->toBe(['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31']);
});

test('february the 29th falls on the 28th in other years', function () {
    expect(datesBetween(schedule('2024-02-29', RepeatFrequency::Yearly), '2024-01-01', '2028-12-31'))
        ->toBe(['2024-02-29', '2025-02-28', '2026-02-28', '2027-02-28', '2028-02-29']);

    expect(datesBetween(schedule('2027-01-31', RepeatFrequency::Monthly), '2028-02-01', '2028-02-29'))
        ->toBe(['2028-02-29']);
});

test('a schedule stops after its last date', function () {
    $loan = schedule('2026-01-15', RepeatFrequency::Monthly, endsOn: '2026-03-15');

    expect(datesBetween($loan, '2026-01-01', '2026-12-31'))->toBe(['2026-01-15', '2026-02-15', '2026-03-15']);
});

test('the next date is found on or after a day', function (Recurrence $recurrence, string $on, ?string $expected) {
    expect($recurrence->nextOnOrAfter(CarbonImmutable::parse($on))?->toDateString())->toBe($expected);
})->with([
    'later this month' => [fn () => schedule('2026-01-15', RepeatFrequency::Monthly), '2026-11-08', '2026-11-15'],
    'the same day' => [fn () => schedule('2026-01-15', RepeatFrequency::Monthly), '2026-11-15', '2026-11-15'],
    'next month' => [fn () => schedule('2026-01-15', RepeatFrequency::Monthly), '2026-11-16', '2026-12-15'],
    'before it starts' => [fn () => schedule('2027-01-01', RepeatFrequency::Yearly), '2026-11-08', '2027-01-01'],
    'a one-off already past' => [fn () => schedule('2026-10-01', RepeatFrequency::Once), '2026-11-08', null],
    'after its last date' => [fn () => schedule('2026-01-15', RepeatFrequency::Monthly, endsOn: '2026-10-15'), '2026-11-08', null],
]);

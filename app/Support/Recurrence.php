<?php

namespace App\Support;

use App\Enums\RepeatFrequency;
use Carbon\CarbonImmutable;

/**
 * A schedule's dates, computed from its first date (ADR 0005). Monthly and
 * yearly dates keep the first date's day, clamped to the end of shorter
 * months: a schedule starting Jan 31 falls on Feb 28 (29 in a leap year),
 * then Mar 31 again.
 */
final readonly class Recurrence
{
    public function __construct(
        public CarbonImmutable $startsOn,
        public RepeatFrequency $frequency,
        public int $interval = 1,
        public ?CarbonImmutable $endsOn = null,
    ) {}

    /**
     * Every date from $from to $to, both inclusive, in order.
     *
     * @return list<CarbonImmutable>
     */
    public function between(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $last = $this->endsOn !== null && $this->endsOn->lessThan($to) ? $this->endsOn : $to;
        $dates = [];

        for ($n = $this->firstIndexNear($from); ($date = $this->nth($n)) !== null && $date->lessThanOrEqualTo($last); $n++) {
            if ($date->greaterThanOrEqualTo($from)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * The first date on or after $date, or null once the schedule has ended.
     */
    public function nextOnOrAfter(CarbonImmutable $date): ?CarbonImmutable
    {
        for ($n = $this->firstIndexNear($date); ($next = $this->nth($n)) !== null; $n++) {
            if ($this->endsOn !== null && $next->greaterThan($this->endsOn)) {
                return null;
            }

            if ($next->greaterThanOrEqualTo($date)) {
                return $next;
            }
        }

        return null;
    }

    /**
     * The schedule's nth date, counting the first date as 0, or null when a
     * one-off has no such date.
     */
    private function nth(int $n): ?CarbonImmutable
    {
        return match ($this->frequency) {
            RepeatFrequency::Once => $n === 0 ? $this->startsOn : null,
            RepeatFrequency::Weekly => $this->startsOn->addWeeks($n * $this->interval),
            RepeatFrequency::Monthly => $this->monthsLater($n * $this->interval),
            RepeatFrequency::Yearly => $this->monthsLater($n * $this->interval * 12),
        };
    }

    private function monthsLater(int $months): CarbonImmutable
    {
        $month = $this->startsOn->startOfMonth()->addMonths($months);

        return $month->setDay(min($this->startsOn->day, $month->daysInMonth));
    }

    /**
     * An index at or just before the first date on or after $date, so long
     * schedules don't have to be walked from the start.
     */
    private function firstIndexNear(CarbonImmutable $date): int
    {
        if ($date->lessThanOrEqualTo($this->startsOn)) {
            return 0;
        }

        $steps = match ($this->frequency) {
            RepeatFrequency::Once => 0,
            RepeatFrequency::Weekly => intdiv((int) $this->startsOn->diffInDays($date), 7 * $this->interval),
            RepeatFrequency::Monthly => intdiv((int) $this->startsOn->diffInMonths($date), $this->interval),
            RepeatFrequency::Yearly => intdiv((int) $this->startsOn->diffInYears($date), $this->interval),
        };

        return max(0, $steps - 1);
    }
}

<?php

namespace App\Models;

use App\Concerns\BelongsToFamily;
use App\Enums\RepeatFrequency;
use App\Support\Recurrence;
use Carbon\CarbonImmutable;
use Database\Factories\CommitmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A promise to pay on a schedule, shown as a Bill in the app. It records what
 * is owed, never what was paid; its dates are computed, never stored
 * (ADR 0002, ADR 0005).
 *
 * @property int $id
 * @property int $family_id
 * @property int $category_id
 * @property int|null $payment_method_id
 * @property string $name
 * @property bool $amount_varies
 * @property RepeatFrequency $frequency
 * @property int $interval
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable|null $ends_on
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, CommitmentAmount> $amounts
 * @property-read Collection<int, CommitmentException> $exceptions
 */
#[Fillable(['name', 'category_id', 'payment_method_id', 'amount_varies', 'frequency', 'interval', 'starts_on', 'ends_on'])]
class Commitment extends Model
{
    use BelongsToFamily;

    /** @use HasFactory<CommitmentFactory> */
    use HasFactory;

    /**
     * The bill's amount changes, each from a due date onward (ADR 0012).
     *
     * @return HasMany<CommitmentAmount, $this>
     */
    public function amounts(): HasMany
    {
        return $this->hasMany(CommitmentAmount::class)->orderBy('effective_on');
    }

    /**
     * Changes to single due dates, keyed on their original date (ADR 0001).
     *
     * @return HasMany<CommitmentException, $this>
     */
    public function exceptions(): HasMany
    {
        return $this->hasMany(CommitmentException::class);
    }

    /**
     * What's owed on one due date, or null when it still needs entering: that
     * date's own amount if it has one, then (unless the amount changes each
     * time) the latest amount change on or before it.
     */
    public function amountDueOn(CarbonImmutable $dueOn): ?int
    {
        $exception = $this->exceptions->first(fn (CommitmentException $exception) => $exception->original_due_on->equalTo($dueOn));

        if ($exception?->amount_cents !== null || $this->amount_varies) {
            return $exception?->amount_cents;
        }

        return $this->amountChangeOn($dueOn);
    }

    /**
     * Before the bill starts changing each time, give each due date up to the
     * end of this month, or up to its next bill if that's later, the amount it
     * shows now as its own, and so too each later date an amount change was
     * entered on. Switching never blanks an amount you can see, the bill
     * about to be paid, or one you typed in; other later dates ask for theirs.
     */
    public function keepShownAmounts(): void
    {
        $this->load(['amounts', 'exceptions']);

        $today = CarbonImmutable::today();
        $until = $today->endOfMonth()->max($this->recurrence()->nextOnOrAfter($today) ?? $today);
        $enteredLater = $this->amounts
            ->map(fn (CommitmentAmount $amount) => $amount->effective_on)
            ->filter(fn (CarbonImmutable $effectiveOn) => $effectiveOn->greaterThan($until) && $this->isDueOn($effectiveOn));

        foreach ([...$this->recurrence()->between($this->starts_on, $until), ...$enteredLater] as $dueOn) {
            $amountCents = $this->amountDueOn($dueOn);
            $hasOwnAmount = $this->exceptions->contains(fn (CommitmentException $exception) => $exception->original_due_on->equalTo($dueOn));

            if ($amountCents !== null && ! $hasOwnAmount) {
                $this->exceptions()->create(['original_due_on' => $dueOn, 'amount_cents' => $amountCents]);
            }
        }
    }

    /**
     * Once the bill stops changing each time, carry its latest entered amount
     * forward from that date if it has no amount changes yet, and drop the
     * dates' own amounts that now match anyway, so a later "this and later
     * months" change isn't blocked by them.
     */
    public function carryAmountsForward(): void
    {
        $this->load(['amounts', 'exceptions']);
        $latest = $this->exceptions
            ->whereNotNull('amount_cents')
            ->sortBy('original_due_on')
            ->last();

        if ($this->amounts->isEmpty() && $latest !== null) {
            $this->amounts()->create(['effective_on' => $latest->original_due_on, 'amount_cents' => $latest->amount_cents]);
            $this->load('amounts');
        }

        $this->exceptions
            ->filter(fn (CommitmentException $exception) => $exception->amount_cents === $this->amountChangeOn($exception->original_due_on))
            ->each(fn (CommitmentException $exception) => $exception->delete());
    }

    /**
     * Keep the bill's first amount applying from its first due date, so
     * moving the start earlier doesn't leave that amount behind later changes.
     */
    public function startFirstAmountAtStart(): void
    {
        $first = $this->amounts()->first();

        if ($first !== null && $first->effective_on->greaterThan($this->starts_on)) {
            $first->update(['effective_on' => $this->starts_on]);
        }
    }

    /**
     * The latest amount change on or before a date, or the first one for
     * dates before any change.
     */
    private function amountChangeOn(CarbonImmutable $dueOn): ?int
    {
        return ($this->amounts->last(fn (CommitmentAmount $amount) => $amount->effective_on->lessThanOrEqualTo($dueOn))
            ?? $this->amounts->first())?->amount_cents;
    }

    /**
     * Whether the schedule falls on this date, whether or not it has passed.
     */
    public function isDueOn(CarbonImmutable $date): bool
    {
        return $this->recurrence()->nextOnOrAfter($date)?->equalTo($date) ?? false;
    }

    /**
     * The bill's due dates. They stop after its final payment date, and an
     * archived bill stops the day it was archived, keeping earlier months.
     */
    public function recurrence(): Recurrence
    {
        $lastDates = array_filter([$this->ends_on, $this->archived_at?->startOfDay()->subDay()]);

        return new Recurrence(
            $this->starts_on,
            $this->frequency,
            $this->interval,
            $lastDates === [] ? null : min($lastDates),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'amount_varies' => 'boolean',
            'frequency' => RepeatFrequency::class,
            'interval' => 'integer',
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'archived_at' => 'datetime',
        ];
    }
}

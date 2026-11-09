<?php

namespace App\Http\Controllers\Finances;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finances\CommitmentStoreRequest;
use App\Http\Requests\Finances\CommitmentUpdateRequest;
use App\Models\Commitment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Bills in the app. They're archived, never deleted (ADR 0007), and editing,
 * archiving and unarchiving confirm with an undo toast.
 */
class CommitmentController extends Controller
{
    /**
     * Add a bill. Its amount repeats from the first due date, or belongs to
     * just the first bill when the amount changes each time.
     */
    public function store(CommitmentStoreRequest $request): RedirectResponse
    {
        $commitment = DB::transaction(function () use ($request) {
            $commitment = Commitment::create($request->commitmentAttributes());
            $amountCents = $request->amountCents();

            if ($amountCents !== null && $commitment->amount_varies) {
                $commitment->exceptions()->create(['original_due_on' => $commitment->starts_on, 'amount_cents' => $amountCents]);
            } elseif ($amountCents !== null) {
                $commitment->amounts()->create(['effective_on' => $commitment->starts_on, 'amount_cents' => $amountCents]);
            }

            return $commitment;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Added ":name".', ['name' => $commitment->name])]);

        return back();
    }

    /**
     * Change a bill's details or schedule. Amounts change per due date
     * instead (CommitmentAmountController), so no month changes by surprise.
     * Switching whether the amount changes each time keeps every amount
     * already shown (ADR 0012).
     */
    public function update(CommitmentUpdateRequest $request, Commitment $commitment): RedirectResponse
    {
        $previous = [
            'name' => $commitment->name,
            'amount_varies' => $commitment->amount_varies ? 1 : 0,
            'category_id' => $commitment->category_id,
            'payment_method_id' => $commitment->payment_method_id,
            'frequency' => $commitment->frequency->value,
            'interval' => $commitment->interval,
            'starts_on' => $commitment->starts_on->toDateString(),
            'ends_on' => $commitment->ends_on?->toDateString(),
        ];

        DB::transaction(function () use ($request, $commitment) {
            $wasVarying = $commitment->amount_varies;
            $willVary = $request->boolean('amount_varies');

            if (! $wasVarying && $willVary) {
                $commitment->keepShownAmounts();
            }

            $commitment->update($request->commitmentAttributes());
            $commitment->startFirstAmountAtStart();

            if ($wasVarying && ! $willVary) {
                $commitment->carryAmountsForward();
            }
        });

        $this->toastWithUndo(__('Saved ":name".', ['name' => $commitment->name]), [
            'url' => route('finances.commitments.update', $commitment),
            'method' => 'patch',
            'data' => $previous,
        ]);

        return back();
    }

    /**
     * Archive a bill. Its dates stop from today, and earlier months keep it.
     */
    public function archive(Commitment $commitment): RedirectResponse
    {
        $commitment->forceFill(['archived_at' => now()])->save();

        $this->toastWithUndo(__('Archived ":name".', ['name' => $commitment->name]), [
            'url' => route('finances.commitments.unarchive', $commitment),
            'method' => 'delete',
        ]);

        return back();
    }

    /**
     * Restart an archived bill's schedule.
     */
    public function unarchive(Commitment $commitment): RedirectResponse
    {
        $commitment->forceFill(['archived_at' => null])->save();

        $this->toastWithUndo(__('Unarchived ":name".', ['name' => $commitment->name]), [
            'url' => route('finances.commitments.archive', $commitment),
            'method' => 'post',
        ]);

        return back();
    }
}

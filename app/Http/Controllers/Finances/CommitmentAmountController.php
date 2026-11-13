<?php

namespace App\Http\Controllers\Finances;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finances\CommitmentAmountUpdateRequest;
use App\Models\Commitment;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Sets what a bill costs on one of its due dates: for just that date (a
 * commitment exception, ADR 0001) or from that date on (an amount change,
 * ADR 0012). Earlier dates never change.
 */
class CommitmentAmountController extends Controller
{
    /**
     * Set or clear the amounts given for a due date. The undo sends back
     * whatever each one was before.
     */
    public function update(CommitmentAmountUpdateRequest $request, Commitment $commitment): RedirectResponse
    {
        $dueOn = CarbonImmutable::parse($request->string('due_on')->toString());
        $undo = ['due_on' => $dueOn->toDateString()];

        DB::transaction(function () use ($request, $commitment, $dueOn, &$undo) {
            if ($request->has('month_amount')) {
                $exception = $commitment->exceptions()->where('original_due_on', $dueOn->toDateString())->first();
                $undo['month_amount'] = $this->toInput($exception?->amount_cents);
                $amountCents = $this->cents($request->input('month_amount'));

                $amountCents === null
                    ? $exception?->delete()
                    : $commitment->exceptions()->updateOrCreate(['original_due_on' => $dueOn->toDateString()], ['amount_cents' => $amountCents]);
            }

            if ($request->has('onward_amount')) {
                $change = $commitment->amounts()->where('effective_on', $dueOn->toDateString())->first();
                $undo['onward_amount'] = $this->toInput($change?->amount_cents);
                $amountCents = $this->cents($request->input('onward_amount'));

                $amountCents === null
                    ? $change?->delete()
                    : $commitment->amounts()->updateOrCreate(['effective_on' => $dueOn->toDateString()], ['amount_cents' => $amountCents]);
            }
        });

        $this->toastWithUndo(__('Updated ":name" for :date.', ['name' => $commitment->name, 'date' => $dueOn->format('M j')]), [
            'url' => route('finances.commitments.amounts.update', $commitment),
            'method' => 'put',
            'data' => $undo,
        ]);

        return back();
    }

    private function cents(mixed $amount): ?int
    {
        return is_string($amount) ? Money::parseCents($amount) : null;
    }

    private function toInput(?int $cents): ?string
    {
        return $cents === null ? null : number_format($cents / 100, 2, '.', '');
    }
}

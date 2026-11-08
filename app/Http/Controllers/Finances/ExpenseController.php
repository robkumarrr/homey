<?php

namespace App\Http\Controllers\Finances;

use App\Actions\DeleteExpense;
use App\Actions\RecordExpense;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finances\ExpenseStoreRequest;
use App\Http\Requests\Finances\ExpenseUpdateRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Every change confirms with a toast whose Undo replays the opposite request:
 * adding is undone by deleting (into Recently deleted), editing by saving the
 * previous values, and deleting by restoring.
 */
class ExpenseController extends Controller
{
    /**
     * Record a new expense.
     */
    public function store(ExpenseStoreRequest $request, RecordExpense $recordExpense): RedirectResponse
    {
        $expense = $recordExpense->handle(
            recordedBy: $request->user(),
            name: $request->string('name')->toString(),
            amountCents: $request->amountCents(),
            categoryId: $request->integer('category_id'),
            paidOn: $request->string('paid_on')->toString(),
        );

        $this->toastWithUndo(__('Added ":name".', ['name' => $expense->name]), [
            'url' => route('finances.expenses.destroy', $expense),
            'method' => 'delete',
        ]);

        return back();
    }

    /**
     * Update an expense's details.
     */
    public function update(ExpenseUpdateRequest $request, Expense $expense): RedirectResponse
    {
        $previous = [
            'name' => $expense->name,
            'amount' => number_format($expense->amount_cents / 100, 2, '.', ''),
            'category_id' => $expense->category_id,
            'paid_on' => $expense->paid_on->toDateString(),
        ];

        $expense->update([
            'name' => $request->string('name')->toString(),
            'amount_cents' => $request->amountCents(),
            'category_id' => $request->integer('category_id'),
            'paid_on' => $request->string('paid_on')->toString(),
        ]);

        $this->toastWithUndo(__('Saved ":name".', ['name' => $expense->name]), [
            'url' => route('finances.expenses.update', $expense),
            'method' => 'patch',
            'data' => $previous,
        ]);

        return back();
    }

    /**
     * Move an expense to Recently deleted.
     */
    public function destroy(Request $request, Expense $expense, DeleteExpense $deleteExpense): RedirectResponse
    {
        $deleted = $deleteExpense->handle($expense, $request->user());

        $this->toastWithUndo(__('Deleted ":name".', ['name' => $expense->name]), [
            'url' => route('finances.deleted-expenses.restore', $deleted),
            'method' => 'post',
        ]);

        return back();
    }

    /**
     * @param  array{url: string, method: string, data?: array<string, mixed>}  $undo
     */
    private function toastWithUndo(string $message, array $undo): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message, 'undo' => $undo]);
    }
}

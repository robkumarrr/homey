<?php

namespace App\Http\Controllers\Finances;

use App\Actions\RecordExpense;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finances\ExpenseStoreRequest;
use App\Http\Requests\Finances\ExpenseUpdateRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    /**
     * Record a new expense.
     */
    public function store(ExpenseStoreRequest $request, RecordExpense $recordExpense): RedirectResponse
    {
        $recordExpense->handle(
            recordedBy: $request->user(),
            amountCents: $request->amountCents(),
            categoryId: $request->integer('category_id'),
            paidOn: $request->string('paid_on')->toString(),
            note: $request->validated('note'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense added.')]);

        return back();
    }

    /**
     * Update an expense's details.
     */
    public function update(ExpenseUpdateRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update([
            'amount_cents' => $request->amountCents(),
            'category_id' => $request->integer('category_id'),
            'paid_on' => $request->string('paid_on')->toString(),
            'note' => $request->validated('note'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense updated.')]);

        return back();
    }
}

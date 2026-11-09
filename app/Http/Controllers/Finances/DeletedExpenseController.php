<?php

namespace App\Http\Controllers\Finances;

use App\Actions\RestoreExpense;
use App\Http\Controllers\Controller;
use App\Models\DeletedExpense;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DeletedExpenseController extends Controller
{
    /**
     * Restore an expense from Recently deleted.
     */
    public function restore(DeletedExpense $deletedExpense, RestoreExpense $restoreExpense): RedirectResponse
    {
        $expense = $restoreExpense->handle($deletedExpense);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Restored ":name".', ['name' => $expense->name])]);

        return back();
    }
}

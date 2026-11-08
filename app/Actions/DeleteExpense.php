<?php

namespace App\Actions;

use App\Models\DeletedExpense;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteExpense
{
    /**
     * Move an expense to Recently deleted: snapshot the row, then delete it, so
     * `expenses` never holds hidden rows (ADR 0011).
     */
    public function handle(Expense $expense, User $deletedBy): DeletedExpense
    {
        return DB::transaction(function () use ($expense, $deletedBy): DeletedExpense {
            $deleted = new DeletedExpense([]);
            $deleted->family_id = $expense->family_id;
            $deleted->expense_id = $expense->id;
            $deleted->payload = $expense->getAttributes();
            $deleted->deleted_by = $deletedBy->id;
            $deleted->deleted_at = now();
            $deleted->save();

            $expense->delete();

            return $deleted;
        });
    }
}

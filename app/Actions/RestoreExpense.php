<?php

namespace App\Actions;

use App\Models\DeletedExpense;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class RestoreExpense
{
    /**
     * Put a deleted expense back exactly as it was, under its original id, and
     * take it out of Recently deleted.
     */
    public function handle(DeletedExpense $deleted): Expense
    {
        return DB::transaction(function () use ($deleted): Expense {
            $expense = (new Expense)->forceFill($deleted->payload);
            $expense->timestamps = false;
            $expense->save();

            $deleted->delete();

            return $expense;
        });
    }
}

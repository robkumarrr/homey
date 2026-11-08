<?php

namespace App\Actions;

use App\Models\Expense;
use App\Models\User;

class RecordExpense
{
    /**
     * Record money that left the household. Every path that records spending
     * goes through here, so later features (bills, autopay, projects) share it.
     */
    public function handle(
        User $recordedBy,
        int $amountCents,
        int $categoryId,
        string $paidOn,
        ?string $note = null,
    ): Expense {
        $expense = new Expense([
            'amount_cents' => $amountCents,
            'category_id' => $categoryId,
            'paid_on' => $paidOn,
            'note' => $note,
        ]);
        $expense->created_by = $recordedBy->id;
        $expense->save();

        return $expense;
    }
}

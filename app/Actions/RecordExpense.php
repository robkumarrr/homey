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
        string $name,
        int $amountCents,
        int $categoryId,
        string $paidOn,
        ?int $paymentMethodId = null,
    ): Expense {
        $expense = new Expense([
            'name' => $name,
            'amount_cents' => $amountCents,
            'category_id' => $categoryId,
            'paid_on' => $paidOn,
            'payment_method_id' => $paymentMethodId,
        ]);
        $expense->created_by = $recordedBy->id;
        $expense->save();

        return $expense;
    }
}

<?php

namespace App\Concerns;

use App\Models\Expense;
use App\Rules\MoneyAmount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Exists;

trait ExpenseValidationRules
{
    use PicksFamilyRecords;

    /**
     * Get the validation rules used to validate an expense.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string|Exists>>
     */
    protected function expenseRules(?Expense $expense = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'string', new MoneyAmount],
            'category_id' => ['required', 'integer', $this->activeCategory($expense?->category_id)],
            'payment_method_id' => ['nullable', 'integer', $this->activePaymentMethod($expense?->payment_method_id)],
            'paid_on' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'payment_method_id' => 'payment method',
            'paid_on' => 'date',
        ];
    }
}

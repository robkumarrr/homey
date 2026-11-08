<?php

namespace App\Concerns;

use App\Models\Expense;
use App\Rules\MoneyAmount;
use App\Support\Tenancy\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait ExpenseValidationRules
{
    /**
     * Get the validation rules used to validate an expense. The category must
     * belong to the current family (ADR 0006) and be active, unless an edited
     * expense keeps the archived category it already had.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string|Exists>>
     */
    protected function expenseRules(?Expense $expense = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'string', new MoneyAmount],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('family_id', app(Tenant::class)->familyId())
                    ->where(fn (Builder $query) => $query
                        ->whereNull('archived_at')
                        ->when($expense, fn (Builder $query, Expense $expense) => $query->orWhere('id', $expense->category_id))),
            ],
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
            'paid_on' => 'date',
        ];
    }
}

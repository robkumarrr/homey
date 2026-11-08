<?php

namespace App\Http\Requests\Finances;

use App\Concerns\ExpenseValidationRules;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseStoreRequest extends FormRequest
{
    use ExpenseValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->expenseRules();
    }

    /**
     * The validated amount in integer cents.
     */
    public function amountCents(): int
    {
        return (int) Money::parseCents($this->string('amount')->toString());
    }
}

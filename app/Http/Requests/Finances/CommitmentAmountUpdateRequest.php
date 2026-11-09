<?php

namespace App\Http\Requests\Finances;

use App\Models\Commitment;
use App\Rules\MoneyAmount;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommitmentAmountUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request. Each amount given
     * is set for the due date, and an empty one clears it: `month_amount` for
     * just that date, `onward_amount` from that date on (fixed bills only).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $commitment = $this->commitment();

        return [
            'due_on' => [
                'required',
                'date_format:Y-m-d',
                function (string $attribute, string $value, Closure $fail) use ($commitment) {
                    if (! $commitment->isDueOn(CarbonImmutable::parse($value))) {
                        $fail('That isn\'t one of this bill\'s due dates.');
                    }
                },
            ],
            'month_amount' => ['sometimes', 'nullable', 'string', new MoneyAmount],
            'onward_amount' => [Rule::prohibitedIf($commitment->amount_varies), 'sometimes', 'nullable', 'string', new MoneyAmount],
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
            'month_amount' => 'amount',
            'onward_amount' => 'amount',
        ];
    }

    public function commitment(): Commitment
    {
        /** @var Commitment $commitment */
        $commitment = $this->route('commitment');

        return $commitment;
    }
}

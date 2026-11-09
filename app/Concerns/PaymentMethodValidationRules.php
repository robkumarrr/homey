<?php

namespace App\Concerns;

use App\Enums\PaymentMethodKind;
use App\Models\PaymentMethod;
use App\Support\Tenancy\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;

trait PaymentMethodValidationRules
{
    /**
     * Get the validation rules used to validate a payment method. Names are
     * unique within the family, archived ones included, and only the last four
     * digits of a number are ever kept.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string|Unique|Enum>>
     */
    protected function paymentMethodRules(?PaymentMethod $paymentMethod = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_methods', 'name')
                    ->where('family_id', app(Tenant::class)->familyId())
                    ->ignore($paymentMethod),
            ],
            'kind' => ['required', Rule::enum(PaymentMethodKind::class)],
            'last_four' => ['nullable', 'digits:4'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'You already have a payment method called ":input". If it isn\'t listed, it\'s under Archived.',
            'last_four.digits' => 'Enter just the last 4 digits.',
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
            'last_four' => 'last 4 digits',
        ];
    }
}

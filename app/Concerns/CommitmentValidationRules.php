<?php

namespace App\Concerns;

use App\Enums\RepeatFrequency;
use App\Models\Commitment;
use App\Rules\MoneyAmount;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\RequiredIf;

trait CommitmentValidationRules
{
    use PicksFamilyRecords;

    /**
     * Get the validation rules used to validate a bill. The amount is only
     * given when adding one: it repeats from the first due date, or is just
     * the first bill's when the amount changes each time (and may be left
     * out). Later amount changes are made per due date. A one-off bill has no
     * final payment date, since its first date is its only one.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string|Exists|Enum|RequiredIf>>
     */
    protected function commitmentRules(?Commitment $commitment = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => $commitment === null
                ? [Rule::requiredIf(! $this->boolean('amount_varies')), 'nullable', 'string', new MoneyAmount]
                : ['exclude'],
            'amount_varies' => ['boolean'],
            'category_id' => ['required', 'integer', $this->activeCategory($commitment?->category_id)],
            'payment_method_id' => ['nullable', 'integer', $this->activePaymentMethod($commitment?->payment_method_id)],
            'frequency' => ['required', Rule::enum(RepeatFrequency::class)],
            'interval' => ['required', 'integer', 'between:1,12'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['exclude_if:frequency,once', 'nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * The bill's validated details, without its amount.
     *
     * @return array<string, mixed>
     */
    public function commitmentAttributes(): array
    {
        $validated = $this->safe()->except('amount');

        return [
            ...$validated,
            'amount_varies' => $this->boolean('amount_varies'),
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'ends_on' => $validated['ends_on'] ?? null,
        ];
    }

    /**
     * The first bill's amount in integer cents, if one was given.
     */
    public function amountCents(): ?int
    {
        return $this->filled('amount') ? Money::parseCents($this->string('amount')->toString()) : null;
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
            'starts_on' => 'first due date',
            'ends_on' => 'final payment date',
        ];
    }
}

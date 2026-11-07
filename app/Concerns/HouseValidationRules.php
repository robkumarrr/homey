<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait HouseValidationRules
{
    /**
     * Get the validation rules used to validate house details.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function houseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}

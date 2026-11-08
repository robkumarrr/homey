<?php

namespace App\Rules;

use App\Support\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class MoneyAmount implements ValidationRule
{
    /**
     * Accept a typed amount above zero, like "19.99", "$1,200" or "5".
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cents = is_string($value) ? Money::parseCents($value) : null;

        if ($cents === null || $cents === 0) {
            $fail('Enter an amount like 19.99 or $1,200.');
        }
    }
}

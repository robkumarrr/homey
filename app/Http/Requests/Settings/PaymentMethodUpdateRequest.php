<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PaymentMethodValidationRules;
use App\Models\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodUpdateRequest extends FormRequest
{
    use PaymentMethodValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $this->route('paymentMethod');

        return $this->paymentMethodRules($paymentMethod);
    }
}

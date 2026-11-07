<?php

namespace App\Http\Requests\Settings;

use App\Concerns\HouseValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class HouseUpdateRequest extends FormRequest
{
    use HouseValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->houseRules();
    }
}

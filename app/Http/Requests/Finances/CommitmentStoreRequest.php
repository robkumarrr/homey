<?php

namespace App\Http\Requests\Finances;

use App\Concerns\CommitmentValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CommitmentStoreRequest extends FormRequest
{
    use CommitmentValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->commitmentRules();
    }
}

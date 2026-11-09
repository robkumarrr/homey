<?php

namespace App\Http\Requests\Finances;

use App\Concerns\CommitmentValidationRules;
use App\Models\Commitment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CommitmentUpdateRequest extends FormRequest
{
    use CommitmentValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Commitment $commitment */
        $commitment = $this->route('commitment');

        return $this->commitmentRules($commitment);
    }
}

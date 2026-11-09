<?php

namespace App\Concerns;

use App\Enums\CategoryColour;
use App\Enums\CategoryIcon;
use App\Models\Category;
use App\Support\Tenancy\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;

trait CategoryValidationRules
{
    /**
     * Get the validation rules used to validate a category. Names are unique
     * within the family, archived categories included, so an archived name is
     * unarchived rather than added again.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string|Unique|Enum>>
     */
    protected function categoryRules(?Category $category = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'name')
                    ->where('family_id', app(Tenant::class)->familyId())
                    ->ignore($category),
            ],
            'icon' => ['required', Rule::enum(CategoryIcon::class)],
            'colour' => ['required', Rule::enum(CategoryColour::class)],
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
            'name.unique' => 'You already have a category called ":input". If it isn\'t listed, it\'s under Archived.',
        ];
    }
}

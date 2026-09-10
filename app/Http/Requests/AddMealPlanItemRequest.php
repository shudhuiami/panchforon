<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddMealPlanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'recipe_id' => ['required', 'integer', 'exists:recipes,id'],
            'servings' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}

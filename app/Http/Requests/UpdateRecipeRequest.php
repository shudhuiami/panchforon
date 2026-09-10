<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'cuisine' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'instructions' => ['sometimes', 'required', 'string'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'servings' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.raw_text' => ['required_without:ingredients.*.name', 'nullable', 'string'],
            'ingredients.*.name' => ['nullable', 'string'],
            'ingredients.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:50'],
        ];
    }
}

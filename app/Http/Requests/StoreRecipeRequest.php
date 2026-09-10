<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'cuisine' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'instructions' => ['required', 'string'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'servings' => ['nullable', 'integer', 'min:1', 'max:100'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.raw_text' => ['required_without:ingredients.*.name', 'nullable', 'string'],
            'ingredients.*.name' => ['nullable', 'string'],
            'ingredients.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:50'],
        ];
    }
}

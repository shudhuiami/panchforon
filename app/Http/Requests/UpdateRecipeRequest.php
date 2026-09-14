<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            /**
             * Only meaningful while the recipe is still a draft: "published"
             * sends it to the review queue, "draft" keeps it private. The
             * controller ignores it on a recipe that has already been
             * submitted, so editing one cannot demote it back to a draft.
             */
            'status' => ['sometimes', Rule::in(['draft', 'published'])],
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

    /**
     * Whether the author asked for this edit to submit the recipe for review.
     */
    public function publishesDraft(): bool
    {
        return $this->input('status') === 'published';
    }
}

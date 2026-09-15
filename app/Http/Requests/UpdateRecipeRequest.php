<?php

namespace App\Http\Requests;

use App\Enums\SpiceLevel;
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
            'name_bn' => ['nullable', 'string', 'max:255'],
            'cuisine' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'instructions' => ['sometimes', 'required', 'string'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'servings' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            /**
             * Minutes, never a phrase, and capped at a day: past that someone
             * is describing a marinade in the wrong field.
             */
            'prep_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'cook_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'spice_level' => ['nullable', Rule::enum(SpiceLevel::class)],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.raw_text' => ['required_without:ingredients.*.name', 'nullable', 'string'],
            'ingredients.*.name' => ['nullable', 'string'],
            'ingredients.*.quantity' => ['nullable', 'numeric', 'min:0'],
            /**
             * Deliberately not checked against the units table: imports carry
             * whatever the source wrote, and a line whose unit does not resolve
             * is a line that simply will not merge — not a 422.
             */
            'ingredients.*.unit' => ['nullable', 'string', 'max:50'],
            'ingredients.*.is_optional' => ['nullable', 'boolean'],
            'ingredients.*.note' => ['nullable', 'string', 'max:255'],
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

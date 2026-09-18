<?php

namespace App\Http\Requests;

use App\Enums\ModerationStatus;
use App\Enums\SpiceLevel;
use App\Services\SettingsRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipeRequest extends FormRequest
{
    /**
     * Writing the catalogue is a creator's job, so the role comes first: a
     * member has to apply for it before any of this is reachable, draft or not.
     *
     * The "user submissions" toggle on the admin settings screen then applies
     * on top. A draft is private to its author and never reaches the review
     * queue, so saving one is still allowed while submissions are closed; it is
     * publishing that counts as submitting.
     */
    public function authorize(): bool
    {
        if (! $this->user()?->isCreator()) {
            return false;
        }

        if ($this->savesAsDraft()) {
            return true;
        }

        return app(SettingsRepository::class)->boolean('submissions_open', true);
    }

    /**
     * Two different refusals wear the same 403, so each says which one it is:
     * a member needs to apply for the role, while a creator is only waiting for
     * the submissions toggle to come back on.
     */
    protected function failedAuthorization(): never
    {
        throw new AuthorizationException(
            $this->user()?->isCreator()
                ? 'Recipe submissions are currently closed.'
                : 'Only creators can post recipes. Apply to become a creator to share yours.',
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(['draft', 'published'])],
            'title' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'cuisine' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'instructions' => ['required', 'string'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'servings' => ['nullable', 'integer', 'min:1', 'max:100'],
            /**
             * Minutes, never a phrase, and capped at a day: past that someone
             * is describing a marinade in the wrong field.
             */
            'prep_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'cook_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'spice_level' => ['nullable', Rule::enum(SpiceLevel::class)],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.raw_text' => ['required_without:ingredients.*.name', 'nullable', 'string'],
            'ingredients.*.name' => ['nullable', 'string'],
            'ingredients.*.quantity' => ['nullable', 'numeric', 'min:0'],
            /**
             * Deliberately not checked against the units table: imports carry
             * whatever the source wrote ("bunch", "handful", "小さじ"), and a
             * line whose unit does not resolve is a line that simply will not
             * merge — not a reason to reject the whole recipe.
             */
            'ingredients.*.unit' => ['nullable', 'string', 'max:50'],
            'ingredients.*.is_optional' => ['nullable', 'boolean'],
            'ingredients.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * What the recipe is created as. Publishing is the default, so a client
     * that sends no status is saving something the site can show.
     *
     * The rule itself is ModerationStatus::forAuthor(), shared with the API's
     * publish endpoint and with the Creator Studio so that all three agree on
     * what a given author's save becomes.
     */
    public function moderationStatus(): ModerationStatus
    {
        return ModerationStatus::forAuthor($this->user(), $this->savesAsDraft());
    }

    /**
     * Read before validation runs, so it compares the raw input.
     */
    private function savesAsDraft(): bool
    {
        return $this->input('status') === 'draft';
    }
}

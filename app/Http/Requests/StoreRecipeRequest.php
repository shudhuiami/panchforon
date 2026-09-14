<?php

namespace App\Http\Requests;

use App\Enums\ModerationStatus;
use App\Services\SettingsRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipeRequest extends FormRequest
{
    /**
     * Honours the "user submissions" toggle on the admin settings screen. A
     * draft is private to its author and never reaches the review queue, so
     * saving one is still allowed while submissions are closed; it is
     * publishing that counts as submitting.
     */
    public function authorize(): bool
    {
        if ($this->savesAsDraft()) {
            return true;
        }

        return app(SettingsRepository::class)->boolean('submissions_open', true);
    }

    protected function failedAuthorization(): never
    {
        throw new AuthorizationException('Recipe submissions are currently closed.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(['draft', 'published'])],
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

    /**
     * What the recipe is created as. Publishing is the default so a client
     * that sends no status keeps submitting straight to the queue.
     */
    public function moderationStatus(): ModerationStatus
    {
        return $this->savesAsDraft() ? ModerationStatus::Draft : ModerationStatus::Pending;
    }

    /**
     * Read before validation runs, so it compares the raw input.
     */
    private function savesAsDraft(): bool
    {
        return $this->input('status') === 'draft';
    }
}

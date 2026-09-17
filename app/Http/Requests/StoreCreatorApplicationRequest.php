<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class StoreCreatorApplicationRequest extends FormRequest
{
    /**
     * Applying is for the people who cannot post yet.
     *
     * A creator already holds what this form asks for, and an admin holds more
     * than it, so neither has anything to gain from the queue — and letting
     * them in would put applications in front of a reviewer that no decision
     * could change. `isCreator()` answers for both, admins included.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isCreator();
    }

    /**
     * Said plainly, because the storefront shows this message as-is: someone
     * who lands on the form with the role already has a studio to go to.
     */
    protected function failedAuthorization(): never
    {
        throw new AuthorizationException('You can already post recipes, so there is nothing to apply for.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'pitch' => ['required', 'string', 'max:2000'],
            /**
             * Optional, and only ever a link: a creator's recipes carry a video
             * from their channel, but plenty of good cooks do not film.
             */
            'youtube_channel_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pitch.required' => 'Tell us what you cook — that is the whole application.',
            'youtube_channel_url.url' => 'That does not look like a link. Paste the address of your channel.',
        ];
    }
}

<?php

namespace App\Rules;

use App\Support\YouTubeVideoId;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;

/**
 * Accepts a pasted YouTube link and leaves the bare video id behind.
 *
 * The rule rewrites the attribute in place, so validated() hands back the
 * eleven-character id rather than whatever URL was typed. Nothing downstream
 * has to remember to normalise, and nothing but an id can reach the column
 * the embed is built from.
 */
class YouTubeVideoLink implements ValidationRule, ValidatorAwareRule
{
    protected Validator $validator;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute field must be a YouTube video link.');

            return;
        }

        $id = YouTubeVideoId::fromInput($value);

        if ($id === null) {
            $fail('The :attribute field must be a YouTube video link.');

            return;
        }

        $this->validator->setValue($attribute, $id);
    }

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }
}

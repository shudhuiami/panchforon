<?php

namespace App\Http\Requests;

use App\Enums\MealSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMealPlanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Every field is optional so a drag onto another day does not have to
     * resend the servings, but an empty body changes nothing and is rejected.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'servings' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'planned_for' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'meal_slot' => ['sometimes', Rule::enum(MealSlot::class)],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->hasAny(['servings', 'planned_for', 'meal_slot'])) {
                return;
            }

            $validator->errors()->add('servings', 'Send at least one of servings, planned_for or meal_slot.');
        });
    }
}

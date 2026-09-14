<?php

namespace App\Http\Requests;

use App\Enums\MealSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddMealPlanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Whether the day is inside the plan's range is checked by the controller,
     * which is the only place that knows which plan the dish is landing in.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'recipe_id' => ['required', 'integer', 'exists:recipes,id'],
            'servings' => ['nullable', 'integer', 'min:1', 'max:100'],
            'planned_for' => ['nullable', 'date_format:Y-m-d'],
            'meal_slot' => ['nullable', Rule::enum(MealSlot::class)],
        ];
    }
}

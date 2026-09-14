<?php

namespace App\Http\Requests;

use App\Models\MealPlan;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMealPlanRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_on.after_or_equal' => 'A plan starts today or later.',
            'ends_on.after_or_equal' => 'The plan has to end on or after it starts.',
        ];
    }

    /**
     * The length cap needs both ends parsed, so it runs once the field rules
     * have already vouched for the format.
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $startsOn = CarbonImmutable::parse((string) $this->input('starts_on'));
            $endsOn = CarbonImmutable::parse((string) $this->input('ends_on'));

            if (round($startsOn->diffInDays($endsOn)) + 1 > MealPlan::MAX_DAYS) {
                $validator->errors()->add('ends_on', 'A plan can cover at most '.MealPlan::MAX_DAYS.' days.');
            }
        });
    }
}

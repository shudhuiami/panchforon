<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMealPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Each field stands on its own here; whether the resulting range still
     * makes sense depends on the plan being edited, so the controller settles
     * that once it has loaded it.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $endsOn = ['sometimes', 'date_format:Y-m-d'];

        if ($this->has('starts_on')) {
            $endsOn[] = 'after_or_equal:starts_on';
        }

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'starts_on' => ['sometimes', 'date_format:Y-m-d'],
            'ends_on' => $endsOn,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_on.after_or_equal' => 'The plan has to end on or after it starts.',
        ];
    }
}

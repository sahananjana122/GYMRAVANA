<?php

namespace App\Http\Requests\Admin;

use App\Models\GameGoal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            'exercise_name' => ['required', 'string', 'max:160'],
            'metric_type' => ['required', Rule::in(GameGoal::METRICS)],
            'target_value' => [
                'required', 'numeric', 'min:0.01',
                'max:'.($this->input('metric_type') === GameGoal::METRIC_PERCENTAGE ? 100 : 1000000),
            ],
            'pace_target' => [
                Rule::requiredIf(fn (): bool => $this->input('metric_type') === GameGoal::METRIC_PACE_DURATION),
                'nullable', 'numeric', 'min:0.01', 'max:1000',
            ],
            'pace_unit' => [
                Rule::requiredIf(fn (): bool => $this->input('metric_type') === GameGoal::METRIC_PACE_DURATION),
                'nullable', Rule::in(GameGoal::PACE_UNITS),
            ],
            'validation_method' => ['required', Rule::in(GameGoal::VALIDATION_METHODS)],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

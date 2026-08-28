<?php

namespace App\Http\Requests\Admin;

use App\Models\GamificationMission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GamificationMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(GamificationMission::KINDS)],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'metric' => ['required', Rule::in(GamificationMission::METRICS)],
            'target_value' => ['required', 'integer', 'min:1', 'max:1000000'],
            'reward_xp' => ['required', 'integer', 'min:0', 'max:10000'],
            'starts_on' => [
                Rule::requiredIf(fn (): bool => $this->input('kind') === GamificationMission::KIND_CHALLENGE),
                'nullable',
                'date',
            ],
            'ends_on' => [
                Rule::requiredIf(fn (): bool => $this->input('kind') === GamificationMission::KIND_CHALLENGE),
                'nullable',
                'date',
                'after_or_equal:starts_on',
            ],
            'status' => ['required', Rule::in(GamificationMission::STATUSES)],
        ];
    }
}

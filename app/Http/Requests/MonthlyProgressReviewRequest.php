<?php

namespace App\Http\Requests;

use App\Models\MonthlyProgressReview;
use App\Models\User;
use App\Services\TrainerClientAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MonthlyProgressReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->user()?->trainerProfile;
        /** @var User|null $member */
        $member = $this->route('member');

        return $profile && $member
            ? app(TrainerClientAccessService::class)->canManage($profile, $member)
            : false;
    }

    public function rules(): array
    {
        return [
            'review_month' => ['required', 'date_format:Y-m'],
            'monthly_goals' => ['nullable', 'string', 'max:3000'],
            'goal_completion_percent' => ['nullable', 'integer', 'between:0,100'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'assessment' => ['nullable', Rule::in(MonthlyProgressReview::ASSESSMENTS)],
            'trainer_notes' => ['nullable', 'string', 'max:5000'],
            'next_month_goals' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->date('review_month', 'Y-m')?->startOfMonth()->isAfter(today()->startOfMonth())) {
                    $validator->errors()->add('review_month', 'Monthly reviews cannot be recorded for a future month.');
                }
            },
        ];
    }
}

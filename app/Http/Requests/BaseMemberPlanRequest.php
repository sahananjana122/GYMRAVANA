<?php

namespace App\Http\Requests;

use App\Models\MemberPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BaseMemberPlanRequest extends FormRequest
{
    protected function planRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'overview' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in([
                MemberPlan::STATUS_DRAFT,
                MemberPlan::STATUS_ACTIVE,
                MemberPlan::STATUS_COMPLETED,
            ])],
            'items' => ['required', 'array', 'min:1', 'max:40'],
            'items.*.day_of_week' => ['nullable', 'integer', 'between:1,7'],
            'items.*.scheduled_time' => ['nullable', 'date_format:H:i'],
            'items.*.section' => ['nullable', 'string', 'max:100'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.instructions' => ['nullable', 'string', 'max:3000'],
            'items.*.target' => ['nullable', 'string', 'max:255'],
        ];
    }
}

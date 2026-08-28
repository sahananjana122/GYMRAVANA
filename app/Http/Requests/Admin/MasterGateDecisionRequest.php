<?php

namespace App\Http\Requests\Admin;

use App\Models\MasterGateApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterGateDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([
                MasterGateApplication::STATUS_APPROVED,
                MasterGateApplication::STATUS_DECLINED,
                MasterGateApplication::STATUS_REVOKED,
            ])],
            'review_notes' => ['required', 'string', 'max:3000'],
            'override_reason' => ['nullable', 'string', 'max:3000'],
        ];
    }
}

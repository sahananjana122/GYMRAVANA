<?php

namespace App\Http\Requests\Admin;

use App\Models\FinanceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'transaction_type' => ['required', Rule::in(array_keys(FinanceCategory::TYPES))],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

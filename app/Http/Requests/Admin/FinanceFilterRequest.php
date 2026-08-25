<?php

namespace App\Http\Requests\Admin;

use App\Models\FinanceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'from_date' => $this->input('from_date'),
            'to_date' => $this->input('to_date'),
            'month' => $this->input('month'),
            'year' => $this->input('year'),
            'transaction_type' => $this->input('transaction_type'),
            'finance_category_id' => $this->input('finance_category_id'),
        ], fn ($value) => $value !== ''));
    }

    public function rules(): array
    {
        return [
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'transaction_type' => ['nullable', Rule::in(array_keys(FinanceCategory::TYPES))],
            'finance_category_id' => ['nullable', 'integer', 'exists:finance_categories,id'],
        ];
    }
}

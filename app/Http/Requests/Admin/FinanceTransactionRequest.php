<?php

namespace App\Http\Requests\Admin;

use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FinanceTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'transaction_type' => ['required', Rule::in(array_keys(FinanceCategory::TYPES))],
            'finance_category_id' => ['required', 'integer', 'exists:finance_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'programme_name' => ['nullable', 'string', 'max:160'],
            'supplier_payee' => ['nullable', 'string', 'max:160'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $category = FinanceCategory::find($this->integer('finance_category_id'));

            if (! $category) {
                return;
            }

            if ($category->transaction_type !== $this->input('transaction_type')) {
                $validator->errors()->add('finance_category_id', 'Select a category matching the transaction type.');
            }

            $transaction = $this->route('financialTransaction');
            $isCurrentCategory = $transaction instanceof FinancialTransaction
                && $transaction->finance_category_id === $category->id;

            if (! $category->is_active && ! $isCurrentCategory) {
                $validator->errors()->add('finance_category_id', 'Select an active finance category.');
            }
        }];
    }
}

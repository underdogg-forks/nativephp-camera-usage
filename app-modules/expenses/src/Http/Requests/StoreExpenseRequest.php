<?php

namespace Modules\Expenses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Expenses\Enums\ExpenseType;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:expense_categories,id',
            'expense_number' => 'required|string|unique:expenses',
            'expense_type' => ['required', 'string', 'in:' . implode(',', array_map(fn($case) => $case->value, ExpenseType::cases()))],
            'expense_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string',
            'expensed_at' => 'required|date',
            'receipt_path' => 'nullable|string',
        ];
    }
}

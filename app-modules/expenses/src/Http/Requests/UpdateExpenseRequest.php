<?php

namespace Modules\Expenses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Enums\ExpenseType;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:expense_categories,id',
            'expense_type' => ['nullable', 'string', 'in:' . implode(',', array_map(fn($case) => $case->value, ExpenseType::cases()))],
            'expense_status' => ['nullable', 'string', 'in:' . implode(',', array_map(fn($case) => $case->value, ExpenseStatus::cases()))],
            'expense_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string',
            'expensed_at' => 'nullable|date',
            'receipt_path' => 'nullable|string',
        ];
    }
}

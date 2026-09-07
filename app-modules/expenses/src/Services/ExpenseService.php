<?php

namespace Modules\Expenses\Services;

use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Models\Expense;

class ExpenseService
{
    public function createExpense(array $data): Expense
    {
        return Expense::create($data);
    }

    public function updateExpense(Expense $expense, array $data): Expense
    {
        $expense->update($data);

        return $expense;
    }

    public function deleteExpense(Expense $expense): void
    {
        $expense->delete();
    }

    public function approveExpense(Expense $expense): Expense
    {
        $expense->update(['expense_status' => ExpenseStatus::APPROVED->value]);

        return $expense;
    }

    public function rejectExpense(Expense $expense): Expense
    {
        $expense->update(['expense_status' => ExpenseStatus::DRAFT->value]);

        return $expense;
    }
}

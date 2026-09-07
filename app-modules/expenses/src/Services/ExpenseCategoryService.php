<?php

namespace Modules\Expenses\Services;

use Modules\Expenses\Models\ExpenseCategory;

class ExpenseCategoryService
{
    public function createExpenseCategory(array $data): ExpenseCategory
    {
        return ExpenseCategory::create($data);
    }

    public function updateExpenseCategory(ExpenseCategory $category, array $data): ExpenseCategory
    {
        $category->update($data);

        return $category;
    }

    public function deleteExpenseCategory(ExpenseCategory $category): void
    {
        $category->delete();
    }
}

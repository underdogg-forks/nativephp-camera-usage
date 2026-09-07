<?php

namespace Modules\Expenses\Support;

use Modules\Core\Models\Company;
use Modules\Expenses\Models\Expense;

/**
 * Generates the next sequential expense_number for a company, formatted
 * EXP-00001. Counts existing expenses for that company (ignoring the
 * BelongsToCompany global scope, since number generation must see every
 * row regardless of who is currently authenticated) rather than keeping a
 * separate counter table.
 */
class ExpenseNumberGenerator
{
    public static function next(Company $company): string
    {
        $count = Expense::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->count();

        return sprintf('EXP-%05d', $count + 1);
    }
}

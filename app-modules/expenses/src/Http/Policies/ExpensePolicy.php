<?php

namespace Modules\Expenses\Http\Policies;

use App\Models\User;
use Modules\Expenses\Models\Expense;

class ExpensePolicy
{
    public function view(User $user, Expense $expense): bool
    {
        return $user->id === $expense->user_id;
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->id === $expense->user_id;
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->id === $expense->user_id;
    }
}

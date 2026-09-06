<?php

namespace Modules\Expenses\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Expenses\Http\Policies\ExpensePolicy;
use Modules\Expenses\Models\Expense;

class ExpensesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->routes();

        Gate::policy(Expense::class, ExpensePolicy::class);
    }

    protected function routes(): void
    {
        \Illuminate\Support\Facades\Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../Routes/api.php');
    }
}

<?php

namespace Modules\Expenses\Providers;

use Illuminate\Support\ServiceProvider;

class ExpensesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

<?php

namespace Modules\Expenses\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runModuleMigrations();
        $this->createDefaultUser();
    }

    protected function runModuleMigrations(): void
    {
        Artisan::call('migrate', [
            '--path' => 'Modules/Expenses/Database/Migrations',
        ]);
    }

    protected function createDefaultUser(): void
    {
        User::create([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'id' => 2,
            'name' => 'Second User',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}

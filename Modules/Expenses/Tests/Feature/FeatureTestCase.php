<?php

namespace Modules\Expenses\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createDefaultUser();
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

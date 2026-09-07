<?php

namespace Modules\Expenses\Tests\Feature;

use App\Models\User;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Enums\ExpenseType;
use Modules\Expenses\Models\Expense;
use Modules\Expenses\Models\ExpenseCategory;
use PHPUnit\Framework\Attributes\Test;

class ExpenseApiAuthTest extends FeatureTestCase
{
    #[Test]
    public function it_requires_bearer_token_to_access_protected_routes(): void
    {
        /* Act & Assert */
        $response = $this->postJson('/api/expenses');
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    #[Test]
    public function it_creates_expense_with_valid_token(): void
    {
        /* Arrange */
        $user = User::find(1);
        $token = $user->createToken('test-token')->plainTextToken;
        $category = ExpenseCategory::factory()->create();

        $payload = [
            'category_id' => $category->id,
            'expense_number' => 'EXP-API-001',
            'expense_type' => 'one_time',
            'expense_amount' => 99.99,
            'currency' => 'USD',
            'description' => 'Conference ticket via API',
            'expensed_at' => now()->toDateTimeString(),
        ];

        /* Act */
        $response = $this->withToken($token)->postJson('/api/expenses', $payload);

        /* Assert */
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'expense' => [
                'id',
                'user_id',
                'expense_number',
                'expense_amount',
                'currency',
                'description',
                'expense_status',
                'expense_type',
            ],
        ]);
        $response->assertJson(['expense' => ['expense_amount' => 99.99]]);
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'expense_number' => 'EXP-API-001',
        ]);
    }

    #[Test]
    public function it_lists_user_expenses_via_api(): void
    {
        /* Arrange */
        $user = User::find(1);
        $otherUser = User::find(2);
        $token = $user->createToken('test-token')->plainTextToken;

        Expense::factory()->count(3)->create(['user_id' => $user->id]);
        Expense::factory()->count(2)->create(['user_id' => $otherUser->id]);

        /* Act */
        $response = $this->withToken($token)->getJson('/api/expenses');

        /* Assert */
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'expenses' => [
                '*' => [
                    'id',
                    'expense_number',
                    'expense_amount',
                    'expense_status',
                ],
            ],
        ]);
        $response->assertJsonCount(3, 'expenses');
    }

    #[Test]
    public function it_retrieves_single_expense_via_api(): void
    {
        /* Arrange */
        $user = User::find(1);
        $token = $user->createToken('test-token')->plainTextToken;
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        /* Act */
        $response = $this->withToken($token)->getJson("/api/expenses/{$expense->id}");

        /* Assert */
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'expense']);
        $response->assertJson(['expense' => ['id' => $expense->id]]);
    }

    #[Test]
    public function it_forbids_accessing_other_users_expense(): void
    {
        /* Arrange */
        $user = User::find(1);
        $otherUser = User::find(2);
        $token = $user->createToken('test-token')->plainTextToken;
        $expense = Expense::factory()->create(['user_id' => $otherUser->id]);

        /* Act */
        $response = $this->withToken($token)->getJson("/api/expenses/{$expense->id}");

        /* Assert */
        $response->assertStatus(403);
        $response->assertJsonPath('message', 'This action is unauthorized.');
    }

    #[Test]
    public function it_updates_expense_with_valid_token(): void
    {
        /* Arrange */
        $user = User::find(1);
        $token = $user->createToken('test-token')->plainTextToken;
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $updatePayload = [
            'expense_status' => ExpenseStatus::SUBMITTED->value,
            'description' => 'Updated description',
        ];

        /* Act */
        $response = $this->withToken($token)->putJson("/api/expenses/{$expense->id}", $updatePayload);

        /* Assert */
        $response->assertStatus(200);
        $response->assertJson(['expense' => ['description' => 'Updated description']]);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Updated description',
            'expense_status' => ExpenseStatus::SUBMITTED->value,
        ]);
    }

    #[Test]
    public function it_deletes_expense_with_valid_token(): void
    {
        /* Arrange */
        $user = User::find(1);
        $token = $user->createToken('test-token')->plainTextToken;
        $expense = Expense::factory()->create(['user_id' => $user->id]);
        $expenseId = $expense->id;

        /* Act */
        $response = $this->withToken($token)->deleteJson("/api/expenses/{$expenseId}");

        /* Assert */
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
    }

    #[Test]
    public function it_approves_expense(): void
    {
        /* Arrange */
        $user = User::find(1);
        $token = $user->createToken('test-token')->plainTextToken;
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'expense_status' => ExpenseStatus::SUBMITTED->value,
        ]);

        /* Act */
        $response = $this->withToken($token)->putJson("/api/expenses/{$expense->id}/approve");

        /* Assert */
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'expense' => ['expense_status' => ExpenseStatus::APPROVED->value],
        ]);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'expense_status' => ExpenseStatus::APPROVED->value,
        ]);
    }

    #[Test]
    public function it_rejects_expense(): void
    {
        /* Arrange */
        $user = User::find(1);
        $token = $user->createToken('test-token')->plainTextToken;
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'expense_status' => ExpenseStatus::SUBMITTED->value,
        ]);

        /* Act */
        $response = $this->withToken($token)->putJson("/api/expenses/{$expense->id}/reject");

        /* Assert */
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'expense_status' => ExpenseStatus::DRAFT->value,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_on_create(): void
    {
        /* Arrange */
        $user = User::find(1);
        $token = $user->createToken('test-token')->plainTextToken;

        $payload = [
            // missing required fields
        ];

        /* Act */
        $response = $this->withToken($token)->postJson('/api/expenses', $payload);

        /* Assert */
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    #[Test]
    public function it_validates_numeric_expense_amount(): void
    {
        /* Arrange */
        $user = User::find(1);
        $token = $user->createToken('test-token')->plainTextToken;
        $category = ExpenseCategory::factory()->create();

        $payload = [
            'category_id' => $category->id,
            'expense_number' => 'EXP-INVALID',
            'expense_type' => 'one_time',
            'expense_amount' => 'not-a-number',
            'currency' => 'USD',
            'description' => 'Invalid amount',
            'expensed_at' => now()->toDateTimeString(),
        ];

        /* Act */
        $response = $this->withToken($token)->postJson('/api/expenses', $payload);

        /* Assert */
        $response->assertStatus(422);
        $response->assertJsonPath('errors.expense_amount', [
            'The expense amount field must be a number.',
        ]);
    }

    #[Test]
    public function it_validates_valid_expense_type(): void
    {
        /* Arrange */
        $user = User::find(1);
        $token = $user->createToken('test-token')->plainTextToken;
        $category = ExpenseCategory::factory()->create();

        $payload = [
            'category_id' => $category->id,
            'expense_number' => 'EXP-INVALID-TYPE',
            'expense_type' => 'invalid_type',
            'expense_amount' => 50.00,
            'currency' => 'USD',
            'description' => 'Invalid type',
            'expensed_at' => now()->toDateTimeString(),
        ];

        /* Act */
        $response = $this->withToken($token)->postJson('/api/expenses', $payload);

        /* Assert */
        $response->assertStatus(422);
        $this->assertTrue($response->json('errors.expense_type') !== null);
    }
}

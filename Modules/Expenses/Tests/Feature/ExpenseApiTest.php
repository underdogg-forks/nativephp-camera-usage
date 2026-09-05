<?php

namespace Modules\Expenses\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Expenses\Models\Expense;
use PHPUnit\Framework\Attributes\Test;

class ExpenseApiTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('expenses');
    }

    #[Test]
    public function it_creates_an_expense_via_api(): void
    {
        /* Arrange */
        $payload = [
            'user_id' => 1,
            'amount' => 99.99,
            'currency' => 'USD',
            'description' => 'Conference ticket',
            'expense_date' => now()->toDateString(),
            'status' => 'pending',
        ];

        /* Act */
        $response = $this->postJson('/api/expense', $payload);

        /* Assert */
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'expense' => [
                'id',
                'user_id',
                'amount',
                'currency',
                'description',
                'expense_date',
                'status',
            ],
        ]);
        $this->assertDatabaseHas('expenses', $payload);
    }

    #[Test]
    public function it_retrieves_user_expenses_via_api(): void
    {
        /* Arrange */
        $expense1 = Expense::factory()->create(['user_id' => 1]);
        $expense2 = Expense::factory()->create(['user_id' => 1]);
        Expense::factory()->create(['user_id' => 2]);

        /* Act */
        $response = $this->getJson('/api/expenses/1');

        /* Assert */
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'expenses']);
        $response->assertJsonCount(2, 'expenses');
    }

    #[Test]
    public function it_retrieves_single_expense_via_api(): void
    {
        /* Arrange */
        $expense = Expense::factory()->create();

        /* Act */
        $response = $this->getJson("/api/expense/{$expense->id}");

        /* Assert */
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'expense']);
        $response->assertJson(['expense' => ['id' => $expense->id]]);
    }

    #[Test]
    public function it_returns_404_for_missing_expense(): void
    {
        /* Act */
        $response = $this->getJson('/api/expense/99999');

        /* Assert */
        $response->assertStatus(404);
        $response->assertJson(['error' => 'Expense not found']);
    }

    #[Test]
    public function it_updates_an_expense_via_api(): void
    {
        /* Arrange */
        $expense = Expense::factory()->create();
        $payload = [
            'status' => 'approved',
            'description' => 'Updated description',
        ];

        /* Act */
        $response = $this->putJson("/api/expense/{$expense->id}", $payload);

        /* Assert */
        $response->assertStatus(200);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => 'approved',
            'description' => 'Updated description',
        ]);
    }

    #[Test]
    public function it_deletes_an_expense_via_api(): void
    {
        /* Arrange */
        $expense = Expense::factory()->create();

        /* Act */
        $response = $this->deleteJson("/api/expense/{$expense->id}");

        /* Assert */
        $response->assertStatus(200);
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    #[Test]
    public function it_validates_required_fields_on_create(): void
    {
        /* Arrange */
        $payload = [
            'user_id' => 1,
            // missing required amount, currency, expense_date
        ];

        /* Act */
        $response = $this->postJson('/api/expense', $payload);

        /* Assert */
        $response->assertStatus(422);
    }

    #[Test]
    public function it_validates_numeric_amount(): void
    {
        /* Arrange */
        $payload = [
            'user_id' => 1,
            'amount' => 'not-a-number',
            'currency' => 'USD',
            'expense_date' => now()->toDateString(),
        ];

        /* Act */
        $response = $this->postJson('/api/expense', $payload);

        /* Assert */
        $response->assertStatus(422);
    }

    #[Test]
    public function it_validates_valid_status(): void
    {
        /* Arrange */
        $payload = [
            'user_id' => 1,
            'amount' => 99.99,
            'currency' => 'USD',
            'expense_date' => now()->toDateString(),
            'status' => 'invalid-status',
        ];

        /* Act */
        $response = $this->postJson('/api/expense', $payload);

        /* Assert */
        $response->assertStatus(422);
    }
}

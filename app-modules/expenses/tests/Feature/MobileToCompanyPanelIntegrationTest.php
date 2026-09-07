<?php

namespace Modules\Expenses\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Core\Database\Seeders\RolesSeeder;
use Modules\Core\Enums\UserRole;
use Modules\Core\Models\Company;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Filament\Company\Resources\Expenses\Pages\ListExpenses;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * End-to-end proof of the workflow described in CLAUDE.md: mobile app
 * captures a receipt -> authenticated REST API creates the expense ->
 * secretary reviews and approves it in the Company Filament panel.
 * Ties together the personal API (Task: REST API with Sanctum) and the
 * new multi-tenant Filament admin (Tasks 3-8) for the first time.
 */
class MobileToCompanyPanelIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_expense_submitted_via_the_mobile_api_is_company_scoped_and_visible_to_the_secretary(): void
    {
        /* Arrange: a company with a mobile-app employee and an office secretary */
        (new RolesSeeder())->run();
        $company = Company::factory()->create();

        $mobileUser = User::factory()->create();
        $mobileUser->companies()->attach($company->id);
        $mobileUser->assignRole(UserRole::CUSTOMER->value);
        $token = $mobileUser->createToken('mobile')->plainTextToken;

        $secretary = User::factory()->create();
        $secretary->companies()->attach($company->id);
        $secretary->assignRole(UserRole::CUSTOMER_ADMIN->value);

        /* Act: mobile app captures a receipt and posts it to the API (always starts as draft) */
        $response = $this->withToken($token)->postJson('/api/expenses', [
            'expense_number' => 'EXP-MOBILE-001',
            'expense_type' => 'one_time',
            'expense_amount' => 24.99,
            'currency' => 'USD',
            'description' => 'Taxi receipt',
            'expensed_at' => now()->toDateTimeString(),
            'receipt_path' => 'receipts/2026/09/07/photo.jpg',
        ]);

        $response->assertStatus(201);
        $expenseId = $response->json('expense.id');
        $this->assertDatabaseHas('expenses', [
            'id' => $expenseId,
            'company_id' => $company->id,
            'user_id' => $mobileUser->id,
            'expense_status' => ExpenseStatus::DRAFT->value,
        ]);

        /* Act: mobile user submits it for review */
        $this->withToken($token)->putJson("/api/expenses/{$expenseId}", [
            'expense_status' => ExpenseStatus::SUBMITTED->value,
        ])->assertStatus(200);

        /* Act & Assert: the secretary sees and approves it in the Filament panel */
        Filament::setTenant($company, isQuiet: true);

        Livewire::actingAs($secretary)
            ->test(ListExpenses::class)
            ->assertCanSeeTableRecords([\Modules\Expenses\Models\Expense::find($expenseId)])
            ->callTableAction('approve', $expenseId);

        $this->assertDatabaseHas('expenses', [
            'id' => $expenseId,
            'expense_status' => ExpenseStatus::APPROVED->value,
        ]);
    }

    #[Test]
    public function a_mobile_user_with_no_company_keeps_using_the_api_as_a_purely_personal_expense_log(): void
    {
        /* Arrange: pre-existing camera-flow user, no company at all */
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        /* Act */
        $response = $this->withToken($token)->postJson('/api/expenses', [
            'expense_number' => 'EXP-PERSONAL-001',
            'expense_type' => 'one_time',
            'expense_amount' => 12.00,
            'currency' => 'USD',
            'expensed_at' => now()->toDateTimeString(),
        ]);

        /* Assert: still works, just has no company */
        $response->assertStatus(201);
        $this->assertDatabaseHas('expenses', [
            'expense_number' => 'EXP-PERSONAL-001',
            'company_id' => null,
            'user_id' => $user->id,
        ]);
    }
}

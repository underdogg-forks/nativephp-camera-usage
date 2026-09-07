<?php

namespace Modules\Expenses\Tests\Feature;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Livewire\Livewire;
use Modules\Core\Enums\UserRole;
use Modules\Core\Tests\AbstractCompanyPanelTestCase;
use Modules\Expenses\Filament\Company\Resources\ExpenseCategories\Pages\ListExpenseCategories;
use Modules\Expenses\Models\ExpenseCategory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ListExpenseCategories::class)]
class ExpenseCategoryResourceTest extends AbstractCompanyPanelTestCase
{
    #[Test]
    public function it_lists_only_the_current_companys_categories(): void
    {
        /* Arrange */
        $ownCategory = ExpenseCategory::factory()->for($this->company, 'company')->create(['category_name' => 'Travel']);

        $otherCompany = \Modules\Core\Models\Company::factory()->create();
        ExpenseCategory::factory()->for($otherCompany, 'company')->create(['category_name' => 'Other Co Category']);

        /* Act */
        $component = Livewire::actingAs($this->user)->test(ListExpenseCategories::class);

        /* Assert */
        $component->assertCanSeeTableRecords([$ownCategory]);
    }

    #[Test]
    public function customer_admin_can_create_an_expense_category(): void
    {
        /* Act */
        Livewire::actingAs($this->user)
            ->test(ListExpenseCategories::class)
            ->callAction('create', data: [
                'category_name' => 'Office Supplies',
            ]);

        /* Assert */
        $this->assertDatabaseHas('expense_categories', [
            'category_name' => 'Office Supplies',
            'company_id' => $this->company->id,
        ]);
    }

    #[Test]
    public function customer_admin_can_edit_an_expense_category(): void
    {
        /* Arrange */
        $category = ExpenseCategory::factory()->for($this->company, 'company')->create(['category_name' => 'Travel']);

        /* Act */
        Livewire::actingAs($this->user)
            ->test(ListExpenseCategories::class)
            ->callTableAction(EditAction::class, $category, data: [
                'category_name' => 'Business Travel',
            ]);

        /* Assert */
        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'category_name' => 'Business Travel',
        ]);
    }

    #[Test]
    public function customer_admin_can_delete_an_expense_category(): void
    {
        /* Arrange */
        $category = ExpenseCategory::factory()->for($this->company, 'company')->create();

        /* Act */
        Livewire::actingAs($this->user)
            ->test(ListExpenseCategories::class)
            ->callTableAction(DeleteAction::class, $category);

        /* Assert */
        $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
    }

    #[Test]
    public function a_customer_without_admin_role_cannot_create_categories(): void
    {
        /* Arrange */
        $this->user->syncRoles([UserRole::CUSTOMER->value]);

        /* Act */
        $component = Livewire::actingAs($this->user)->test(ListExpenseCategories::class);

        /* Assert */
        $component->assertActionHidden('create');
    }
}

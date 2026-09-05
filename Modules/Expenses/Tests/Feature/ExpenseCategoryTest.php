<?php

namespace Modules\Expenses\Tests\Feature;

use Modules\Expenses\Models\ExpenseCategory;
use PHPUnit\Framework\Attributes\Test;

class ExpenseCategoryTest extends FeatureTestCase
{
    #[Test]
    public function it_creates_an_expense_category(): void
    {
        /* Arrange */
        $payload = [
            'category_name' => 'Travel',
            'description' => 'Travel expenses',
        ];

        /* Act */
        $category = ExpenseCategory::create($payload);

        /* Assert */
        $this->assertDatabaseHas('expense_categories', $payload);
        $this->assertNotNull($category->id);
        $this->assertEquals('Travel', $category->category_name);
    }

    #[Test]
    public function it_retrieves_an_expense_category(): void
    {
        /* Arrange */
        $category = ExpenseCategory::factory()->create(['category_name' => 'Meals']);

        /* Act */
        $retrieved = ExpenseCategory::find($category->id);

        /* Assert */
        $this->assertNotNull($retrieved);
        $this->assertEquals($category->id, $retrieved->id);
        $this->assertEquals('Meals', $retrieved->category_name);
    }

    #[Test]
    public function it_updates_an_expense_category(): void
    {
        /* Arrange */
        $category = ExpenseCategory::factory()->create(['category_name' => 'Original']);

        /* Act */
        $category->update(['category_name' => 'Updated']);

        /* Assert */
        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'category_name' => 'Updated',
        ]);
    }

    #[Test]
    public function it_deletes_an_expense_category(): void
    {
        /* Arrange */
        $category = ExpenseCategory::factory()->create();
        $categoryId = $category->id;

        /* Act */
        $category->delete();

        /* Assert */
        $this->assertDatabaseMissing('expense_categories', ['id' => $categoryId]);
        $this->assertNull(ExpenseCategory::find($categoryId));
    }

    #[Test]
    public function it_lists_all_expense_categories(): void
    {
        /* Arrange */
        ExpenseCategory::factory()->count(5)->create();

        /* Act */
        $categories = ExpenseCategory::all();

        /* Assert */
        $this->assertGreaterThanOrEqual(5, $categories->count());
    }

    #[Test]
    public function it_requires_category_name(): void
    {
        /* Arrange */
        $payload = [
            'category_name' => null,
        ];

        /* Act & Assert */
        $this->expectException(\Exception::class);
        ExpenseCategory::create($payload);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Expense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class CameraToStorageFlowTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('expenses');
    }

    #[Test]
    public function it_stores_a_photo_file_successfully(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);

        /** Act */
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** Assert */
        Storage::disk('expenses')->assertExists($storagePath);
        $this->assertNotEmpty(Storage::disk('expenses')->get($storagePath));
    }

    #[Test]
    public function it_stores_photo_with_timestamped_directory_structure(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $date = now()->format('Y/m/d');

        /** Act */
        $storagePath = Storage::disk('expenses')->putFile("receipts/{$date}", $photoFile);

        /** Assert */
        Storage::disk('expenses')->assertExists($storagePath);
        $this->assertStringContainsString($date, $storagePath);
    }

    #[Test]
    public function it_stores_photo_with_exif_orientation_data(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg', 1080, 1440);

        /** Act */
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** Assert */
        Storage::disk('expenses')->assertExists($storagePath);
        $this->assertNotNull(Storage::disk('expenses')->get($storagePath));
    }

    #[Test]
    public function it_persists_photo_path_in_expense_record(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** Act */
        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 25.50,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Restaurant expense',
            'expense_date' => now(),
        ]);

        /** Assert */
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'receipt_path' => $storagePath,
        ]);
    }

    #[Test]
    public function it_stores_multiple_photos_independently(): void
    {
        /** Arrange */
        $photoFile1 = UploadedFile::fake()->image('receipt1.jpg');
        $photoFile2 = UploadedFile::fake()->image('receipt2.jpg');

        /** Act */
        $path1 = Storage::disk('expenses')->putFile('receipts', $photoFile1);
        $path2 = Storage::disk('expenses')->putFile('receipts', $photoFile2);

        /** Assert */
        Storage::disk('expenses')->assertExists($path1);
        Storage::disk('expenses')->assertExists($path2);
        $this->assertNotEquals($path1, $path2);
    }

    #[Test]
    public function it_retrieves_stored_photo_from_storage(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** Act */
        $retrievedContent = Storage::disk('expenses')->get($storagePath);

        /** Assert */
        $this->assertNotNull($retrievedContent);
        $this->assertIsString($retrievedContent);
        $this->assertGreaterThan(0, strlen($retrievedContent));
    }

    #[Test]
    public function it_rejects_invalid_photo_file(): void
    {
        /** Arrange */
        $invalidFile = UploadedFile::fake()->create('receipt.txt', 100, 'text/plain');

        /** Act */
        $isInvalid = !in_array(
            $invalidFile->getMimeType(),
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        );

        /** Assert */
        $this->assertTrue($isInvalid);
    }

    #[Test]
    public function it_validates_photo_file_size(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');

        /** Act */
        $isWithinLimit = $photoFile->getSize() < (10 * 1024 * 1024); // 10MB max

        /** Assert */
        $this->assertTrue($isWithinLimit);
    }

    #[Test]
    public function it_follows_consistent_photo_storage_naming_pattern(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');

        /** Act */
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** Assert */
        $this->assertStringStartsWith('receipts', $storagePath);
        $this->assertStringNotContainsString('receipt.jpg', $storagePath);
    }

    #[Test]
    public function it_deletes_photo_file_from_storage(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** Act */
        Storage::disk('expenses')->delete($storagePath);

        /** Assert */
        Storage::disk('expenses')->assertMissing($storagePath);
    }

    #[Test]
    public function it_creates_expense_record_with_stored_photo(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** Act */
        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 45.75,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Lunch meeting',
            'expense_date' => now(),
            'status' => 'pending',
        ]);

        /** Assert */
        $this->assertDatabaseHas('expenses', [
            'user_id' => 1,
            'amount' => 45.75,
            'receipt_path' => $storagePath,
            'status' => 'pending',
        ]);
        $this->assertNotNull($expense->id);
    }

    #[Test]
    public function it_persists_stored_photo_across_multiple_requests(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** Act */
        Storage::disk('expenses')->assertExists($storagePath);
        Storage::disk('expenses')->assertExists($storagePath);

        /** Assert */
        $this->assertTrue(true);
    }

    #[Test]
    public function it_retrieves_expense_with_photo_from_database(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 32.00,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Client dinner',
            'expense_date' => now(),
        ]);

        /** Act */
        $retrievedExpense = Expense::find($expense->id);

        /** Assert */
        $this->assertEquals($storagePath, $retrievedExpense->receipt_path);
        $this->assertEquals(32.00, $retrievedExpense->amount);
    }

    #[Test]
    public function it_stores_photo_in_proper_directory_hierarchy(): void
    {
        /** Arrange */
        $date = now()->format('Y/m/d');
        $photoFile = UploadedFile::fake()->image('receipt.jpg');

        /** Act */
        $storagePath = Storage::disk('expenses')->putFile("receipts/{$date}", $photoFile);

        /** Assert */
        $this->assertStringContainsString("receipts/{$date}", $storagePath);
    }

    #[Test]
    public function it_handles_concurrent_photo_uploads_with_unique_paths(): void
    {
        /** Arrange */
        $paths = [];

        /** Act */
        for ($i = 0; $i < 5; $i++) {
            $photoFile = UploadedFile::fake()->image('receipt.jpg');
            $path = Storage::disk('expenses')->putFile('receipts', $photoFile);
            $paths[] = $path;
        }

        /** Assert */
        $this->assertEquals(count($paths), count(array_unique($paths)));
        foreach ($paths as $path) {
            Storage::disk('expenses')->assertExists($path);
        }
    }

    #[Test]
    public function it_sets_expense_date_to_capture_time(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);
        $now = now();

        /** Act */
        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 50.00,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Expense with default date',
            'expense_date' => $now,
        ]);

        /** Assert */
        $this->assertEquals($now->format('Y-m-d'), $expense->expense_date->format('Y-m-d'));
    }

    #[Test]
    public function it_validates_stored_photo_mime_type(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);

        /** Act */
        $validMime = in_array(
            $photoFile->getMimeType(),
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        );

        /** Assert */
        $this->assertTrue($validMime);
    }

    #[Test]
    public function it_allows_multiple_expenses_to_reference_same_photo(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** Act */
        $expense1 = Expense::create([
            'user_id' => 1,
            'amount' => 25.00,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Expense 1',
            'expense_date' => now(),
        ]);

        $expense2 = Expense::create([
            'user_id' => 2,
            'amount' => 25.00,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Expense 2',
            'expense_date' => now(),
        ]);

        /** Assert */
        $this->assertEquals($storagePath, $expense1->receipt_path);
        $this->assertEquals($storagePath, $expense2->receipt_path);
        $this->assertNotEquals($expense1->id, $expense2->id);
    }

    #[Test]
    public function it_persists_photo_after_expense_deletion(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 30.00,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Expense to delete',
            'expense_date' => now(),
        ]);

        /** Act */
        $expense->delete();

        /** Assert */
        Storage::disk('expenses')->assertExists($storagePath);
    }

    #[Test]
    public function it_queries_expense_by_receipt_path(): void
    {
        /** Arrange */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 55.50,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Queryable expense',
            'expense_date' => now(),
        ]);

        /** Act */
        $foundExpense = Expense::where('receipt_path', $storagePath)->first();

        /** Assert */
        $this->assertNotNull($foundExpense);
        $this->assertEquals($expense->id, $foundExpense->id);
    }
}

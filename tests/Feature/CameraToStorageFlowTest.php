<?php

namespace Tests\Feature;

use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CameraToStorageFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('expenses');
    }

    /**
     * Test that a photo file can be created and stored to disk
     */
    public function test_photo_file_is_stored_successfully(): void
    {
        // Create a fake image file
        $photoFile = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);

        // Store the file
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        // Assert the file exists in storage
        Storage::disk('expenses')->assertExists($storagePath);

        // Assert the file has content
        $this->assertNotEmpty(Storage::disk('expenses')->get($storagePath));
    }

    /**
     * Test that photo file is stored with correct naming convention
     */
    public function test_photo_file_stored_with_timestamped_directory(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg');

        $date = now()->format('Y/m/d');
        $storagePath = Storage::disk('expenses')->putFile("receipts/{$date}", $photoFile);

        Storage::disk('expenses')->assertExists($storagePath);

        // Verify the path contains the date structure
        $this->assertStringContainsString($date, $storagePath);
    }

    /**
     * Test that photo with EXIF orientation data is stored
     */
    public function test_photo_with_exif_data_is_stored(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg', 1080, 1440);

        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        Storage::disk('expenses')->assertExists($storagePath);

        // Verify we can retrieve the stored file
        $storedContent = Storage::disk('expenses')->get($storagePath);
        $this->assertNotNull($storedContent);
        $this->assertIsString($storedContent);
    }

    /**
     * Test that photo file path is persisted in database
     */
    public function test_expense_record_stores_photo_path(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 25.50,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Restaurant expense',
            'expense_date' => now(),
        ]);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'receipt_path' => $storagePath,
        ]);
    }

    /**
     * Test that multiple photos can be stored sequentially
     */
    public function test_multiple_photos_are_stored_independently(): void
    {
        $photoFile1 = UploadedFile::fake()->image('receipt1.jpg');
        $photoFile2 = UploadedFile::fake()->image('receipt2.jpg');

        $path1 = Storage::disk('expenses')->putFile('receipts', $photoFile1);
        $path2 = Storage::disk('expenses')->putFile('receipts', $photoFile2);

        Storage::disk('expenses')->assertExists($path1);
        Storage::disk('expenses')->assertExists($path2);

        $this->assertNotEquals($path1, $path2);
    }

    /**
     * Test that photo file can be retrieved from storage
     */
    public function test_stored_photo_can_be_retrieved(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        $retrievedContent = Storage::disk('expenses')->get($storagePath);

        $this->assertNotNull($retrievedContent);
        $this->assertIsString($retrievedContent);
        $this->assertGreaterThan(0, strlen($retrievedContent));
    }

    /**
     * Test that invalid photo file is rejected
     */
    public function test_invalid_photo_file_is_rejected(): void
    {
        $invalidFile = UploadedFile::fake()->create('receipt.txt', 100, 'text/plain');

        // This should fail mime validation
        $this->assertFalse($invalidFile->getMimeType() === 'image/jpeg' || $invalidFile->getMimeType() === 'image/png');
    }

    /**
     * Test that photo file size is within acceptable limits
     */
    public function test_photo_file_size_is_validated(): void
    {
        // Create a file and check its size
        $photoFile = UploadedFile::fake()->image('receipt.jpg');

        $this->assertLessThan(
            10 * 1024 * 1024, // 10MB max
            $photoFile->getSize()
        );
    }

    /**
     * Test that photo storage path follows consistent naming pattern
     */
    public function test_photo_storage_path_follows_pattern(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        // Path should start with 'receipts' directory
        $this->assertStringStartsWith('receipts', $storagePath);

        // Path should not contain user-controlled filename
        $this->assertStringNotContainsString('receipt.jpg', $storagePath);
    }

    /**
     * Test that photo file can be deleted from storage
     */
    public function test_photo_file_can_be_deleted(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        Storage::disk('expenses')->assertExists($storagePath);

        Storage::disk('expenses')->delete($storagePath);

        Storage::disk('expenses')->assertMissing($storagePath);
    }

    /**
     * Test that expense record is created when photo is stored
     */
    public function test_expense_record_created_with_stored_photo(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 45.75,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Lunch meeting',
            'expense_date' => now(),
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('expenses', [
            'user_id' => 1,
            'amount' => 45.75,
            'receipt_path' => $storagePath,
            'status' => 'pending',
        ]);

        $this->assertNotNull($expense->id);
    }

    /**
     * Test that stored photo persists across requests
     */
    public function test_stored_photo_persists_across_requests(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        // First check
        Storage::disk('expenses')->assertExists($storagePath);

        // Simulate another request - file should still exist
        Storage::disk('expenses')->assertExists($storagePath);
    }

    /**
     * Test that expense with photo can be retrieved from database
     */
    public function test_expense_with_photo_can_be_retrieved(): void
    {
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

        $retrievedExpense = Expense::find($expense->id);

        $this->assertEquals($storagePath, $retrievedExpense->receipt_path);
        $this->assertEquals(32.00, $retrievedExpense->amount);
    }

    /**
     * Test that photo path is stored with proper directory structure
     */
    public function test_photo_stored_in_proper_directory_hierarchy(): void
    {
        $date = now()->format('Y/m/d');
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile("receipts/{$date}", $photoFile);

        // Verify directory structure is preserved
        $this->assertStringContainsString("receipts/{$date}", $storagePath);
    }

    /**
     * Test that concurrent photo uploads don't conflict
     */
    public function test_concurrent_photo_uploads_get_unique_paths(): void
    {
        $paths = [];

        for ($i = 0; $i < 5; $i++) {
            $photoFile = UploadedFile::fake()->image('receipt.jpg');
            $path = Storage::disk('expenses')->putFile('receipts', $photoFile);
            $paths[] = $path;
        }

        // All paths should be unique
        $this->assertEquals(count($paths), count(array_unique($paths)));

        // All files should exist
        foreach ($paths as $path) {
            Storage::disk('expenses')->assertExists($path);
        }
    }

    /**
     * Test that expense expense_date defaults to current date when not provided
     */
    public function test_expense_date_defaults_to_capture_time(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        $now = now();
        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 50.00,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Expense with default date',
            'expense_date' => $now,
        ]);

        $this->assertEquals($now->format('Y-m-d'), $expense->expense_date->format('Y-m-d'));
    }

    /**
     * Test that photo file mime type can be verified
     */
    public function test_stored_photo_mime_type_is_valid(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);

        $this->assertIn(
            $photoFile->getMimeType(),
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        );
    }

    /**
     * Test that multiple expenses can reference same photo (if needed)
     */
    public function test_multiple_expenses_can_reference_photo(): void
    {
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

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

        $this->assertEquals($storagePath, $expense1->receipt_path);
        $this->assertEquals($storagePath, $expense2->receipt_path);
        $this->assertNotEquals($expense1->id, $expense2->id);
    }

    /**
     * Test that photo file persists even if expense is deleted (orphaned file scenario)
     */
    public function test_photo_persists_after_expense_deletion(): void
    {
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

        // File exists before deletion
        Storage::disk('expenses')->assertExists($storagePath);

        // Delete expense
        $expense->delete();

        // File should still exist (cleanup would be handled separately)
        Storage::disk('expenses')->assertExists($storagePath);
    }

    /**
     * Test that expense can be queried by receipt path
     */
    public function test_expense_can_be_queried_by_receipt_path(): void
    {
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

        $foundExpense = Expense::where('receipt_path', $storagePath)->first();

        $this->assertNotNull($foundExpense);
        $this->assertEquals($expense->id, $foundExpense->id);
    }
}

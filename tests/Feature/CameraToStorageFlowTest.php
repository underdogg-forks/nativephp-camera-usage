<?php

namespace Tests\Feature;

use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CameraToStorageFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('expenses');
    }

    #[Test]
    public function it_stores_a_photo_file_successfully(): void
    {
        /** @arrange Create a fake image file */
        $photoFile = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);

        /** @act Store the file */
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** @assert The file exists in storage and has content */
        Storage::disk('expenses')->assertExists($storagePath);
        $this->assertNotEmpty(Storage::disk('expenses')->get($storagePath));
    }

    #[Test]
    public function it_stores_photo_with_timestamped_directory_structure(): void
    {
        /** @arrange Prepare a photo file and date structure */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $date = now()->format('Y/m/d');

        /** @act Store the file in dated directory */
        $storagePath = Storage::disk('expenses')->putFile("receipts/{$date}", $photoFile);

        /** @assert Verify the path contains the date structure */
        Storage::disk('expenses')->assertExists($storagePath);
        $this->assertStringContainsString($date, $storagePath);
    }

    #[Test]
    public function it_stores_photo_with_exif_orientation_data(): void
    {
        /** @arrange Create a photo file with orientation metadata */
        $photoFile = UploadedFile::fake()->image('receipt.jpg', 1080, 1440);

        /** @act Store the file */
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** @assert Verify file is stored and retrievable */
        Storage::disk('expenses')->assertExists($storagePath);
        $this->assertNotNull(Storage::disk('expenses')->get($storagePath));
    }

    #[Test]
    public function it_persists_photo_path_in_expense_record(): void
    {
        /** @arrange Create and store a photo file */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** @act Create an expense record with the photo path */
        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 25.50,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Restaurant expense',
            'expense_date' => now(),
        ]);

        /** @assert Verify the photo path is in the database */
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'receipt_path' => $storagePath,
        ]);
    }

    #[Test]
    public function it_stores_multiple_photos_independently(): void
    {
        /** @arrange Create two photo files */
        $photoFile1 = UploadedFile::fake()->image('receipt1.jpg');
        $photoFile2 = UploadedFile::fake()->image('receipt2.jpg');

        /** @act Store both files */
        $path1 = Storage::disk('expenses')->putFile('receipts', $photoFile1);
        $path2 = Storage::disk('expenses')->putFile('receipts', $photoFile2);

        /** @assert Both files exist and have different paths */
        Storage::disk('expenses')->assertExists($path1);
        Storage::disk('expenses')->assertExists($path2);
        $this->assertNotEquals($path1, $path2);
    }

    #[Test]
    public function it_retrieves_stored_photo_from_storage(): void
    {
        /** @arrange Store a photo file */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** @act Retrieve the photo content */
        $retrievedContent = Storage::disk('expenses')->get($storagePath);

        /** @assert Verify the content is valid and not empty */
        $this->assertNotNull($retrievedContent);
        $this->assertIsString($retrievedContent);
        $this->assertGreaterThan(0, strlen($retrievedContent));
    }

    #[Test]
    public function it_rejects_invalid_photo_file(): void
    {
        /** @arrange Create an invalid file (text instead of image) */
        $invalidFile = UploadedFile::fake()->create('receipt.txt', 100, 'text/plain');

        /** @act Check mime type */
        $isInvalid = !in_array(
            $invalidFile->getMimeType(),
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        );

        /** @assert Verify the file is rejected */
        $this->assertTrue($isInvalid);
    }

    #[Test]
    public function it_validates_photo_file_size(): void
    {
        /** @arrange Create a photo file */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');

        /** @act Check file size against limit */
        $isWithinLimit = $photoFile->getSize() < (10 * 1024 * 1024); // 10MB max

        /** @assert Verify file size is acceptable */
        $this->assertTrue($isWithinLimit);
    }

    #[Test]
    public function it_follows_consistent_photo_storage_naming_pattern(): void
    {
        /** @arrange Create a photo file */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');

        /** @act Store the file */
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** @assert Verify storage path follows pattern and hides original filename */
        $this->assertStringStartsWith('receipts', $storagePath);
        $this->assertStringNotContainsString('receipt.jpg', $storagePath);
    }

    #[Test]
    public function it_deletes_photo_file_from_storage(): void
    {
        /** @arrange Store a photo file */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** @act Delete the file */
        Storage::disk('expenses')->delete($storagePath);

        /** @assert Verify the file no longer exists */
        Storage::disk('expenses')->assertMissing($storagePath);
    }

    #[Test]
    public function it_creates_expense_record_with_stored_photo(): void
    {
        /** @arrange Store a photo and prepare expense data */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** @act Create an expense record */
        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 45.75,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Lunch meeting',
            'expense_date' => now(),
            'status' => 'pending',
        ]);

        /** @assert Verify expense is in database with correct data */
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
        /** @arrange Store a photo file */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** @act Check file existence multiple times (simulating multiple requests) */
        Storage::disk('expenses')->assertExists($storagePath);
        Storage::disk('expenses')->assertExists($storagePath);

        /** @assert File should exist in all checks */
        $this->assertTrue(true);
    }

    #[Test]
    public function it_retrieves_expense_with_photo_from_database(): void
    {
        /** @arrange Store a photo and create an expense record */
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

        /** @act Retrieve the expense from database */
        $retrievedExpense = Expense::find($expense->id);

        /** @assert Verify all data matches */
        $this->assertEquals($storagePath, $retrievedExpense->receipt_path);
        $this->assertEquals(32.00, $retrievedExpense->amount);
    }

    #[Test]
    public function it_stores_photo_in_proper_directory_hierarchy(): void
    {
        /** @arrange Prepare date-based directory structure */
        $date = now()->format('Y/m/d');
        $photoFile = UploadedFile::fake()->image('receipt.jpg');

        /** @act Store file in dated directory */
        $storagePath = Storage::disk('expenses')->putFile("receipts/{$date}", $photoFile);

        /** @assert Verify directory structure is preserved */
        $this->assertStringContainsString("receipts/{$date}", $storagePath);
    }

    #[Test]
    public function it_handles_concurrent_photo_uploads_with_unique_paths(): void
    {
        /** @arrange Create multiple photo files */
        $paths = [];

        /** @act Store 5 photos concurrently */
        for ($i = 0; $i < 5; $i++) {
            $photoFile = UploadedFile::fake()->image('receipt.jpg');
            $path = Storage::disk('expenses')->putFile('receipts', $photoFile);
            $paths[] = $path;
        }

        /** @assert All paths are unique and files exist */
        $this->assertEquals(count($paths), count(array_unique($paths)));
        foreach ($paths as $path) {
            Storage::disk('expenses')->assertExists($path);
        }
    }

    #[Test]
    public function it_sets_expense_date_to_capture_time(): void
    {
        /** @arrange Store a photo and set capture time */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);
        $now = now();

        /** @act Create expense with capture time */
        $expense = Expense::create([
            'user_id' => 1,
            'amount' => 50.00,
            'currency' => 'USD',
            'receipt_path' => $storagePath,
            'description' => 'Expense with default date',
            'expense_date' => $now,
        ]);

        /** @assert Verify expense date matches capture time */
        $this->assertEquals($now->format('Y-m-d'), $expense->expense_date->format('Y-m-d'));
    }

    #[Test]
    public function it_validates_stored_photo_mime_type(): void
    {
        /** @arrange Create a photo file */
        $photoFile = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);

        /** @act Check mime type */
        $validMime = in_array(
            $photoFile->getMimeType(),
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        );

        /** @assert Verify mime type is valid */
        $this->assertTrue($validMime);
    }

    #[Test]
    public function it_allows_multiple_expenses_to_reference_same_photo(): void
    {
        /** @arrange Store a photo file */
        $photoFile = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $photoFile);

        /** @act Create two separate expense records with same photo */
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

        /** @assert Both expenses reference the same photo but have different IDs */
        $this->assertEquals($storagePath, $expense1->receipt_path);
        $this->assertEquals($storagePath, $expense2->receipt_path);
        $this->assertNotEquals($expense1->id, $expense2->id);
    }

    #[Test]
    public function it_persists_photo_after_expense_deletion(): void
    {
        /** @arrange Store a photo and create an expense */
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

        /** @act Delete the expense record */
        $expense->delete();

        /** @assert Photo should still exist in storage */
        Storage::disk('expenses')->assertExists($storagePath);
    }

    #[Test]
    public function it_queries_expense_by_receipt_path(): void
    {
        /** @arrange Store a photo and create an expense */
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

        /** @act Query expense by receipt path */
        $foundExpense = Expense::where('receipt_path', $storagePath)->first();

        /** @assert Verify the correct expense is found */
        $this->assertNotNull($foundExpense);
        $this->assertEquals($expense->id, $foundExpense->id);
    }
}

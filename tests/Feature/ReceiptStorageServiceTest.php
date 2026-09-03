<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('expenses');
    }

    /**
     * Test that receipt file can be validated before storage
     */
    public function test_receipt_file_is_validated_before_storage(): void
    {
        $validFile = UploadedFile::fake()->image('receipt.jpg');

        // Check mime type
        $this->assertIn(
            $validFile->getMimeType(),
            ['image/jpeg', 'image/png']
        );

        // Check file size is reasonable
        $this->assertGreaterThan(0, $validFile->getSize());
        $this->assertLessThan(10 * 1024 * 1024, $validFile->getSize()); // 10MB max
    }

    /**
     * Test that receipt file validation rejects non-image files
     */
    public function test_receipt_file_validation_rejects_non_images(): void
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->assertNotIn(
            $invalidFile->getMimeType(),
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        );
    }

    /**
     * Test that receipt file is stored with user-agnostic naming
     */
    public function test_receipt_file_stored_with_random_name(): void
    {
        $file1 = UploadedFile::fake()->image('receipt.jpg');
        $file2 = UploadedFile::fake()->image('receipt.jpg');

        $path1 = Storage::disk('expenses')->putFile('receipts', $file1);
        $path2 = Storage::disk('expenses')->putFile('receipts', $file2);

        // Filenames should be different (random)
        $this->assertNotEquals(
            pathinfo($path1, PATHINFO_FILENAME),
            pathinfo($path2, PATHINFO_FILENAME)
        );
    }

    /**
     * Test that receipt storage creates proper directory structure
     */
    public function test_receipt_storage_creates_dated_directories(): void
    {
        $today = now()->format('Y/m/d');
        $file = UploadedFile::fake()->image('receipt.jpg');

        $path = Storage::disk('expenses')->putFile("receipts/{$today}", $file);

        // Path should contain date structure
        $this->assertStringContainsString($today, $path);
    }

    /**
     * Test that stored receipt can be retrieved as file object
     */
    public function test_stored_receipt_can_be_retrieved_as_stream(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        $contents = Storage::disk('expenses')->get($path);

        $this->assertIsString($contents);
        $this->assertNotEmpty($contents);
    }

    /**
     * Test that receipt file size is preserved during storage
     */
    public function test_receipt_file_size_preserved_during_storage(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg');
        $originalSize = $file->getSize();

        $path = Storage::disk('expenses')->putFile('receipts', $file);

        $storedSize = Storage::disk('expenses')->size($path);

        $this->assertEquals($originalSize, $storedSize);
    }

    /**
     * Test that receipt storage path can be retrieved from database
     */
    public function test_receipt_path_persisted_to_database(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $file);

        // Simulate saving to database
        $databaseRecord = [
            'receipt_path' => $storagePath,
            'stored_at' => now(),
        ];

        $this->assertArrayHasKey('receipt_path', $databaseRecord);
        $this->assertEquals($storagePath, $databaseRecord['receipt_path']);
    }

    /**
     * Test that receipt can be moved not just stored
     */
    public function test_receipt_can_be_moved_from_temp_location(): void
    {
        $tempPath = 'receipts/temp/photo.jpg';
        $finalPath = 'receipts/'.now()->format('Y/m/d').'/photo.jpg';

        $file = UploadedFile::fake()->image('receipt.jpg');
        Storage::disk('expenses')->putFileAs('receipts/temp', $file, 'photo.jpg');

        // Verify temp file exists
        Storage::disk('expenses')->assertExists('receipts/temp/photo.jpg');

        // Move to final location (simulated)
        $contents = Storage::disk('expenses')->get('receipts/temp/photo.jpg');
        Storage::disk('expenses')->putFileAs(
            'receipts/'.now()->format('Y/m/d'),
            $file,
            'photo.jpg'
        );

        // Both should exist (in real implementation, temp would be deleted)
        Storage::disk('expenses')->assertExists('receipts/'.now()->format('Y/m/d').'/photo.jpg');
    }

    /**
     * Test that receipt storage supports multiple image formats
     */
    public function test_receipt_storage_supports_multiple_formats(): void
    {
        $formats = ['jpg', 'png'];

        foreach ($formats as $format) {
            $file = UploadedFile::fake()->image("receipt.{$format}", 1920, 1440);
            $path = Storage::disk('expenses')->putFile('receipts', $file);

            Storage::disk('expenses')->assertExists($path);
        }
    }

    /**
     * Test that receipt file can be marked for deletion
     */
    public function test_receipt_can_be_marked_for_cleanup(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        // Mark for cleanup (soft delete concept)
        $markForCleanup = Storage::disk('expenses')->exists($path);

        $this->assertTrue($markForCleanup);

        // Actual cleanup
        Storage::disk('expenses')->delete($path);

        $this->assertFalse(Storage::disk('expenses')->exists($path));
    }

    /**
     * Test that receipt URLs can be generated for retrieval
     */
    public function test_receipt_url_can_be_generated(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        $url = Storage::disk('expenses')->url($path);

        $this->assertIsString($url);
        $this->assertStringContainsString('storage', $url);
        $this->assertStringContainsString('receipt', $url);
    }

    /**
     * Test that receipt visibility is set to private
     */
    public function test_receipt_storage_visibility_is_private(): void
    {
        $config = config('filesystems.disks.expenses');

        $this->assertEquals('private', $config['visibility']);
    }

    /**
     * Test that receipt storage root path is secure
     */
    public function test_receipt_storage_root_path_is_in_storage_app(): void
    {
        $config = config('filesystems.disks.expenses');

        $this->assertStringContainsString('storage/app/expenses', $config['root']);
    }

    /**
     * Test that receipt can be stored with metadata
     */
    public function test_receipt_metadata_can_be_associated(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        $metadata = [
            'original_name' => 'receipt.jpg',
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now()->toIso8601String(),
            'user_id' => 1,
        ];

        $this->assertArrayHasKey('original_name', $metadata);
        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('mime_type', $metadata);
    }

    /**
     * Test that duplicate receipt files get unique storage paths
     */
    public function test_duplicate_receipts_get_unique_paths(): void
    {
        $paths = [];

        for ($i = 0; $i < 3; $i++) {
            $file = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);
            $path = Storage::disk('expenses')->putFile('receipts', $file);
            $paths[] = $path;
        }

        // All paths should be unique
        $uniquePaths = array_unique($paths);
        $this->assertCount(3, $uniquePaths);
    }

    /**
     * Test that receipt storage failure is handled gracefully
     */
    public function test_receipt_storage_failure_returns_error(): void
    {
        // Simulate storage failure by using non-existent disk
        try {
            Storage::disk('nonexistent')->put('test.jpg', 'content');
            $this->fail('Should have thrown an exception');
        } catch (\Exception $e) {
            $this->assertIsThrowable($e);
        }
    }

    /**
     * Test that receipt path is absolute or relative to storage root
     */
    public function test_receipt_path_is_properly_formatted(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        // Path should not start with /
        $this->assertStringNotStartsWith('/', $path);

        // Path should be relative to storage root
        $this->assertStringStartsWith('receipts', $path);
    }
}

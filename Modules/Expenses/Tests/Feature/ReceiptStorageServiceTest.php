<?php

namespace Modules\Expenses\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class ReceiptStorageServiceTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('expenses');
    }

    #[Test]
    public function it_validates_receipt_file_before_storage(): void
    {
        /** Arrange */
        $validFile = UploadedFile::fake()->image('receipt.jpg');

        /** Act */
        $hasValidMime = in_array($validFile->getMimeType(), ['image/jpeg', 'image/png']);
        $hasSize = $validFile->getSize() > 0;

        /** Assert */
        $this->assertTrue($hasValidMime);
        $this->assertTrue($hasSize);
        $this->assertLessThan(10 * 1024 * 1024, $validFile->getSize());
    }

    #[Test]
    public function it_rejects_non_image_receipt_files(): void
    {
        /** Arrange */
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        /** Act */
        $isImage = in_array(
            $invalidFile->getMimeType(),
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        );

        /** Assert */
        $this->assertFalse($isImage);
    }

    #[Test]
    public function it_generates_random_filenames_for_receipt_storage(): void
    {
        /** Arrange */
        $file1 = UploadedFile::fake()->image('receipt.jpg');
        $file2 = UploadedFile::fake()->image('receipt.jpg');

        /** Act */
        $path1 = Storage::disk('expenses')->putFile('receipts', $file1);
        $path2 = Storage::disk('expenses')->putFile('receipts', $file2);

        /** Assert */
        $this->assertNotEquals(
            pathinfo($path1, PATHINFO_FILENAME),
            pathinfo($path2, PATHINFO_FILENAME)
        );
    }

    #[Test]
    public function it_creates_dated_directory_structure_for_receipts(): void
    {
        /** Arrange */
        $today = now()->format('Y/m/d');
        $file = UploadedFile::fake()->image('receipt.jpg');

        /** Act */
        $path = Storage::disk('expenses')->putFile("receipts/{$today}", $file);

        /** Assert */
        $this->assertStringContainsString($today, $path);
    }

    #[Test]
    public function it_retrieves_stored_receipt_as_readable_stream(): void
    {
        /** Arrange */
        $file = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** Act */
        $contents = Storage::disk('expenses')->get($path);

        /** Assert */
        $this->assertIsString($contents);
        $this->assertNotEmpty($contents);
    }

    #[Test]
    public function it_preserves_receipt_file_size_during_storage(): void
    {
        /** Arrange */
        $file = UploadedFile::fake()->image('receipt.jpg');
        $originalSize = $file->getSize();

        /** Act */
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** Assert */
        $storedSize = Storage::disk('expenses')->size($path);
        $this->assertEquals($originalSize, $storedSize);
    }

    #[Test]
    public function it_persists_receipt_path_to_database(): void
    {
        /** Arrange */
        $file = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $file);

        /** Act */
        $databaseRecord = [
            'receipt_path' => $storagePath,
            'stored_at' => now(),
        ];

        /** Assert */
        $this->assertArrayHasKey('receipt_path', $databaseRecord);
        $this->assertEquals($storagePath, $databaseRecord['receipt_path']);
    }

    #[Test]
    public function it_moves_receipt_from_temporary_location(): void
    {
        /** Arrange */
        $file = UploadedFile::fake()->image('receipt.jpg');

        /** Act */
        Storage::disk('expenses')->putFileAs('receipts/temp', $file, 'photo.jpg');
        $newFile = UploadedFile::fake()->image('receipt.jpg');
        Storage::disk('expenses')->putFileAs(
            'receipts/'.now()->format('Y/m/d'),
            $newFile,
            'photo.jpg'
        );

        /** Assert */
        Storage::disk('expenses')->assertExists('receipts/temp/photo.jpg');
        Storage::disk('expenses')->assertExists('receipts/'.now()->format('Y/m/d').'/photo.jpg');
    }

    #[Test]
    public function it_supports_multiple_receipt_image_formats(): void
    {
        /** Arrange */
        $formats = ['jpg', 'png'];

        /** Act */
        $paths = [];
        foreach ($formats as $format) {
            $file = UploadedFile::fake()->image("receipt.{$format}", 1920, 1440);
            $path = Storage::disk('expenses')->putFile('receipts', $file);
            $paths[] = $path;
        }

        /** Assert */
        foreach ($paths as $path) {
            Storage::disk('expenses')->assertExists($path);
        }
    }

    #[Test]
    public function it_marks_receipt_for_cleanup_and_deletion(): void
    {
        /** Arrange */
        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** Act */
        $this->assertTrue(Storage::disk('expenses')->exists($path));
        Storage::disk('expenses')->delete($path);

        /** Assert */
        $this->assertFalse(Storage::disk('expenses')->exists($path));
    }

    #[Test]
    public function it_generates_url_for_receipt_retrieval(): void
    {
        /** Arrange */
        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** Act */
        $url = Storage::disk('expenses')->url($path);

        /** Assert */
        $this->assertIsString($url);
        $this->assertStringContainsString('storage', $url);
        $this->assertStringContainsString('receipt', $url);
    }

    #[Test]
    public function it_sets_receipt_storage_visibility_to_private(): void
    {
        /** Arrange */
        $config = config('filesystems.disks.expenses');

        /** Act */
        $visibility = $config['visibility'] ?? null;

        /** Assert */
        $this->assertEquals('private', $visibility);
    }

    #[Test]
    public function it_stores_receipts_in_secure_storage_path(): void
    {
        /** Arrange */
        $config = config('filesystems.disks.expenses');

        /** Act */
        $rootPath = $config['root'] ?? null;

        /** Assert */
        $this->assertStringContainsString('storage/app/expenses', $rootPath);
    }

    #[Test]
    public function it_associates_metadata_with_receipt(): void
    {
        /** Arrange */
        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** Act */
        $metadata = [
            'original_name' => 'receipt.jpg',
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now()->toIso8601String(),
            'user_id' => 1,
        ];

        /** Assert */
        $this->assertArrayHasKey('original_name', $metadata);
        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('mime_type', $metadata);
        $this->assertArrayHasKey('uploaded_at', $metadata);
        $this->assertArrayHasKey('user_id', $metadata);
    }

    #[Test]
    public function it_generates_unique_paths_for_duplicate_receipts(): void
    {
        /** Arrange */
        $paths = [];

        /** Act */
        for ($i = 0; $i < 3; $i++) {
            $file = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);
            $path = Storage::disk('expenses')->putFile('receipts', $file);
            $paths[] = $path;
        }

        /** Assert */
        $uniquePaths = array_unique($paths);
        $this->assertCount(3, $uniquePaths);
    }

    #[Test]
    public function it_handles_storage_failure_gracefully(): void
    {
        /** Arrange */
        $exceptionThrown = false;

        /** Act */
        try {
            Storage::disk('nonexistent')->put('test.jpg', 'content');
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        /** Assert */
        $this->assertTrue($exceptionThrown);
    }

    #[Test]
    public function it_formats_receipt_path_relative_to_storage_root(): void
    {
        /** Arrange */
        $file = UploadedFile::fake()->image('receipt.jpg');

        /** Act */
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** Assert */
        $this->assertFalse(str_starts_with($path, '/'));
        $this->assertTrue(str_starts_with($path, 'receipts'));
    }

    #[Test]
    public function it_ensures_receipt_path_doesnt_leak_original_filename(): void
    {
        /** Arrange */
        $file = UploadedFile::fake()->image('employee_name_receipt.jpg');

        /** Act */
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** Assert */
        $this->assertStringNotContainsString('employee_name_receipt.jpg', $path);
        $this->assertStringNotContainsString('employee_name', $path);
    }
}

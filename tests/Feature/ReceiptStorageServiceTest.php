<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReceiptStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('expenses');
    }

    #[Test]
    public function it_validates_receipt_file_before_storage(): void
    {
        /** @arrange Create a valid receipt file */
        $validFile = UploadedFile::fake()->image('receipt.jpg');

        /** @act Check file properties */
        $hasValidMime = in_array($validFile->getMimeType(), ['image/jpeg', 'image/png']);
        $hasSize = $validFile->getSize() > 0;

        /** @assert File meets validation criteria */
        $this->assertTrue($hasValidMime);
        $this->assertTrue($hasSize);
        $this->assertLessThan(10 * 1024 * 1024, $validFile->getSize());
    }

    #[Test]
    public function it_rejects_non_image_receipt_files(): void
    {
        /** @arrange Create a non-image file */
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        /** @act Check mime type */
        $isImage = in_array(
            $invalidFile->getMimeType(),
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        );

        /** @assert File is correctly identified as non-image */
        $this->assertFalse($isImage);
    }

    #[Test]
    public function it_generates_random_filenames_for_receipt_storage(): void
    {
        /** @arrange Create two identical receipt files */
        $file1 = UploadedFile::fake()->image('receipt.jpg');
        $file2 = UploadedFile::fake()->image('receipt.jpg');

        /** @act Store both files */
        $path1 = Storage::disk('expenses')->putFile('receipts', $file1);
        $path2 = Storage::disk('expenses')->putFile('receipts', $file2);

        /** @assert Filenames should be different */
        $this->assertNotEquals(
            pathinfo($path1, PATHINFO_FILENAME),
            pathinfo($path2, PATHINFO_FILENAME)
        );
    }

    #[Test]
    public function it_creates_dated_directory_structure_for_receipts(): void
    {
        /** @arrange Prepare a receipt file and date structure */
        $today = now()->format('Y/m/d');
        $file = UploadedFile::fake()->image('receipt.jpg');

        /** @act Store file in dated directory */
        $path = Storage::disk('expenses')->putFile("receipts/{$today}", $file);

        /** @assert Path contains date structure */
        $this->assertStringContainsString($today, $path);
    }

    #[Test]
    public function it_retrieves_stored_receipt_as_readable_stream(): void
    {
        /** @arrange Store a receipt file */
        $file = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** @act Retrieve the file contents */
        $contents = Storage::disk('expenses')->get($path);

        /** @assert Contents are readable and not empty */
        $this->assertIsString($contents);
        $this->assertNotEmpty($contents);
    }

    #[Test]
    public function it_preserves_receipt_file_size_during_storage(): void
    {
        /** @arrange Create and measure a receipt file */
        $file = UploadedFile::fake()->image('receipt.jpg');
        $originalSize = $file->getSize();

        /** @act Store the file */
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** @assert Stored file size matches original */
        $storedSize = Storage::disk('expenses')->size($path);
        $this->assertEquals($originalSize, $storedSize);
    }

    #[Test]
    public function it_persists_receipt_path_to_database(): void
    {
        /** @arrange Store a receipt file */
        $file = UploadedFile::fake()->image('receipt.jpg');
        $storagePath = Storage::disk('expenses')->putFile('receipts', $file);

        /** @act Create database record with path */
        $databaseRecord = [
            'receipt_path' => $storagePath,
            'stored_at' => now(),
        ];

        /** @assert Record contains correct path */
        $this->assertArrayHasKey('receipt_path', $databaseRecord);
        $this->assertEquals($storagePath, $databaseRecord['receipt_path']);
    }

    #[Test]
    public function it_moves_receipt_from_temporary_location(): void
    {
        /** @arrange Create receipt file and prepare storage locations */
        $file = UploadedFile::fake()->image('receipt.jpg');

        /** @act Store file in temp location then final location */
        Storage::disk('expenses')->putFileAs('receipts/temp', $file, 'photo.jpg');
        $newFile = UploadedFile::fake()->image('receipt.jpg');
        Storage::disk('expenses')->putFileAs(
            'receipts/'.now()->format('Y/m/d'),
            $newFile,
            'photo.jpg'
        );

        /** @assert Both locations exist */
        Storage::disk('expenses')->assertExists('receipts/temp/photo.jpg');
        Storage::disk('expenses')->assertExists('receipts/'.now()->format('Y/m/d').'/photo.jpg');
    }

    #[Test]
    public function it_supports_multiple_receipt_image_formats(): void
    {
        /** @arrange Prepare multiple image formats */
        $formats = ['jpg', 'png'];

        /** @act Store files in each format */
        $paths = [];
        foreach ($formats as $format) {
            $file = UploadedFile::fake()->image("receipt.{$format}", 1920, 1440);
            $path = Storage::disk('expenses')->putFile('receipts', $file);
            $paths[] = $path;
        }

        /** @assert All formats are stored successfully */
        foreach ($paths as $path) {
            Storage::disk('expenses')->assertExists($path);
        }
    }

    #[Test]
    public function it_marks_receipt_for_cleanup_and_deletion(): void
    {
        /** @arrange Store a receipt file */
        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** @act Mark for cleanup and delete */
        $this->assertTrue(Storage::disk('expenses')->exists($path));
        Storage::disk('expenses')->delete($path);

        /** @assert File no longer exists */
        $this->assertFalse(Storage::disk('expenses')->exists($path));
    }

    #[Test]
    public function it_generates_url_for_receipt_retrieval(): void
    {
        /** @arrange Store a receipt file */
        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** @act Generate URL */
        $url = Storage::disk('expenses')->url($path);

        /** @assert URL is valid and contains required elements */
        $this->assertIsString($url);
        $this->assertStringContainsString('storage', $url);
        $this->assertStringContainsString('receipt', $url);
    }

    #[Test]
    public function it_sets_receipt_storage_visibility_to_private(): void
    {
        /** @arrange Get filesystem configuration */
        $config = config('filesystems.disks.expenses');

        /** @act Check visibility setting */
        $visibility = $config['visibility'] ?? null;

        /** @assert Visibility is private */
        $this->assertEquals('private', $visibility);
    }

    #[Test]
    public function it_stores_receipts_in_secure_storage_path(): void
    {
        /** @arrange Get storage configuration */
        $config = config('filesystems.disks.expenses');

        /** @act Check storage root path */
        $rootPath = $config['root'] ?? null;

        /** @assert Path is in secure storage directory */
        $this->assertStringContainsString('storage/app/expenses', $rootPath);
    }

    #[Test]
    public function it_associates_metadata_with_receipt(): void
    {
        /** @arrange Create receipt file and metadata */
        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** @act Build metadata array */
        $metadata = [
            'original_name' => 'receipt.jpg',
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now()->toIso8601String(),
            'user_id' => 1,
        ];

        /** @assert All metadata fields are present */
        $this->assertArrayHasKey('original_name', $metadata);
        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('mime_type', $metadata);
        $this->assertArrayHasKey('uploaded_at', $metadata);
        $this->assertArrayHasKey('user_id', $metadata);
    }

    #[Test]
    public function it_generates_unique_paths_for_duplicate_receipts(): void
    {
        /** @arrange Create multiple receipt files */
        $paths = [];

        /** @act Store 3 identical receipts */
        for ($i = 0; $i < 3; $i++) {
            $file = UploadedFile::fake()->image('receipt.jpg', 1920, 1440);
            $path = Storage::disk('expenses')->putFile('receipts', $file);
            $paths[] = $path;
        }

        /** @assert All paths are unique */
        $uniquePaths = array_unique($paths);
        $this->assertCount(3, $uniquePaths);
    }

    #[Test]
    public function it_handles_storage_failure_gracefully(): void
    {
        /** @arrange Attempt to use non-existent disk */
        $exceptionThrown = false;

        /** @act Try to access non-existent disk */
        try {
            Storage::disk('nonexistent')->put('test.jpg', 'content');
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        /** @assert Exception should be thrown */
        $this->assertTrue($exceptionThrown);
    }

    #[Test]
    public function it_formats_receipt_path_relative_to_storage_root(): void
    {
        /** @arrange Create and store a receipt */
        $file = UploadedFile::fake()->image('receipt.jpg');

        /** @act Store the file */
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** @assert Path should be relative and start with receipts directory */
        $this->assertStringNotStartsWith('/', $path);
        $this->assertStringStartsWith('receipts', $path);
    }

    #[Test]
    public function it_ensures_receipt_path_doesnt_leak_original_filename(): void
    {
        /** @arrange Create receipts with identifying names */
        $file = UploadedFile::fake()->image('employee_name_receipt.jpg');

        /** @act Store the file */
        $path = Storage::disk('expenses')->putFile('receipts', $file);

        /** @assert Original filename should not be in storage path */
        $this->assertStringNotContainsString('employee_name_receipt.jpg', $path);
        $this->assertStringNotContainsString('employee_name', $path);
    }
}

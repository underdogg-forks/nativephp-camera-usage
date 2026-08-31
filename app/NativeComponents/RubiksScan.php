<?php

namespace App\NativeComponents;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Events\Camera\PhotoTaken;
use Native\Mobile\Facades\Camera;

class RubiksScan extends NativeComponent
{
    public array $scannedFaces = [];

    public ?int $activeFaceIndex = null;

    public ?string $pendingPhotoPath = null;

    public bool $isAligning = false;

    public bool $isProcessing = false;

    // pollCount removed

    public bool $showGuide = false;

    public int $guideStep = 1;

    public ?string $errorMessage = null;

    public array $scanOrder = [
        0 => ['name' => 'Top', 'desc' => 'White center'],
        1 => ['name' => 'Left', 'desc' => 'Orange center'],
        2 => ['name' => 'Front', 'desc' => 'Green center'],
        3 => ['name' => 'Right', 'desc' => 'Red center'],
        4 => ['name' => 'Bottom', 'desc' => 'Yellow center'],
        5 => ['name' => 'Back', 'desc' => 'Blue center'],
    ];

    public function mount(): void
    {
        $faces = Cache::get('rubiks_faces');
        $this->scannedFaces = is_array($faces) ? $faces : [];
    }

    public function navTitle(): string
    {
        $count = count(array_filter($this->scannedFaces));
        if ($count >= 6) {
            return 'Scan Complete';
        }

        return 'Scan Cube ('.$count.'/6)';
    }

    public function takePhoto(int $faceIndex)
    {
        $this->activeFaceIndex = $faceIndex;
        // If they are rescanning a face, we must clear it from the cache first
        $faces = Cache::get('rubiks_faces');
        if (is_array($faces) && isset($faces[$faceIndex])) {
            unset($faces[$faceIndex]);
            Cache::put('rubiks_faces', $faces);
            $this->scannedFaces = $faces;
        }

        Log::info('Launching system camera for face index: '.$faceIndex);
        Camera::getPhoto(['allowEditing' => true])->start();
    }

    public function cancelAlignment()
    {
        // Remove the face from cache since they cancelled
        if ($this->activeFaceIndex !== null) {
            $faces = Cache::get('rubiks_faces');
            if (is_array($faces) && isset($faces[$this->activeFaceIndex])) {
                unset($faces[$this->activeFaceIndex]);
                Cache::put('rubiks_faces', $faces);
                $this->scannedFaces = $faces;
            }
        }
        $this->isAligning = false;
        $this->pendingPhotoPath = null;
        $this->activeFaceIndex = null;
    }

    public function confirmAlignment()
    {
        // Keep the colors in cache, just close the results screen
        $this->isAligning = false;
        $this->pendingPhotoPath = null;
        $this->activeFaceIndex = null;
    }

    // checkCache removed because API is synchronous now

    #[On(PhotoTaken::class)]
    public function handlePhotoTaken(string $path)
    {
        Log::info('PhotoTaken event fired with path: '.$path);
        if ($this->activeFaceIndex === null) {
            Log::error('No active face index selected!');

            return;
        }

        if (file_exists($path)) {
            try {
                $orientation = 1;
                if (function_exists('exif_read_data')) {
                    $exif = @exif_read_data($path);
                    if (! empty($exif['Orientation'])) {
                        $orientation = $exif['Orientation'];
                    }
                }
                // Send the image to the host machine running Apache/Laragon since Android doesn't have Python
                $response = Http::timeout(30)->attach(
                    'image', file_get_contents($path), 'cube.jpg'
                )->post('http://192.168.1.109/Rubick/public/api/extract-colors', [
                    'orientation' => $orientation,
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    if (isset($result['colors']) && count($result['colors']) === 9) {
                        $colors = $result['colors'];

                        $allFaces = Cache::get('rubiks_faces', []);
                        $allFaces[$this->activeFaceIndex] = $colors;
                        Cache::put('rubiks_faces', $allFaces);

                        $this->scannedFaces = $allFaces;

                        // Save the debug image so we can show it to the user
                        if (isset($result['debug_image'])) {
                            // NativePHP Mobile <native:image> only supports local file paths.
                            // Save the debug image to the same directory as the original camera photo to avoid permission/storage issues.
                            $debugPath = dirname($path).'/debug_'.uniqid().'.jpg';
                            file_put_contents($debugPath, base64_decode($result['debug_image']));

                            $this->pendingPhotoPath = $debugPath;
                            $this->isAligning = true; // Show results
                        } else {
                            $this->activeFaceIndex = null;
                        }
                    } else {
                        $this->errorMessage = 'API returned invalid data format.';
                        $this->activeFaceIndex = null;
                    }
                } else {
                    $this->errorMessage = 'API Error: '.$response->status();
                    $this->activeFaceIndex = null;
                }
            } catch (\Exception $e) {
                $this->errorMessage = 'Connection Failed: '.$e->getMessage();
                $this->activeFaceIndex = null;
            }
        }
    }

    private function processImageColors(string $path)
    {
        try {
            $response = Http::timeout(30)->attach(
                'image', file_get_contents($path), 'cube.jpg'
            )->post('http://10.185.58.239/Rubick/public/api/extract-colors');

            if ($response->successful()) {
                $colors = $response->json('colors');
                if (is_array($colors) && count($colors) === 9) {
                    $allFaces = Cache::get('rubiks_faces');
                    $allFaces = is_array($allFaces) ? $allFaces : [];
                    $allFaces[$this->activeFaceIndex] = $colors;

                    Cache::put('rubiks_faces', $allFaces);
                    $this->scannedFaces = $allFaces;
                    $this->errorMessage = null;
                    Log::info('Colors extracted successfully: '.implode(',', $colors));
                } else {
                    $this->errorMessage = 'API returned invalid data format.';
                }
            } else {
                $this->errorMessage = 'API Error: '.$response->status().' '.$response->body();
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Connection Failed: '.$e->getMessage();
        }

        $this->activeFaceIndex = null;
    }

    private function mapToRubiksColor($r, $g, $b): string
    {
        $palette = [
            'U' => [255, 255, 255], // White
            'D' => [255, 213, 0],   // Yellow
            'F' => [0, 155, 72],    // Green
            'B' => [0, 69, 173],    // Blue
            'L' => [255, 89, 0],    // Orange
            'R' => [185, 0, 0],      // Red
        ];

        $closest = 'U';
        $minDist = PHP_INT_MAX;

        foreach ($palette as $face => $color) {
            $dist = pow($r - $color[0], 2) + pow($g - $color[1], 2) + pow($b - $color[2], 2);
            if ($dist < $minDist) {
                $minDist = $dist;
                $closest = $face;
            }
        }

        return $closest;
    }

    public function resetScan()
    {
        $this->scannedFaces = [];
        Cache::forget('rubiks_faces');
        $this->errorMessage = null;
    }

    public function openGuide()
    {
        $this->showGuide = true;
        $this->guideStep = 1;
    }

    public function closeGuide()
    {
        $this->showGuide = false;
    }

    public function prevGuideStep()
    {
        if ($this->guideStep > 1) {
            $this->guideStep--;
        }
    }

    public function nextGuideStep()
    {
        if ($this->guideStep < 6) {
            $this->guideStep++;
        } else {
            $this->showGuide = false;
        }
    }

    public function render(): View
    {
        return view('native.rubiks-scan');
    }
}

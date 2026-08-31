<?php

namespace App\NativeComponents;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class RubiksReview extends NativeComponent
{
    public array $faces = [];

    public ?int $editingFace = null;

    public function mount(): void
    {
        $faces = Cache::get('rubiks_faces', []);
        $this->faces = is_array($faces) ? $faces : [];
    }

    public function editFace(int $faceIndex)
    {
        $this->editingFace = $faceIndex;
    }

    public function closeEdit()
    {
        $this->editingFace = null;
    }

    public function navTitle(): string
    {
        return 'Review Colors';
    }

    public function nextFace()
    {
        if ($this->currentFaceIndex < count($this->faces) - 1) {
            $this->currentFaceIndex++;
        }
    }

    public function prevFace()
    {
        if ($this->currentFaceIndex > 0) {
            $this->currentFaceIndex--;
        }
    }

    public function cycleColor(int $faceIndex, int $index)
    {
        // Safety check to ensure the face exists, initialize if it doesn't
        if (! isset($this->faces[$faceIndex]) || ! is_array($this->faces[$faceIndex])) {
            $this->faces[$faceIndex] = array_fill(0, 9, 'U');
        }

        $colors = ['U', 'D', 'F', 'B', 'L', 'R'];
        $currentColor = $this->faces[$faceIndex][$index] ?? '?';

        $cIndex = array_search($currentColor, $colors);
        if ($cIndex === false) {
            $cIndex = -1;
        }
        $nextIndex = ($cIndex + 1) % count($colors);

        $this->faces[$faceIndex][$index] = $colors[$nextIndex];
        Cache::put('rubiks_faces', $this->faces);
    }

    public function render(): View
    {
        return view('native.rubiks-review');
    }
}

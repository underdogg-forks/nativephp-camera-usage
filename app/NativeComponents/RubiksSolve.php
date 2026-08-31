<?php

namespace App\NativeComponents;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class RubiksSolve extends NativeComponent
{
    public function navTitle(): string
    {
        return 'Solve Cube';
    }

    public function render(): View
    {
        $faces = Cache::get('rubiks_faces', []);

        // Map faces to CubeJS order: U R F D L B
        // Our scan order: 0:Top, 1:Left, 2:Front, 3:Right, 4:Bottom, 5:Back
        $safeFaces = [];
        for ($i = 0; $i < 6; $i++) {
            $safeFaces[$i] = isset($faces[$i]) && is_array($faces[$i]) && count($faces[$i]) === 9
                ? $faces[$i]
                : array_fill(0, 9, '?');
        }

        $cubeString = implode('', $safeFaces[0])  // U
            .implode('', $safeFaces[3])           // R
            .implode('', $safeFaces[2])           // F
            .implode('', $safeFaces[4])           // D
            .implode('', $safeFaces[1])           // L
            .implode('', $safeFaces[5]);          // B

        $solution = null;
        $error = null;

        // Validate
        if (strlen($cubeString) !== 54 || str_contains($cubeString, '?')) {
            $error = 'Incomplete scan! Please scan all 6 faces first.';
        } else {
            $counts = array_count_values(str_split($cubeString));
            $valid = true;
            foreach (['U', 'R', 'F', 'D', 'L', 'B'] as $face) {
                if (($counts[$face] ?? 0) !== 9) {
                    $valid = false;
                    break;
                }
            }

            if (! $valid) {
                $error = 'Invalid cube! Each color must appear exactly 9 times. Check the Review tab.';
            } else {
                // Send the cube string to the host machine running Apache/Laragon to run Node.js
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(30)->post('http://192.168.1.109/Rubick/public/api/solve', [
                        'cube' => $cubeString,
                    ]);

                    if ($response->successful()) {
                        $result = $response->json();
                        if (isset($result['solution'])) {
                            $solution = $result['solution'];
                        } else {
                            $error = 'Could not find a solution. The cube state may be physically invalid.';
                        }
                    } else {
                        $err = $response->json('error');
                        $error = $err ? "Solver Error: $err" : 'API Error: '.$response->status();
                    }
                } catch (\Exception $e) {
                    $error = 'Connection Failed: '.$e->getMessage();
                }
            }
        }

        // Parse solution into step list
        $steps = $solution ? array_filter(explode(' ', trim($solution))) : [];

        return view('native.rubiks-solve', [
            'steps' => array_values($steps),
            'solution' => $solution,
            'error' => $error,
        ]);
    }
}

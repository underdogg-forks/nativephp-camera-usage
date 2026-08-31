<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CubeController extends Controller
{
    public function index()
    {
        return view('cube.scanner');
    }

    public function scan(Request $request)
    {
        $faces = $request->input('faces');

        if (! is_array($faces)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid cube data',
            ], 422);
        }

        if (count($faces) !== 6) {
            return response()->json([
                'success' => false,
                'message' => 'Exactly six faces are required',
            ], 422);
        }

        // Validate that we have exactly 9 stickers per color overall
        // A standard Rubik's cube has 9 stickers of each of the 6 colors
        $colorCounts = [
            'W' => 0, 'Y' => 0, 'R' => 0, 'O' => 0, 'G' => 0, 'B' => 0,
        ];

        foreach ($faces as $faceData) {
            $stickers = $faceData['stickers'] ?? [];

            if (count($stickers) !== 9) {
                return response()->json([
                    'success' => false,
                    'message' => 'Each face must have exactly 9 stickers',
                ], 422);
            }

            foreach ($stickers as $color) {
                if (! isset($colorCounts[$color])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid color detected: '.$color,
                    ], 422);
                }
                $colorCounts[$color]++;
            }
        }

        foreach ($colorCounts as $color => $count) {
            if ($count !== 9) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid cube state: found $count stickers for color $color (expected 9)",
                ], 422);
            }
        }

        // If validation passes, we can proceed to solve or save the state
        // For now, we'll just return success

        return response()->json([
            'success' => true,
            'message' => 'Cube state is valid!',
            'faces' => $faces,
        ]);
    }
}

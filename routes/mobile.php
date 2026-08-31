<?php

use App\Http\Controllers\CubeController;
use App\NativeComponents\Layouts\RubiksTabsLayout;
use App\NativeComponents\RubiksReview;
use App\NativeComponents\RubiksScan;
use App\NativeComponents\RubiksSolve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::nativeGroup(RubiksTabsLayout::class, function () {
    Route::native('/', RubiksScan::class)->name('home');
    Route::native('/scan', RubiksScan::class)->name('scan');
    Route::native('/review', RubiksReview::class)->name('review');
    Route::native('/solve', RubiksSolve::class)->name('solve');
});

// Standard web route for the 3D Solver webview and 3D Guide webview
Route::get('/scan-guide', function () {
    return view('scan-guide');
})->name('scan-guide');

Route::get('/solve-3d', function () {
    $faces = Cache::get('rubiks_faces', []);

    // Map faces to CubeJS expected order: U (Top), R (Right), F (Front), D (Bottom), L (Left), B (Back)
    // Our scan order is: 0:Top, 1:Left, 2:Front, 3:Right, 4:Bottom, 5:Back

    // Ensure all 6 faces exist, fill with '?' if missing
    $safeFaces = [];
    for ($i = 0; $i < 6; $i++) {
        $safeFaces[$i] = isset($faces[$i]) && is_array($faces[$i]) && count($faces[$i]) === 9
            ? $faces[$i]
            : array_fill(0, 9, '?');
    }

    $cubeString = '';
    $cubeString .= implode('', $safeFaces[0]); // U
    $cubeString .= implode('', $safeFaces[3]); // R
    $cubeString .= implode('', $safeFaces[2]); // F
    $cubeString .= implode('', $safeFaces[4]); // D
    $cubeString .= implode('', $safeFaces[1]); // L
    $cubeString .= implode('', $safeFaces[5]); // B

    // If for some bizarre reason it's still not 54, fallback
    if (strlen($cubeString) !== 54) {
        $cubeString = 'UUUUUUUUURRRRRRRRRFFFFFFFFFDDDDDDDDDLLLLLLLLLBBBBBBBBB';
    }

    return view('solve-3d', ['cubeString' => $cubeString]);
})->name('solve-3d');

// Route to serve the Hidden WebView that does the image processing
Route::get('/process-colors', function () {
    return view('process-colors');
})->name('process-colors');

// API Route for Local Proxy color extraction
Route::post('/api/extract-colors', function (Request $request) {
    if (! $request->hasFile('image')) {
        return response()->json(['error' => 'No image uploaded'], 400);
    }

    $file = $request->file('image');
    $path = $file->getRealPath();
    $orientation = $request->input('orientation', 1);

    // Execute Python OpenCV script
    $command = escapeshellcmd('python '.base_path('cube_scanner.py')).' '.escapeshellarg($path).' '.escapeshellarg($orientation);
    $output = shell_exec($command);

    Log::info('Python OpenCV output: '.$output);

    $result = json_decode($output, true);
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], 500);
    }

    return response()->json([
        'colors' => $result['colors'] ?? [],
        'debug_image' => $result['debug_image'] ?? null,
    ]);
});

Route::post('/api/solve', function (Request $request) {
    $cubeString = $request->input('cube');
    if (! $cubeString || strlen($cubeString) !== 54) {
        return response()->json(['error' => 'Invalid cube string'], 400);
    }

    // Validate exactly 9 of each face color
    $counts = array_count_values(str_split($cubeString));
    foreach (['U', 'R', 'F', 'D', 'L', 'B'] as $face) {
        if (($counts[$face] ?? 0) !== 9) {
            return response()->json(['error' => 'Invalid cube: must have exactly 9 of each color'], 422);
        }
    }

    // Solve using cubejs via Node.js (runs on the server, not in the crashing WebView)
    $nodePath = 'C:\\Program Files\\nodejs\\node.exe';
    $script = "const Cube = require('cubejs'); Cube.initSolver(); try { const c = Cube.fromString('".addslashes($cubeString)."'); const sol = c.solve(); console.log(JSON.stringify({success:true,solution:sol})); } catch(e) { console.log(JSON.stringify({success:false,error:e.toString()})); }";
    $command = escapeshellarg($nodePath).' -e '.escapeshellarg($script);
    $output = shell_exec($command);

    Log::info('Cubejs solve output: '.$output);

    $result = json_decode(trim($output), true);
    if (! $result || ! isset($result['success'])) {
        return response()->json(['error' => 'Solver failed to run'], 500);
    }
    if (! $result['success']) {
        return response()->json(['error' => $result['error'] ?? 'Solver error'], 422);
    }

    return response()->json(['solution' => $result['solution']]);
});

// WEBRTC & ALIGNMENT SCANNER ROUTES
Route::get('/webrtc-scan', function () {
    return view('webrtc-scan');
});

Route::get('/align-scan', function () {
    return view('align-scan');
});

Route::get('/api/temp-photo', function (Request $request) {
    $path = $request->input('path');
    if ($path && file_exists($path)) {
        return response()->file($path);
    }

    return response('Not found', 404);
});

Route::post('/api/process-webrtc-scan', function (Request $request) {
    $base64 = $request->input('image');
    $faceIndex = $request->input('face');
    if (! $base64 || $faceIndex === null) {
        return response()->json(['error' => 'Invalid payload']);
    }

    // Save base64 to temp file
    $data = explode(',', $base64)[1];
    $path = sys_get_temp_dir().'/'.uniqid('webrtc_').'.jpg';
    file_put_contents($path, base64_decode($data));

    // Execute Python OpenCV script (we can pass orientation 1 since it's already cropped/upright from JS canvas)
    $command = escapeshellcmd('python '.base_path('cube_scanner.py')).' '.escapeshellarg($path).' 1';
    $output = shell_exec($command);

    Log::info('WebRTC Python OpenCV output: '.$output);

    $result = json_decode($output, true);
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], 500);
    }

    if (isset($result['colors']) && count($result['colors']) === 9) {
        $allFaces = Cache::get('rubiks_faces', []);
        $allFaces[$faceIndex] = $result['colors'];
        Cache::put('rubiks_faces', $allFaces);

        return response()->json(['colors' => $result['colors']]);
    }

    return response()->json(['error' => 'Invalid colors format'], 500);
});

// Cube Scanner Routes
Route::get('/cube-scanner', [CubeController::class, 'index']);
Route::post('/cube/scan', [CubeController::class, 'scan']);

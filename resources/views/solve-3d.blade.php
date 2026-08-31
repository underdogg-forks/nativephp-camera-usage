<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>3D Solver</title>
    <script>
        window.rubiksCubeState = "{!! $cubeString !!}";
        window.rubiksSolution = "{!! $solution ?? '' !!}";
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 m-0 overflow-hidden text-white flex items-center justify-center h-screen">
    <div id="cube-container" class="w-full h-full"></div>
    <div id="error-overlay" class="absolute inset-0 bg-black/80 flex items-center justify-center p-8 text-center text-red-500 font-bold text-xl hidden z-50"></div>

    <div class="absolute top-4 left-0 right-0 flex justify-center gap-4 z-50">
        <button id="prev-btn" class="bg-gray-800 px-4 py-2 rounded-lg font-bold disabled:opacity-50" disabled>Prev</button>
        <button id="play-btn" class="bg-blue-600 px-6 py-2 rounded-lg font-bold disabled:opacity-50" disabled>Solve</button>
        <button id="next-btn" class="bg-gray-800 px-4 py-2 rounded-lg font-bold disabled:opacity-50" disabled>Next</button>
    </div>

    <!-- Move Indicator Overlay -->
    <div class="absolute bottom-8 left-0 right-0 flex flex-col items-center justify-center z-50 pointer-events-none">
        <div id="move-badge" class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold text-2xl shadow-lg hidden"></div>
        <div id="move-desc" class="text-white mt-2 font-semibold text-lg shadow-black drop-shadow-md hidden"></div>
    </div>
</body>
</html>

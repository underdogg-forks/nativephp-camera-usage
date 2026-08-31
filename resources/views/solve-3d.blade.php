<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>3D Solver</title>
    <script>
        window.rubiksCubeState = "{{ $cubeString }}";
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 m-0 overflow-hidden text-white flex items-center justify-center h-screen">
    <div id="cube-container" class="w-full h-full"></div>
    <div id="error-overlay" class="absolute top-20 left-4 right-4 bg-red-600 text-white p-4 rounded-lg font-bold text-center hidden"></div>
    <div class="absolute top-4 left-0 right-0 flex justify-center gap-4 z-50">
        <button id="prev-btn" class="bg-gray-800 px-4 py-2 rounded-lg font-bold">Prev</button>
        <button id="play-btn" class="bg-blue-600 px-6 py-2 rounded-lg font-bold">Solve</button>
        <button id="next-btn" class="bg-gray-800 px-4 py-2 rounded-lg font-bold">Next</button>
    </div>
</body>
</html>

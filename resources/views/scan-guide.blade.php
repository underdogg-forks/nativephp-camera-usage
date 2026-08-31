<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scanning Guide</title>
    @vite(['resources/css/app.css', 'resources/js/guide-animator.js'])
</head>
<body class="bg-gray-900 m-0 overflow-hidden text-white flex flex-col h-screen font-sans">
    <div class="p-6 text-center z-10 bg-gray-800 shadow-md">
        <h2 id="guide-title" class="text-2xl font-bold mb-2 text-white">Step 1: Scan Top Face</h2>
        <p id="guide-desc" class="text-gray-300">Hold the cube so the <strong class="text-white">White center</strong> faces you directly.</p>
    </div>
    
    <div id="cube-container" class="flex-1 w-full relative">
        <!-- 3D Cube will be rendered here -->
    </div>
    
    <div class="p-6 bg-gray-800 flex justify-between items-center shadow-[0_-4px_6px_rgba(0,0,0,0.3)] z-10">
        <button id="prev-btn" class="bg-gray-700 hover:bg-gray-600 px-6 py-3 rounded-lg font-bold text-white transition disabled:opacity-50" disabled>Previous</button>
        <span id="step-counter" class="text-gray-400 font-bold">1 / 6</span>
        <button id="next-btn" class="bg-blue-600 hover:bg-blue-500 px-6 py-3 rounded-lg font-bold text-white transition shadow-lg shadow-blue-900/50">Next Step</button>
    </div>
</body>
</html>

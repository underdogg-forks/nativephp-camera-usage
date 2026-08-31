<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .grid-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70vw;
            height: 70vw;
            max-width: 300px;
            max-height: 300px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            border: 2px solid white;
            z-index: 10;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
        }
        .grid-cell {
            border: 1px solid rgba(255,255,255,0.5);
        }
    </style>
</head>
<body class="bg-black m-0 overflow-hidden text-white flex flex-col h-screen relative">
    
    <div id="error-msg" class="absolute top-10 left-0 w-full text-center text-red-500 z-50 font-bold"></div>

    <video id="camera-feed" class="w-full h-full object-cover" autoplay playsinline></video>
    
    <div class="grid-overlay" id="grid-overlay">
        <div class="grid-cell"></div><div class="grid-cell"></div><div class="grid-cell"></div>
        <div class="grid-cell"></div><div class="grid-cell"></div><div class="grid-cell"></div>
        <div class="grid-cell"></div><div class="grid-cell"></div><div class="grid-cell"></div>
    </div>

    <div class="absolute bottom-10 w-full flex justify-center z-50 gap-4">
        <button id="capture-btn" class="bg-blue-600 px-8 py-3 rounded-full font-bold shadow-lg text-xl border-4 border-white">Scan!</button>
    </div>

    <canvas id="canvas" class="hidden"></canvas>

    <script>
        const video = document.getElementById('camera-feed');
        const canvas = document.getElementById('canvas');
        const errorMsg = document.getElementById('error-msg');
        
        // Start Camera
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(err => {
                errorMsg.innerText = "Camera Access Denied by WebView.";
                console.error(err);
            });

        // Capture
        document.getElementById('capture-btn').addEventListener('click', () => {
            if (!video.videoWidth) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Extract the pixels inside the 3x3 grid!
            // Wait, we need to map the CSS grid overlay to the video coordinates
            const overlay = document.getElementById('grid-overlay');
            const rect = overlay.getBoundingClientRect();
            
            // Calculate scale between video element and actual video resolution
            const scaleX = canvas.width / video.clientWidth;
            const scaleY = canvas.height / video.clientHeight;

            // Map grid coordinates to canvas coordinates
            const gridX = rect.left * scaleX;
            const gridY = rect.top * scaleY;
            const gridW = rect.width * scaleX;
            const gridH = rect.height * scaleY;
            const cellW = gridW / 3;
            const cellH = gridH / 3;

            let colors = [];

            // We can just send the cropped image back to PHP, or extract HSL here!
            // Let's just send the cropped image base64 to PHP to reuse our OpenCV script!
            
            // Draw just the grid to a new canvas to send to backend
            const cropCanvas = document.createElement('canvas');
            cropCanvas.width = gridW;
            cropCanvas.height = gridH;
            const cropCtx = cropCanvas.getContext('2d');
            cropCtx.drawImage(canvas, gridX, gridY, gridW, gridH, 0, 0, gridW, gridH);
            
            const base64 = cropCanvas.toDataURL('image/jpeg');
            
            // Get face param from URL
            const urlParams = new URLSearchParams(window.location.search);
            const faceIdx = urlParams.get('face') || 0;
            
            // POST to backend
            fetch('/api/process-webrtc-scan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ image: base64, face: parseInt(faceIdx) })
            }).then(r => r.json()).then(data => {
                if (data.colors) {
                    errorMsg.innerText = "Success! Updating UI...";
                } else {
                    errorMsg.innerText = "Failed: " + data.error;
                }
            }).catch(e => {
                errorMsg.innerText = "API Error";
            });
        });
    </script>
</body>
</html>

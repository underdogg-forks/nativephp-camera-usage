<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Align Photo</title>
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
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7);
            pointer-events: none;
        }
        .grid-cell {
            border: 1px solid rgba(255,255,255,0.5);
        }
        #photo-container {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #photo {
            transform-origin: center;
            will-change: transform;
            cursor: grab;
            max-width: none;
            max-height: none;
        }
    </style>
</head>
<body class="bg-black m-0 overflow-hidden text-white h-screen w-screen relative">
    
    <div id="error-msg" class="absolute top-10 w-full text-center text-white z-50 font-bold bg-black/50 p-2">Drag to align the cube</div>

    <div id="photo-container">
        <img id="photo" src="" alt="Captured Photo">
    </div>
    
    <div class="grid-overlay" id="grid-overlay">
        <div class="grid-cell"></div><div class="grid-cell"></div><div class="grid-cell"></div>
        <div class="grid-cell"></div><div class="grid-cell"></div><div class="grid-cell"></div>
        <div class="grid-cell"></div><div class="grid-cell"></div><div class="grid-cell"></div>
    </div>

    <div class="absolute bottom-10 w-full flex justify-center z-50 gap-4">
        <button id="capture-btn" class="bg-blue-600 px-8 py-3 rounded-full font-bold shadow-lg text-xl border-4 border-white">Extract Colors</button>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const photoUrl = urlParams.get('photo');
        const faceIdx = urlParams.get('face') || 0;
        const errorMsg = document.getElementById('error-msg');
        
        const photo = document.getElementById('photo');
        if(photoUrl) photo.src = photoUrl;

        // --- Drag Logic ---
        let isDragging = false;
        let startX, startY;
        let translateX = 0, translateY = 0;
        let currentScale = 1;

        // Auto-scale image to fit screen initially
        photo.onload = () => {
            const containerRatio = window.innerWidth / window.innerHeight;
            const imgRatio = photo.naturalWidth / photo.naturalHeight;
            
            if(imgRatio > containerRatio) {
                currentScale = window.innerHeight / photo.naturalHeight;
            } else {
                currentScale = window.innerWidth / photo.naturalWidth;
            }
            // Zoom in slightly so they have room to pan
            currentScale *= 1.2;
            updateTransform();
        };

        function updateTransform() {
            photo.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
        }

        photo.addEventListener('mousedown', dragStart);
        photo.addEventListener('touchstart', dragStart, {passive: false});

        document.addEventListener('mousemove', drag);
        document.addEventListener('touchmove', drag, {passive: false});

        document.addEventListener('mouseup', dragEnd);
        document.addEventListener('touchend', dragEnd);

        function dragStart(e) {
            isDragging = true;
            let clientX = e.clientX || e.touches[0].clientX;
            let clientY = e.clientY || e.touches[0].clientY;
            startX = clientX - translateX;
            startY = clientY - translateY;
        }

        function drag(e) {
            if (!isDragging) return;
            e.preventDefault();
            let clientX = e.clientX || e.touches[0].clientX;
            let clientY = e.clientY || e.touches[0].clientY;
            translateX = clientX - startX;
            translateY = clientY - startY;
            updateTransform();
        }

        function dragEnd() {
            isDragging = false;
        }

        // --- Crop & Extract ---
        document.getElementById('capture-btn').addEventListener('click', () => {
            errorMsg.innerText = "Extracting...";
            
            const overlay = document.getElementById('grid-overlay');
            const rect = overlay.getBoundingClientRect();
            
            // We need to render the image to a canvas WITH its current transform, 
            // then crop the rect area.
            const canvas = document.createElement('canvas');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            const ctx = canvas.getContext('2d');
            
            // Move origin to center (where image is positioned)
            ctx.translate(canvas.width/2 + translateX, canvas.height/2 + translateY);
            ctx.scale(currentScale, currentScale);
            // Draw image centered
            ctx.drawImage(photo, -photo.naturalWidth/2, -photo.naturalHeight/2);
            
            // Now extract just the grid area
            const cropCanvas = document.createElement('canvas');
            cropCanvas.width = rect.width;
            cropCanvas.height = rect.height;
            const cropCtx = cropCanvas.getContext('2d');
            
            cropCtx.drawImage(canvas, rect.left, rect.top, rect.width, rect.height, 0, 0, rect.width, rect.height);
            
            const base64 = cropCanvas.toDataURL('image/jpeg');
            
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

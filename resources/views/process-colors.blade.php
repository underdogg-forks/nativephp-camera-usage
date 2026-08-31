<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Process Colors</title>
</head>
<body>
    @php
        $imageData = \Illuminate\Support\Facades\Cache::get('current_image_to_process');
    @endphp

    @if($imageData)
        <img id="sourceImage" src="data:image/jpeg;base64,{{ $imageData['base64'] }}" style="display:none;">
        <canvas id="canvas" style="display:none;"></canvas>
        <script>
            function mapToRubiksColor(r, g, b) {
                const palette = {
                    'U': [255, 255, 255], // White
                    'D': [255, 213, 0],   // Yellow
                    'F': [0, 155, 72],    // Green
                    'B': [0, 69, 173],    // Blue
                    'L': [255, 89, 0],    // Orange
                    'R': [185, 0, 0]      // Red
                };

                let closest = 'U';
                let minDist = Infinity;

                for (const [face, color] of Object.entries(palette)) {
                    const dist = Math.pow(r - color[0], 2) + Math.pow(g - color[1], 2) + Math.pow(b - color[2], 2);
                    if (dist < minDist) {
                        minDist = dist;
                        closest = face;
                    }
                }
                return closest;
            }

            window.onload = function() {
                const img = document.getElementById('sourceImage');
                const canvas = document.getElementById('canvas');
                const ctx = canvas.getContext('2d');

                // Draw image to canvas
                canvas.width = img.naturalWidth || 800;
                canvas.height = img.naturalHeight || 800;
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                const colors = [];
                // Sample points at 25%, 50%, 75% for 3x3 grid
                for (let y = 1; y <= 3; y++) {
                    for (let x = 1; x <= 3; x++) {
                        const sampleX = Math.floor(canvas.width * (x * 0.25));
                        const sampleY = Math.floor(canvas.height * (y * 0.25));
                        
                        const pixel = ctx.getImageData(sampleX, sampleY, 1, 1).data;
                        const faceColor = mapToRubiksColor(pixel[0], pixel[1], pixel[2]);
                        colors.push(faceColor);
                    }
                }

                // Post the colors back to the API
                fetch('/api/save-colors', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        faceIndex: {{ $imageData['faceIndex'] }},
                        colors: colors
                    })
                }).then(response => {
                    console.log('Colors saved successfully');
                }).catch(error => {
                    console.error('Error saving colors:', error);
                });
            };
        </script>
    @else
        <p>No image to process.</p>
    @endif
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rubik's Cube Scanner</title>

    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; width: 100%; height: 100%;
            background: #101010; color: white; font-family: Arial, sans-serif;
            overflow: hidden;
        }
        #scanner { position: relative; width: 100%; height: 100%; }
        
        #video, #canvas-input {
            position: absolute; width: 100%; height: 100%;
            object-fit: cover; display: none;
        }
        
        /* We display the video stream directly and overlay canvas on top */
        #video { display: block; z-index: 1; }
        
        #canvas-overlay {
            position: absolute; width: 100%; height: 100%;
            z-index: 2; pointer-events: none;
            object-fit: cover;
        }
        
        #overlay { position: absolute; inset: 0; pointer-events: none; z-index: 3; }
        
        #top {
            position: absolute; top: 25px; left: 0; width: 100%;
            text-align: center; text-shadow: 1px 1px 2px black;
        }
        #instruction { font-size: 18px; font-weight: bold; }
        #face-counter { margin-top: 8px; font-size: 14px; opacity: .8; }
        
        #bottom {
            position: absolute; bottom: 0; width: 100%; padding: 20px;
            background: linear-gradient(transparent, rgba(0,0,0,.9));
            pointer-events: auto; /* Allow clicking buttons */
        }
        
        #status { text-align: center; min-height: 25px; margin-bottom: 12px; }
        #confidence { text-align: center; font-size: 13px; margin-bottom: 10px; }
        
        #detected-colors {
            display: grid; grid-template-columns: repeat(3, 45px);
            gap: 3px; justify-content: center; margin-bottom: 15px;
        }
        
        .color {
            width: 45px; height: 45px; border: 2px solid white; border-radius: 4px;
        }
        
        button {
            width: 100%; padding: 15px; border: 0; border-radius: 8px;
            font-size: 17px; font-weight: bold; cursor: pointer;
        }
        
        #scan { background: white; color: black; }
        #accept { display: none; margin-top: 8px; background: #35c759; color: white; }
        #rescan { display: none; margin-top: 8px; background: #555; color: white; }
    </style>
</head>
<body>

<div id="scanner">
    <!-- Camera stream -->
    <video id="video" autoplay muted playsinline></video>
    
    <!-- Hidden input canvas for OpenCV processing -->
    <canvas id="canvas-input" style="display:none;"></canvas>
    
    <!-- Visible overlay canvas for drawing the detected quadrilateral -->
    <canvas id="canvas-overlay"></canvas>
    
    <!-- Hidden output canvas for perspective transform extraction -->
    <canvas id="canvas-output" style="display:none;"></canvas>

    <div id="overlay">
        <div id="top">
            <div id="instruction">Loading...</div>
            <div id="face-counter">Face 1 / 6</div>
        </div>

        <div id="bottom">
            <div id="status">Please wait for OpenCV to load...</div>
            <div id="confidence"></div>
            <div id="detected-colors"></div>

            <button id="scan">Scan Face</button>
            <button id="accept">Accept Face</button>
            <button id="rescan">Scan Again</button>
        </div>
    </div>
</div>

<!-- Load OpenCV.js -->
<script async src="https://docs.opencv.org/4.8.0/opencv.js" onload="onOpenCvReady();" type="text/javascript"></script>

<!-- Load our scanner logic -->
<script src="{{ asset('js/cube-scanner.js') }}"></script>

</body>
</html>

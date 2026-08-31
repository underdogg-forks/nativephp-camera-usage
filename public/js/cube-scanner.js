const video = document.getElementById("video");
const canvasInput = document.getElementById("canvas-input");
const canvasOverlay = document.getElementById("canvas-overlay");
const canvasOutput = document.getElementById("canvas-output"); // hidden canvas for perspective transform

const scanButton = document.getElementById("scan");
const acceptButton = document.getElementById("accept");
const rescanButton = document.getElementById("rescan");

const statusElement = document.getElementById("status");
const confidenceElement = document.getElementById("confidence");
const colorsElement = document.getElementById("detected-colors");
const instructionElement = document.getElementById("instruction");
const faceCounterElement = document.getElementById("face-counter");

let stream = null;
let isStreaming = false;
let currentFace = 0;
let scannedFaces = [];
let lastDetection = null;

// Reference colors calibrated from the center stickers of each face
let calibratedColors = {
    "W": null,
    "Y": null,
    "R": null,
    "O": null,
    "G": null,
    "B": null
};

// Fixed scanning order based on standard algorithms
// Center piece defines the face color
const faces = [
    { name: "White (Top/U)", expectedCenter: "W", code: "U" },
    { name: "Red (Right/R)", expectedCenter: "R", code: "R" },
    { name: "Green (Front/F)", expectedCenter: "G", code: "F" },
    { name: "Yellow (Bottom/D)", expectedCenter: "Y", code: "D" },
    { name: "Orange (Left/L)", expectedCenter: "O", code: "L" },
    { name: "Blue (Back/B)", expectedCenter: "B", code: "B" }
];

let src = null;
let dst = null;
let gray = null;
let blurred = null;
let edges = null;
let detectedQuad = null; // Store the 4 points of the detected cube face

// Called when OpenCV.js is ready
function onOpenCvReady() {
    statusElement.innerText = "OpenCV loaded. Starting camera...";
    startCamera();
}

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: "environment" },
                width: { ideal: 1280 },
                height: { ideal: 720 },
                frameRate: { ideal: 30 }
            },
            audio: false
        });
        
        video.srcObject = stream;
        video.play();
        
        video.addEventListener("canplay", () => {
            if (!isStreaming) {
                isStreaming = true;
                canvasInput.width = video.videoWidth;
                canvasInput.height = video.videoHeight;
                canvasOverlay.width = video.videoWidth;
                canvasOverlay.height = video.videoHeight;
                
                src = new cv.Mat(video.videoHeight, video.videoWidth, cv.CV_8UC4);
                dst = new cv.Mat(video.videoHeight, video.videoWidth, cv.CV_8UC4);
                gray = new cv.Mat(video.videoHeight, video.videoWidth, cv.CV_8UC1);
                blurred = new cv.Mat(video.videoHeight, video.videoWidth, cv.CV_8UC1);
                edges = new cv.Mat(video.videoHeight, video.videoWidth, cv.CV_8UC1);
                
                updateFaceInstruction();
                requestAnimationFrame(processVideo);
            }
        });
    } catch (error) {
        console.error(error);
        statusElement.innerText = "Camera error: " + error.message;
    }
}

function processVideo() {
    if (!isStreaming) return;
    
    // Clear overlay
    const overlayCtx = canvasOverlay.getContext("2d");
    overlayCtx.clearRect(0, 0, canvasOverlay.width, canvasOverlay.height);

    try {
        const inputCtx = canvasInput.getContext("2d");
        inputCtx.drawImage(video, 0, 0, canvasInput.width, canvasInput.height);
        src.data.set(inputCtx.getImageData(0, 0, canvasInput.width, canvasInput.height).data);
        
        // Find largest quadrilateral
        const quad = findCubeFace(src);
        
        if (quad) {
            detectedQuad = quad;
            // Draw quad
            overlayCtx.strokeStyle = "#00FF00";
            overlayCtx.lineWidth = 3;
            overlayCtx.beginPath();
            overlayCtx.moveTo(quad[0].x, quad[0].y);
            overlayCtx.lineTo(quad[1].x, quad[1].y);
            overlayCtx.lineTo(quad[2].x, quad[2].y);
            overlayCtx.lineTo(quad[3].x, quad[3].y);
            overlayCtx.closePath();
            overlayCtx.stroke();
            
            // Draw center point for visual feedback
            const cx = (quad[0].x + quad[1].x + quad[2].x + quad[3].x) / 4;
            const cy = (quad[0].y + quad[1].y + quad[2].y + quad[3].y) / 4;
            overlayCtx.fillStyle = "#FF0000";
            overlayCtx.beginPath();
            overlayCtx.arc(cx, cy, 5, 0, 2 * Math.PI);
            overlayCtx.fill();
        } else {
            detectedQuad = null;
        }
    } catch (err) {
        console.error("OpenCV processing error:", err);
    }
    
    requestAnimationFrame(processVideo);
}

function findCubeFace(src) {
    cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY, 0);
    cv.GaussianBlur(gray, blurred, new cv.Size(5, 5), 0, 0, cv.BORDER_DEFAULT);
    cv.Canny(blurred, edges, 50, 150);
    
    let contours = new cv.MatVector();
    let hierarchy = new cv.Mat();
    
    cv.findContours(edges, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);
    
    let largestArea = 0;
    let bestQuad = null;
    
    for (let i = 0; i < contours.size(); ++i) {
        let cnt = contours.get(i);
        let area = cv.contourArea(cnt);
        
        // Ignore small contours
        if (area < 10000) continue;
        
        let perimeter = cv.arcLength(cnt, true);
        let approx = new cv.Mat();
        cv.approxPolyDP(cnt, approx, 0.04 * perimeter, true);
        
        if (approx.rows === 4 && area > largestArea) {
            // Check if it's convex
            if (cv.isContourConvex(approx)) {
                largestArea = area;
                bestQuad = [
                    {x: approx.data32S[0], y: approx.data32S[1]},
                    {x: approx.data32S[2], y: approx.data32S[3]},
                    {x: approx.data32S[4], y: approx.data32S[5]},
                    {x: approx.data32S[6], y: approx.data32S[7]}
                ];
            }
        }
        approx.delete();
    }
    
    contours.delete();
    hierarchy.delete();
    
    if (bestQuad) {
        return orderPoints(bestQuad);
    }
    return null;
}

// Order points: top-left, top-right, bottom-right, bottom-left
function orderPoints(pts) {
    pts.sort((a, b) => a.x - b.x);
    let leftMost = [pts[0], pts[1]];
    let rightMost = [pts[2], pts[3]];
    
    leftMost.sort((a, b) => a.y - b.y);
    let tl = leftMost[0];
    let bl = leftMost[1];
    
    rightMost.sort((a, b) => a.y - b.y);
    let tr = rightMost[0];
    let br = rightMost[1];
    
    return [tl, tr, br, bl];
}

scanButton.addEventListener("click", () => {
    if (!detectedQuad) {
        statusElement.innerText = "No cube detected. Align it clearly.";
        return;
    }
    
    statusElement.innerText = "Analyzing colors...";
    
    // Perspective transform
    const flatSize = 300;
    let srcCoords = cv.matFromArray(4, 1, cv.CV_32FC2, [
        detectedQuad[0].x, detectedQuad[0].y,
        detectedQuad[1].x, detectedQuad[1].y,
        detectedQuad[2].x, detectedQuad[2].y,
        detectedQuad[3].x, detectedQuad[3].y
    ]);
    
    let dstCoords = cv.matFromArray(4, 1, cv.CV_32FC2, [
        0, 0,
        flatSize, 0,
        flatSize, flatSize,
        0, flatSize
    ]);
    
    let dsize = new cv.Size(flatSize, flatSize);
    let warped = new cv.Mat();
    let M = cv.getPerspectiveTransform(srcCoords, dstCoords);
    cv.warpPerspective(src, warped, M, dsize, cv.INTER_LINEAR, cv.BORDER_CONSTANT, new cv.Scalar());
    
    // Draw to hidden canvas to read pixel data easily
    canvasOutput.width = flatSize;
    canvasOutput.height = flatSize;
    cv.imshow(canvasOutput, warped);
    
    srcCoords.delete();
    dstCoords.delete();
    M.delete();
    warped.delete();
    
    const ctx = canvasOutput.getContext("2d", { willReadFrequently: true });
    
    const stickers = [];
    const cellSize = flatSize / 3;
    
    for (let row = 0; row < 3; row++) {
        for (let col = 0; col < 3; col++) {
            stickers.push(sampleSticker(ctx, col * cellSize, row * cellSize, cellSize));
        }
    }
    
    const detection = classifyStickerColors(stickers);
    lastDetection = detection;
    displayDetection(detection);
});

function sampleSticker(ctx, startX, startY, cellSize) {
    // Sample central 35%
    const sampleSize = cellSize * 0.35;
    const x = startX + (cellSize - sampleSize) / 2;
    const y = startY + (cellSize - sampleSize) / 2;
    
    const imgData = ctx.getImageData(x, y, sampleSize, sampleSize);
    return analyzePixels(imgData.data);
}

function analyzePixels(data) {
    const pixels = [];
    for (let i = 0; i < data.length; i += 4) {
        const r = data[i], g = data[i+1], b = data[i+2];
        if (r < 20 && g < 20 && b < 20) continue; // ignore very dark
        pixels.push({r, g, b});
    }
    
    if (pixels.length === 0) return { rgb: {r:0, g:0, b:0}, lab: {l:0, a:0, b:0} };
    
    let r = 0, g = 0, b = 0;
    pixels.forEach(p => { r += p.r; g += p.g; b += p.b; });
    
    const avgR = Math.round(r / pixels.length);
    const avgG = Math.round(g / pixels.length);
    const avgB = Math.round(b / pixels.length);
    
    return {
        rgb: { r: avgR, g: avgG, b: avgB },
        lab: rgbToLab(avgR, avgG, avgB)
    };
}

// Basic Hue-based classification for initial center calibration
function classifyInitial(rgb) {
    const hsv = rgbToHSV(rgb.r, rgb.g, rgb.b);
    const h = hsv.h, s = hsv.s, v = hsv.v;
    
    if (s < 0.25 && v > 0.5) return "W";
    if (h >= 35 && h <= 75 && s > 0.3) return "Y";
    if (h >= 10 && h < 35 && s > 0.3) return "O";
    if ((h < 10 || h >= 345) && s > 0.3) return "R";
    if (h >= 70 && h < 165 && s > 0.3) return "G";
    if (h >= 165 && h < 280 && s > 0.3) return "B";
    return "?";
}

function classifyStickerColors(stickers) {
    const colors = [];
    const centerSticker = stickers[4]; // index 4 is the center (row 1, col 1 in 3x3)
    
    // If this is the center, we calibrate it if not already calibrated
    const faceDef = faces[currentFace];
    let detectedCenterColor = faceDef.expectedCenter;
    
    // Update calibration for the expected center color
    calibratedColors[detectedCenterColor] = centerSticker.lab;
    
    let confidenceSum = 0;
    
    stickers.forEach((sticker, index) => {
        let bestColor = "?";
        let bestDist = Infinity;
        
        // If we have calibrated colors, use Lab distance
        // Fallback to initial classification if not enough colors are calibrated
        let numCalibrated = Object.values(calibratedColors).filter(c => c !== null).length;
        
        if (numCalibrated > 2) { // Need at least some calibrated colors to do reliable distance
            for (const [colCode, refLab] of Object.entries(calibratedColors)) {
                if (!refLab) continue;
                let d = deltaE(sticker.lab, refLab);
                if (d < bestDist) {
                    bestDist = d;
                    bestColor = colCode;
                }
            }
        } else {
            // Fallback
            bestColor = classifyInitial(sticker.rgb);
        }
        
        // For the center sticker, force it to be the expected center
        if (index === 4) {
            bestColor = detectedCenterColor;
            bestDist = 0;
        }
        
        let conf = bestDist < 15 ? 0.95 : (bestDist < 25 ? 0.8 : 0.5); // heuristic confidence based on Lab dist
        if (numCalibrated <= 2) conf = 0.8;
        if (index === 4) conf = 1.0;
        
        colors.push({
            color: bestColor,
            rgb: sticker.rgb,
            confidence: conf
        });
        confidenceSum += conf;
    });
    
    return {
        stickers: colors,
        confidence: confidenceSum / stickers.length
    };
}

function displayDetection(detection) {
    colorsElement.innerHTML = "";
    
    detection.stickers.forEach(sticker => {
        const box = document.createElement("div");
        box.className = "color";
        box.style.background = cssColor(sticker.color);
        box.title = `${sticker.color} RGB(${sticker.rgb.r}, ${sticker.rgb.g}, ${sticker.rgb.b})`;
        colorsElement.appendChild(box);
    });
    
    const percentage = Math.round(detection.confidence * 100);
    confidenceElement.innerText = `Confidence: ${percentage}%`;
    
    const unknown = detection.stickers.filter(x => x.color === "?").length;
    
    if (unknown > 0 || percentage < 70) {
        statusElement.innerText = "Some colors are uncertain. Please rescan.";
        rescanButton.style.display = "block";
        acceptButton.style.display = "none";
    } else {
        statusElement.innerText = "Face detected successfully!";
        acceptButton.style.display = "block";
        rescanButton.style.display = "block";
    }
}

acceptButton.addEventListener("click", () => {
    if (!lastDetection) return;
    
    scannedFaces[currentFace] = {
        face: faces[currentFace].code,
        stickers: lastDetection.stickers.map(s => s.color)
    };
    
    currentFace++;
    
    if (currentFace >= 6) {
        finishScanning();
        return;
    }
    
    lastDetection = null;
    colorsElement.innerHTML = "";
    confidenceElement.innerText = "";
    acceptButton.style.display = "none";
    rescanButton.style.display = "none";
    
    updateFaceInstruction();
});

rescanButton.addEventListener("click", () => {
    lastDetection = null;
    colorsElement.innerHTML = "";
    confidenceElement.innerText = "";
    acceptButton.style.display = "none";
    rescanButton.style.display = "none";
    statusElement.innerText = "Ready to scan again";
});

function updateFaceInstruction() {
    const face = faces[currentFace];
    faceCounterElement.innerText = `Face ${currentFace + 1} / 6`;
    instructionElement.innerHTML = `Scan the <b style="color:${cssColor(face.expectedCenter)}">${face.name}</b> face<br><small>Make sure the center sticker is ${face.name.split(' ')[0]}</small>`;
    statusElement.innerText = "Position the cube inside the frame";
}

function finishScanning() {
    statusElement.innerText = "All 6 faces scanned!";
    instructionElement.innerText = "Processing...";
    
    fetch("/cube/scan", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
        },
        body: JSON.stringify({ faces: scannedFaces })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            instructionElement.innerText = "Scan Complete and Validated!";
            // Redirect to solver or show success
            console.log("Laravel response:", data);
        } else {
            instructionElement.innerText = "Error: " + data.message;
        }
    })
    .catch(err => console.error(err));
}

function cssColor(color) {
    switch (color) {
        case "W": return "#ffffff";
        case "Y": return "#ffd900";
        case "R": return "#e60000";
        case "O": return "#ff7b00";
        case "G": return "#00a83b";
        case "B": return "#0066ff";
        default: return "#555";
    }
}

// Math utils for Color conversion
function rgbToHSV(r, g, b) {
    r /= 255; g /= 255; b /= 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b);
    const d = max - min;
    let h = 0;
    if (d !== 0) {
        if (max === r) h = 60 * (((g - b) / d) % 6);
        else if (max === g) h = 60 * ((b - r) / d + 2);
        else h = 60 * ((r - g) / d + 4);
    }
    if (h < 0) h += 360;
    const s = max === 0 ? 0 : d / max;
    return { h, s, v: max };
}

function rgbToLab(r, g, b) {
    let _r = r / 255.0, _g = g / 255.0, _b = b / 255.0;
    _r = _r > 0.04045 ? Math.pow((_r + 0.055) / 1.055, 2.4) : _r / 12.92;
    _g = _g > 0.04045 ? Math.pow((_g + 0.055) / 1.055, 2.4) : _g / 12.92;
    _b = _b > 0.04045 ? Math.pow((_b + 0.055) / 1.055, 2.4) : _b / 12.92;
    
    let x = (_r * 0.4124 + _g * 0.3576 + _b * 0.1805) / 0.95047;
    let y = (_r * 0.2126 + _g * 0.7152 + _b * 0.0722) / 1.00000;
    let z = (_r * 0.0193 + _g * 0.1192 + _b * 0.9505) / 1.08883;
    
    x = x > 0.008856 ? Math.pow(x, 1/3) : (7.787 * x) + 16/116;
    y = y > 0.008856 ? Math.pow(y, 1/3) : (7.787 * y) + 16/116;
    z = z > 0.008856 ? Math.pow(z, 1/3) : (7.787 * z) + 16/116;
    
    return {
        l: (116 * y) - 16,
        a: 500 * (x - y),
        b: 200 * (y - z)
    };
}

function deltaE(lab1, lab2) {
    const dl = lab1.l - lab2.l;
    const da = lab1.a - lab2.a;
    const db = lab1.b - lab2.b;
    return Math.sqrt(dl * dl + da * da + db * db);
}

import sys
import cv2
import numpy as np
import json
import base64

def get_base64_image(image):
    _, buffer = cv2.imencode('.jpg', image, [int(cv2.IMWRITE_JPEG_QUALITY), 80])
    return base64.b64encode(buffer).decode('utf-8')

def map_color_hsv(b, g, r):
    # Convert BGR to HSV
    hsv = cv2.cvtColor(np.uint8([[[b, g, r]]]), cv2.COLOR_BGR2HSV)[0][0]
    h, s, v = hsv[0], hsv[1], hsv[2]
    
    # White is usually low saturation and high value
    # Increased saturation threshold to 60 to account for warm room lighting!
    if s < 60 and v > 120: return 'U' # White
    if v < 60: return 'U' # Assume dark is also white/shadow?
    
    if h < 10 or h > 165: return 'R' # Red
    elif h < 25: return 'L' # Orange
    elif h < 45: return 'D' # Yellow
    elif h < 90: return 'F' # Green
    elif 90 <= h < 140:
        return 'B' # Blue
        
    return 'U' # Fallback

def order_points(pts):
    rect = np.zeros((4, 2), dtype="float32")
    s = pts.sum(axis=1)
    rect[0] = pts[np.argmin(s)] # Top-Left
    rect[2] = pts[np.argmax(s)] # Bottom-Right

    diff = np.diff(pts, axis=1)
    rect[1] = pts[np.argmin(diff)] # Top-Right
    rect[3] = pts[np.argmax(diff)] # Bottom-Left
    return rect

def process_image(image_path, orientation=1):
    img = cv2.imread(image_path)
    if img is None:
        print(json.dumps({'error': 'Failed to load image'}))
        return

    # EXIF Orientation Fix
    if orientation == 3: img = cv2.rotate(img, cv2.ROTATE_180)
    elif orientation == 6: img = cv2.rotate(img, cv2.ROTATE_90_CLOCKWISE)
    elif orientation == 8: img = cv2.rotate(img, cv2.ROTATE_90_COUNTERCLOCKWISE)

    # Resize image to a maximum dimension of 800 for faster processing and UI scaling
    max_dim = 800
    h, w = img.shape[:2]
    if max(h, w) > max_dim:
        scale = max_dim / float(max(h, w))
        img = cv2.resize(img, (int(w * scale), int(h * scale)))

    # We do NOT pad with huge borders anymore. Working directly on the raw image.
    height, width = img.shape[:2]
    
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (7, 7), 0)
    edged = cv2.Canny(blurred, 30, 150)
    
    # Dilate/Erode to close gaps in edges
    kernel = np.ones((5,5), np.uint8)
    edged = cv2.dilate(edged, kernel, iterations=1)
    edged = cv2.erode(edged, kernel, iterations=1)

    contours, _ = cv2.findContours(edged, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    largest_area = 0
    best_quad = None
    
    # 1. Find the largest convex quadrilateral (the cube face)
    for c in contours:
        area = cv2.contourArea(c)
        if area < (width * height * 0.05): # Ignore if smaller than 5% of image
            continue
            
        peri = cv2.arcLength(c, True)
        approx = cv2.approxPolyDP(c, 0.05 * peri, True)
        
        if len(approx) == 4 and cv2.isContourConvex(approx):
            if area > largest_area:
                largest_area = area
                best_quad = approx

    colors = []
    debug_img = img.copy()
    sampled_points = []
    
    if best_quad is not None:
        # 2. Perspective transform
        pts = best_quad.reshape(4, 2)
        rect = order_points(pts)
        (tl, tr, br, bl) = rect
        
        widthA = np.sqrt(((br[0] - bl[0]) ** 2) + ((br[1] - bl[1]) ** 2))
        widthB = np.sqrt(((tr[0] - tl[0]) ** 2) + ((tr[1] - tl[1]) ** 2))
        maxWidth = max(int(widthA), int(widthB))
        
        heightA = np.sqrt(((tr[0] - br[0]) ** 2) + ((tr[1] - br[1]) ** 2))
        heightB = np.sqrt(((tl[0] - bl[0]) ** 2) + ((tl[1] - bl[1]) ** 2))
        maxHeight = max(int(heightA), int(heightB))
        
        side = max(maxWidth, maxHeight)
        
        dst = np.array([
            [0, 0],
            [side - 1, 0],
            [side - 1, side - 1],
            [0, side - 1]
        ], dtype="float32")
        
        M = cv2.getPerspectiveTransform(rect, dst)
        warped = cv2.warpPerspective(img, M, (side, side))
        M_inv = cv2.getPerspectiveTransform(dst, rect) # To map points back
        
        # Draw the detected cube boundary on the debug image
        cv2.drawContours(debug_img, [best_quad], -1, (0, 255, 0), 4)
        
        # 3. Sample 3x3 grid from the perfectly flattened cube face
        tile_size = side / 3.0
        
        for row in range(3):
            for col in range(3):
                cx = int((col + 0.5) * tile_size)
                cy = int((row + 0.5) * tile_size)
                
                # Sample the center 20% of the tile
                patch_size = int(tile_size * 0.20)
                y1, y2 = max(0, cy - patch_size), min(side, cy + patch_size)
                x1, x2 = max(0, cx - patch_size), min(side, cx + patch_size)
                
                patch = warped[y1:y2, x1:x2]
                
                # Remove dark pixels (shadows or black borders)
                pixels = patch.reshape(-1, 3)
                brightness = np.sum(pixels, axis=1)
                valid_pixels = pixels[brightness > 60]
                
                if len(valid_pixels) > 0:
                    median_color = np.median(valid_pixels, axis=0)
                else:
                    median_color = np.median(pixels, axis=0)
                    
                b, g, r = median_color
                colors.append(map_color_hsv(b, g, r))
                
                # Map the sample coordinate back to the original image for debugging
                orig_pt = np.array([[[cx, cy]]], dtype="float32")
                orig_pt_trans = cv2.perspectiveTransform(orig_pt, M_inv)
                ox, oy = orig_pt_trans[0][0]
                
                sampled_points.append((int(ox), int(oy)))
    else:
        # Fallback to center 60% of the image if no quad is detected
        # (Android sometimes ignores allowEditing, so we must assume the image might be full size)
        face_w = min(width, height) * 0.6
        face_h = face_w
        face_x = int((width - face_w) / 2)
        face_y = int((height - face_h) / 2)

        margin = face_w * 0.05
        face_x += margin; face_y += margin
        face_w -= 2 * margin; face_h -= 2 * margin

        tile_w = face_w / 3.0
        tile_h = face_h / 3.0
        
        cv2.rectangle(debug_img, (int(face_x), int(face_y)), (int(face_x + face_w), int(face_y + face_h)), (0, 0, 255), 4)
        
        for row in range(3):
            for col in range(3):
                cx = int(face_x + (col + 0.5) * tile_w)
                cy = int(face_y + (row + 0.5) * tile_h)
                sampled_points.append((cx, cy))
                
                patch_size = int(tile_w * 0.2)
                y1 = max(0, cy - patch_size)
                y2 = min(height, cy + patch_size)
                x1 = max(0, cx - patch_size)
                x2 = min(width, cx + patch_size)
                
                patch = img[y1:y2, x1:x2]
                if patch.size == 0:
                    colors.append('U')
                    continue
                    
                pixels = patch.reshape(-1, 3)
                brightness = np.sum(pixels, axis=1)
                valid_pixels = pixels[brightness > 60]
                if len(valid_pixels) > 0:
                    median_color = np.median(valid_pixels, axis=0)
                else:
                    median_color = np.median(pixels, axis=0)
                    
                b, g, r = median_color
                colors.append(map_color_hsv(b, g, r))

    # Draw visual feedback on the debug image
    for idx, point in enumerate(sampled_points):
        cv2.circle(debug_img, tuple(point), 15, (255, 255, 255), 3)
        cv2.circle(debug_img, tuple(point), 4, (0, 0, 0), -1)
        cv2.putText(debug_img, colors[idx], (point[0] - 10, point[1] - 25), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 255, 255), 4)
        cv2.putText(debug_img, colors[idx], (point[0] - 10, point[1] - 25), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 0), 2)

    print(json.dumps({
        "colors": colors,
        "debug_image": get_base64_image(debug_img)
    }))

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No image path provided'}))
        sys.exit(1)
        
    orientation = int(sys.argv[2]) if len(sys.argv) > 2 else 1
    process_image(sys.argv[1], orientation)

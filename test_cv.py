import cv2
import numpy as np

def test_contour():
    path = "C:/Users/JontyRulz/.gemini/antigravity-ide/brain/a1589636-3cbe-470f-80ea-062ea80ab12b/.user_uploaded/media_1788019081312.jpg"
    img = cv2.imread(path)
    
    # The image might be rotated. For this test, we just process it as is.
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)
    edged = cv2.Canny(blurred, 30, 150)
    
    contours, _ = cv2.findContours(edged.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    contours = sorted(contours, key=cv2.contourArea, reverse=True)
    
    found_cube = False
    for c in contours:
        peri = cv2.arcLength(c, True)
        approx = cv2.approxPolyDP(c, 0.05 * peri, True)
        
        if len(approx) == 4:
            area = cv2.contourArea(c)
            if area > 10000: # Must be reasonably large
                print(f"Found cube boundary! Area: {area}")
                found_cube = True
                break
                
    if not found_cube:
        print("Could not find a 4-point contour large enough.")

test_contour()

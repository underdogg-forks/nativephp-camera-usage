# NativePHP Mobile Camera Integration: Complete Workflow Research Prompt

## Overview

This document describes the complete end-to-end workflow of how NativePHP (a Laravel-based native mobile framework) integrates with native device cameras to capture, process, and persist images. The use case explored here is scanning physical documents (receipts) for optical character recognition (OCR) and automated data extraction, with the goal of storing results in InvoicePlane.

This prompt is designed for comprehensive research using the Greenfield method, challenging assumptions at each step and drilling deep into implementation details.

---

## 1. THE NativePHP BUILD AND DEPLOYMENT PIPELINE

### 1.1 What is NativePHP?

**Questions to research:**
- Is NativePHP a wrapper around a WebView, or does it provide true native UI components?
- How does NativePHP differ from frameworks like React Native or Flutter?
- What is the relationship between Laravel (a PHP web framework) and NativePHP?
- Does NativePHP generate native Android/iOS apps from a Laravel codebase, or does it run Laravel as a service inside a native shell?
- What platforms does NativePHP support? (Android, iOS, Windows, macOS, Linux)
- How does hot-reload work during development?

**Research targets:**
- NativePHP official documentation on architecture
- The distinction between "native components" and "webviews" in NativePHP
- How the development server communicates with the native app
- The role of the Artisan CLI commands (`php artisan native:install`, `php artisan native:run`)

### 1.2 Build Process for Android

**Questions to research:**
- What are the step-by-step build stages? (PHP compilation → Android APK/AAB generation)
- How does NativePHP invoke Gradle? What is the relationship between Laravel and Gradle?
- Does NativePHP auto-generate AndroidManifest.xml files, or do developers configure them manually?
- What happens to PHP code during the build? Is it compiled to bytecode, embedded as a runtime, or executed on a server?
- How are Laravel routes and views bundled into the app?
- Where does the `config/nativephp.php` configuration get applied? (Android SDK versions, permissions, build flags)
- What is the purpose of the `7-Zip` location configuration?
- How are environment variables (`.env`) handled during the build? Are they stripped for production?

**Research targets:**
- The Gradle build system's role in NativePHP
- AndroidManifest.xml generation and camera permission declaration
- The compilation/bundling of Blade templates into the native app
- The cleanup of sensitive environment keys (AWS_*, GITHUB_*, DB_PASSWORD, etc.)
- ProGuard/R8 obfuscation settings and their impact on app size

### 1.3 Platform-Specific Camera Permission Handling

**Questions to research:**
- How does NativePHP request camera permissions at runtime?
- On Android 6.0+, which uses runtime permissions, how does NativePHP handle the permission request flow?
- On iOS, how does NativePHP map the `camera` permission to the Info.plist `NSCameraUsageDescription` string?
- What happens if the user denies camera permission after installation?
- Can the app gracefully degrade if permissions are denied?
- How does the permission system work in `config/nativephp.php`? (The `permissions` array)
- Are custom permission strings translated to platform-specific explanations?

**Research targets:**
- Android Runtime Permissions API (requestPermissions, checkSelfPermission)
- iOS Info.plist privacy strings
- NativePHP's permission bridging mechanism
- The flow when `Camera::getPhoto()` is called without permissions granted

---

## 2. THE CAMERA CAPTURE FLOW

### 2.1 Native Component Architecture

**Questions to research:**
- What is a "NativeComponent" in NativePHP? How does it differ from a standard Laravel controller?
- How do native components communicate state changes to the UI? (Event system, callbacks, reactive properties)
- What lifecycle methods do native components have? (mount, render, unmount, destroy)
- How does the `@On` decorator work? Is it event binding? How are events dispatched?
- When a photo is taken, what event is fired and who receives it?
- Can native components persist state across app restarts, or only during the session?

**Research targets:**
- `NativeComponent` base class in NativePHP
- The `PhotoTaken` event class and its payload (What data does it contain? Path? Image data? Metadata?)
- The attribute binding system (`#[On(PhotoTaken::class)]`)
- State management in `RubiksScan.php` (public properties are reactive)

### 2.2 Triggering the Camera

**Questions to research:**
- What does `Camera::getPhoto(['allowEditing' => true])` do under the hood?
- Does `allowEditing` mean a crop/edit UI is shown before returning the photo?
- Where is the photo saved? Temporary storage? App's private directory? Device camera roll?
- Does `->start()` block execution or trigger an async flow?
- What is the return value of `Camera::getPhoto()->start()`? Does it return immediately or wait?
- Can multiple photos be captured in sequence, or must they be processed one at a time?
- On Android, does NativePHP use the native Camera intent, or does it launch a custom camera UI?

**Research targets:**
- `Camera` facade in NativePHP
- The native Android intent flow (Intent.ACTION_IMAGE_CAPTURE)
- iOS UIImagePickerController or camera APIs used
- File storage permissions and paths (Android context.getFilesDir(), iOS Documents folder, etc.)

### 2.3 Photo Location and Accessibility

**Questions to research:**
- In the `handlePhotoTaken()` method, the `$path` parameter is a file system path. On which file system?
- Can the path be a network file? Or is it always a local file?
- On Android, where are photos stored? `/data/data/app/cache/`? External storage? MediaStore?
- Who owns the file? Can other apps access it, or is it sandboxed to the app?
- What file format is returned? (JPEG, PNG, RAW)
- Is the photo automatically deleted after processing, or does the developer need to clean it up?
- Can the app read EXIF metadata from the photo path?

**Research targets:**
- Android file storage best practices (internal cache, scoped storage API)
- iOS file system sandbox
- EXIF data extraction in PHP (exif_read_data function)
- File lifecycle management in NativePHP apps

---

## 3. IMAGE TRANSMISSION TO BACKEND PROCESSING

### 3.1 Network Architecture

**Questions to research:**
- The current implementation sends the photo to `http://192.168.1.109/Rubick/public/api/extract-colors`. Why is this a hardcoded IP instead of localhost or a domain?
- If this is a local network IP, does that mean the backend is NOT on the same device as the mobile app?
- How does the Android device know how to reach `192.168.1.109`? (WiFi? mDNS broadcast?)
- What happens if the device loses network connectivity?
- Is this architecture intended for production, or is it a development-only workaround?
- Should the backend (Python OpenCV) run on-device, on a dev machine, or on a cloud server?

**Research targets:**
- Network topologies for NativePHP apps (local dev server, remote server, hybrid)
- mDNS/Bonjour for service discovery
- The `server` configuration in `config/nativephp.php` (HTTP port 3000, WebSocket port 8081)
- Whether the dev server can be accessed from mobile devices

### 3.2 HTTP Request Mechanics

**Questions to research:**
- The code uses `Http::attach('image', file_get_contents($path), 'cube.jpg')`. How does this work?
- Is this a multipart/form-data POST request?
- What is the `file_get_contents()` doing? Is it loading the entire photo into memory, or streaming it?
- If a photo is 5-10 MB (typical high-res smartphone cameras), is loading it entirely into memory safe?
- What is the timeout set to? (30 seconds) Is this long enough for slow networks?
- How does the app handle HTTP 404, 500, or timeout errors?
- Can the request be resumed if interrupted?

**Research targets:**
- Laravel HTTP client (Guzzle-based)
- multipart/form-data encoding
- Memory implications of file_get_contents() for large files
- Retry logic and exponential backoff for failed requests
- EXIF orientation data and its purpose (why is orientation passed to the backend?)

### 3.3 The Role of Orientation Data

**Questions to research:**
- Why does the app extract EXIF orientation and send it to the backend?
- What is EXIF orientation? (Values 1-8, representing rotations/flips)
- Does the photo file itself need to be rotated, or is orientation just metadata?
- On Android, do camera photos always have orientation metadata, or is it sometimes missing?
- In the Python backend, how are the orientation values used? (cv2.rotate with ROTATE_90_CLOCKWISE, etc.)
- What happens if orientation is 1 (normal) vs. 6 (90° clockwise)?
- Why might this matter for cube/receipt scanning?

**Research targets:**
- EXIF orientation tag (Tag 0x0112)
- How camera apps save orientation metadata
- Android's exif_read_data in PHP
- OpenCV image rotation functions

---

## 4. BACKEND IMAGE PROCESSING (PYTHON + OPENCV)

### 4.1 The Python Processing Pipeline

**Questions to research:**
- The Python script (`python/cube_scanner.py`) is called via shell_exec(). Why not use a Python library directly?
- How does the PHP backend invoke the Python script? (operating system shell?)
- What happens if Python or OpenCV isn't installed?
- Can this be done asynchronously, or does the PHP request block until the Python process completes?
- What is the performance profile? (Time to process a typical photo)
- Does the Python process persist between calls, or is it spawned fresh each time?
- Can the Python script handle concurrent requests, or will they bottleneck?

**Research targets:**
- `shell_exec()` behavior and security implications
- Process management in PHP
- Python subprocess communication
- OpenCV initialization overhead
- Scaling considerations for multi-user scenarios

### 4.2 Edge Detection and Quadrilateral Finding

**Questions to research:**
- The script uses `cv2.Canny()` for edge detection. What does this do?
- What are the parameters (30, 150) in `cv2.Canny(blurred, 30, 150)`?
- How does morphological operations (dilate/erode) improve edge detection?
- The script looks for "the largest convex quadrilateral" (the cube/receipt face). Why quadrilateral?
- How does `cv2.approxPolyDP()` approximate the contour to a polygon?
- What happens if the photo contains multiple quadrilaterals (e.g., background objects)?
- How does the script ensure it finds the cube face and not a book, poster, or other rectangular object?
- What minimum area threshold is used? (width * height * 0.05 = 5% of image)

**Research targets:**
- Canny edge detection algorithm and parameters
- Morphological operations in image processing
- Contour detection and convex hull analysis
- Polygon approximation and the Ramer-Douglas-Peucker algorithm
- How to distinguish cube faces from other rectangles in the scene

### 4.3 Perspective Transform (Birds-Eye View)

**Questions to research:**
- The script applies a perspective transform to create a birds-eye view of the cube face. Why?
- How does `cv2.getPerspectiveTransform()` work? What is the mathematical principle?
- The script determines the output size as `side = max(maxWidth, maxHeight)`. Why not fix it to 300x300?
- If a cube face is photographed at an angle, does the perspective transform correct the angle to frontal?
- After transformation, is the cube face guaranteed to be perfectly aligned for 3x3 grid sampling?
- What precision is lost in the transformation? (Blurring? Aliasing?)
- How is the inverse transform used to map sample coordinates back to the original image?

**Research targets:**
- Homography and perspective transformation in computer vision
- Four-point perspective correction
- How to verify the output is square and properly oriented
- Interpolation methods in image warping

### 4.4 Color Sampling and HSV Mapping

**Questions to research:**
- Why does the script sample the center 20% of each 3x3 tile instead of the entire tile?
- What is HSV color space? How does it differ from RGB?
- The `map_color_hsv()` function maps BGR to HSV, then classifies colors into Rubik's cube faces (U=White, R=Red, F=Green, etc.). How accurate is this?
- What are the HSV ranges for each color? (e.g., Red is 0-10 or 165-180 hue)
- Why is saturation threshold set to 60? (Mentioned in comment: "to account for warm room lighting")
- How does lighting affect color detection? (Fluorescent vs. daylight vs. tungsten)
- What if the cube has stickers that are worn or misaligned?
- How does the script handle reflections or shadows on the cube?

**Research targets:**
- HSV color space: Hue, Saturation, Value/Brightness
- RGB to HSV conversion formula
- Lighting robustness in color detection
- Median filtering for noise reduction
- How different lighting conditions affect hue and saturation

### 4.5 Fallback Mechanism

**Questions to research:**
- If no quadrilateral is detected (no clear cube face in the image), the script falls back to sampling the center 60% of the image. How robust is this fallback?
- Does this fallback ever produce usable results, or is it just a graceful failure mode?
- Should the app inform the user that edge detection failed?

**Research targets:**
- Graceful degradation in computer vision
- User feedback for failed detections
- Confidence scores for detection results

### 4.6 Debug Image Generation

**Questions to research:**
- The script returns a base64-encoded debug image showing the detected cube boundary, sampled points, and colors. How is this used?
- The debug image is displayed to the user in the "Review" screen. Why is this important for UX?
- Does the user ever need to manually adjust the detected colors after seeing the debug image?
- How does the base64 encoding affect performance? (Overhead of encoding/decoding)
- Is the debug image stored permanently or discarded after review?

**Research targets:**
- Base64 encoding/decoding overhead
- Visual feedback in mobile UX for computer vision results
- How debug output helps users verify OCR results

---

## 5. DATA FLOW: PHOTOS TO LARAVEL ROUTES

### 5.1 The API Route for Color Extraction

**Questions to research:**
- The route `/api/extract-colors` receives an image upload and returns JSON with colors. Is this stateless?
- How does the app distinguish between different faces if it's processing them one at a time?
- The route receives `orientation` as a form parameter. Is this always reliable from exif_read_data?
- What happens if two photos are uploaded simultaneously? (Race condition? Concurrent processing?)
- How are the colors stored? (In PHP Cache? In-memory? Persistent?)
- The cache key is `'rubiks_faces'`. Is this global for all users, or per-session?

**Research targets:**
- Laravel Cache facade and drivers (file, Redis, database, array)
- Session vs. global caching in multi-user apps
- Race conditions in concurrent image processing
- Request/response lifecycle for file uploads

### 5.2 The Solve API Route

**Questions to research:**
- The `/api/solve` route receives a 54-character cube string and invokes Node.js cubejs to solve it.
- Why Node.js? Why not PHP? Why not Python?
- The code invokes node.exe directly via shell_exec(). What are the security implications?
- The command is: `node.exe -e "const Cube = require('cubejs'); ..."`
- Is cubejs a npm package? How is it installed and discovered by shell_exec()?
- What does "solve" mean? Does it compute the sequence of moves to solve the cube?
- How long does a solve take? (Milliseconds? Seconds?)
- Can the solution be verified before returning it to the app?

**Research targets:**
- cubejs npm package: purpose, algorithm, performance
- Shell command injection risks
- Node.js subprocess vs. persistent server
- Verification of cube solutions (is a solution always valid?)
- Time complexity of the Rubik's cube solver

### 5.3 The 3D Solver WebView Route

**Questions to research:**
- The `/solve-3d` route loads a view with a 3D interactive cube. Is this a native component or a WebView?
- The cube string is passed to a JavaScript file (presumably using Three.js). How is the data passed?
- Can the user interact with the 3D cube? (Rotate, animate?)
- Does the 3D solver display the solution moves step-by-step?
- What is the relationship between the Rubik's cube solver (backend computation) and the 3D visualizer (frontend animation)?

**Research targets:**
- WebView in NativePHP (is it Chromium-based? System WebKit?)
- Three.js for 3D visualization
- Data passing from PHP to JavaScript in WebViews
- Animation and interactivity in WebViews

---

## 6. PHOTO PERSISTENCE AND STORAGE

### 6.1 Where Do Photos End Up?

**Questions to research:**
- After a photo is processed, is it deleted immediately, or stored somewhere?
- The app caches color data in `Cache::put('rubiks_faces', $allFaces)`. Is the photo itself cached?
- If the user closes the app without solving the cube, is the cached color data lost?
- Is there a way to retrieve a photo for manual review later?
- For receipt scanning use case, where would scanned receipt images be stored for InvoicePlane integration?
- Should photos be stored locally (on-device) or uploaded to a server?
- What are the privacy/compliance implications of storing photos? (GDPR, sensitive financial documents)

**Research targets:**
- Laravel Cache TTL and storage mechanisms
- Android scoped storage API for persistent file storage
- iOS file system sandbox for persistent storage
- Privacy best practices for financial document handling

### 6.2 Photo Lifecycle Management

**Questions to research:**
- In the current implementation, temporary photos are created by the native camera. Who deletes them?
- Are photos deleted after processing, or do they accumulate and consume storage?
- For a production app with thousands of users, how much storage would be needed for temporary photos?
- Should there be a cleanup job (Laravel scheduled task) to delete old temporary files?
- How can a developer debug the app if photos are auto-deleted? (Should there be a debug mode?)

**Research targets:**
- Laravel scheduled tasks and cleanup jobs
- File storage quotas in Android/iOS apps
- Temporary file management best practices

---

## 7. NETWORK AND ERROR HANDLING

### 7.1 Failure Modes

**Questions to research:**
- What happens if the backend HTTP request fails (timeout, 500 error, connection refused)?
- The current error handling just sets `$this->errorMessage = '...'`. Does this display to the user?
- Can the user retry after an error? Or must they restart the scan from scratch?
- What if the network connection is slow? (30-second timeout)
- Can uploads be paused/resumed?
- How does the app behave if the user is on a mobile data connection (high latency, intermittent)?
- Should there be a queue system for processing photos offline?

**Research targets:**
- Retry logic and exponential backoff
- Offline-first architectures in mobile apps
- Job queues (Laravel Queue for async processing)
- User feedback and error messages

### 7.2 Security Considerations

**Questions to research:**
- Is the hardcoded IP `192.168.1.109` a security concern? (Reveals internal network topology)
- Should there be API authentication between mobile app and backend? (API keys, OAuth)
- Are photos transmitted over HTTPS? (No HTTPS in the hardcoded HTTP URL)
- Could a man-in-the-middle intercept photos or manipulation color detection results?
- Should there be request signing/verification?
- Is there rate limiting to prevent abuse?

**Research targets:**
- HTTPS/TLS for mobile app backends
- API authentication mechanisms
- Rate limiting and DDoS protection
- Secure communication in local networks

---

## 8. SCALING TO RECEIPT SCANNING + INVOICEPLANE

### 8.1 Adapting for Receipt Scanning

**Questions to research:**
- How would the current Rubik's cube scanning architecture be adapted for receipt scanning?
- Receipt scanning requires OCR (Optical Character Recognition), not just color detection. How would this work?
- Should OCR happen on-device (using a library like Tesseract) or on a backend server?
- After OCR, how would invoice data (line items, totals, dates) be extracted from the raw text?
- Should this extraction happen with regex, NLP, or a dedicated invoice parsing service?
- How would the data be validated? (Is this a real invoice? Are amounts reasonable?)

**Research targets:**
- Tesseract OCR: on-device vs. server-side
- Invoice parsing libraries and services
- Data validation for financial documents
- Performance implications of OCR processing

### 8.2 InvoicePlane Integration

**Questions to research:**
- What is InvoicePlane? (A self-hosted invoicing application)
- What API does InvoicePlane expose for programmatic invoice creation?
- Can invoices be created via REST API, or must they be entered through the UI?
- What data format does InvoicePlane expect? (JSON? Form data? Database records?)
- Should the NativePHP app directly call InvoicePlane's API, or should it go through an intermediate backend?
- How are user/vendor/customer relationships modeled in InvoicePlane?
- Should receipts be stored as attachments to invoices in InvoicePlane?

**Research targets:**
- InvoicePlane architecture and API
- Invoice data models and relationships
- File attachment handling in invoicing systems
- Multi-user and account management in InvoicePlane

### 8.3 Data Pipeline: Receipt → OCR → Invoice

**Questions to research:**
- What is the end-to-end data flow?
  - User scans receipt with NativePHP app
  - Receipt image is transmitted to backend
  - Backend runs OCR (Tesseract/cloud service)
  - OCR output is parsed to extract invoice data
  - Invoice data is validated and formatted
  - Invoice is created in InvoicePlane via API
  - User is notified of success/failure
- At which step should the user review and confirm data?
- Should OCR results be manually reviewable before sending to InvoicePlane?
- How are errors handled? (OCR failed? Parsing failed? Invoice creation failed?)
- Should there be a queue of pending invoices for manual review?

**Research targets:**
- OCR accuracy and confidence scores
- Error recovery and manual correction workflows
- Data validation and business logic
- User feedback and notifications

### 8.4 Multi-User and Account Management

**Questions to research:**
- How does the NativePHP app handle multiple users? (User login/authentication)
- Should each user have their own account in the backend?
- How are scanning privileges controlled? (Can all users scan, or only admins?)
- How are invoices associated with users? (Who scanned the receipt?)
- Should there be audit trails for receipts/invoices?
- How are multi-company scenarios handled?

**Research targets:**
- User authentication and authorization in NativePHP apps
- Role-based access control (RBAC)
- Audit logging for financial transactions
- Multi-tenancy in Laravel applications

---

## 9. DEVELOPMENT AND DEPLOYMENT WORKFLOW

### 9.1 Local Development Setup

**Questions to research:**
- To run the current app locally, what tools must be installed?
  - PHP 8.1+
  - Composer
  - Node.js + npm
  - Python 3.x
  - Android Studio + Emulator
  - Optional: Laragon/XAMPP for local Apache server
- How do developers debug the app? (Console logs? Breakpoints? Remote debugging?)
- Is there a way to simulate network failures or slow connections?
- How do developers verify permissions are working? (Grant/deny camera permission)
- Can the app run on physical devices, or only emulators?

**Research targets:**
- NativePHP development requirements and setup
- Android Emulator and ADB (Android Debug Bridge)
- Remote debugging of mobile apps
- Network simulation and throttling tools

### 9.2 Hot Reload and Development Server

**Questions to research:**
- How does NativePHP's hot-reload work? (Detects file changes, refreshes app UI)
- Which files trigger a reload? (PHP, Blade templates, JavaScript, CSS)
- How does the communication between the development machine and mobile device work? (WebSocket?)
- Can developers use a web browser to debug the app during development?
- What is the role of mDNS/Bonjour in the development workflow?

**Research targets:**
- File watchers and change detection
- WebSocket communication for live updates
- Browser DevTools for debugging WebViews
- mDNS service discovery

### 9.3 Testing and CI/CD

**Questions to research:**
- How can the app be tested? (Unit tests? Integration tests? E2E tests?)
- Can tests be run in CI/CD pipelines (GitHub Actions, etc.)?
- What is the cost of building and testing Android APKs?
- Should there be separate test builds for development and production?
- How are environment variables managed in CI/CD? (Secrets management)
- Can the app be automatically deployed to Google Play Store?

**Research targets:**
- Laravel testing frameworks (PHPUnit, Pest)
- Android testing frameworks (Espresso, Robolectric)
- CI/CD pipelines for mobile apps
- App signing and release management

---

## 10. PERFORMANCE AND OPTIMIZATION

### 10.1 Photo Processing Performance

**Questions to research:**
- How long does it take to process a photo from capture to color detection?
- Is the process fast enough for a smooth user experience? (Immediate feedback vs. waiting)
- Are there bottlenecks? (Image transmission? Python subprocess startup? OpenCV processing?)
- Can processing be parallelized? (Process multiple faces concurrently)
- How does photo resolution affect processing time? (High-res photos = slower processing?)
- Are there optimizations available? (GPU acceleration? Caching? Pre-computed models?)

**Research targets:**
- Performance profiling of image processing pipelines
- Benchmarking OpenCV operations
- Memory and CPU usage on mobile devices
- GPU acceleration with CUDA/OpenGL

### 10.2 Network Optimization

**Questions to research:**
- How large are typical photo files? (1-10 MB for modern smartphones?)
- How much bandwidth is consumed per photo upload?
- Can photo compression reduce file size without losing quality for color detection?
- Is the 30-second timeout sufficient for slow networks?
- Should there be a progress indicator for uploads?
- Can photos be compressed differently based on network speed?

**Research targets:**
- Image compression algorithms (JPEG quality settings, WebP, etc.)
- Adaptive bitrate/quality selection
- Progress indicators and user feedback
- Network bandwidth optimization

### 10.3 Battery and Heat Considerations

**Questions to research:**
- Does camera usage drain battery significantly?
- Does processing images (especially Python + OpenCV) consume a lot of CPU and generate heat?
- Should there be idle timeouts to save battery?
- Are there power-saving modes to disable OCR/processing?
- How long can a user continuously scan receipts before needing to charge?

**Research targets:**
- Mobile device power consumption profiling
- Battery optimization techniques
- Thermal management
- User expectations for battery drain

---

## 11. SECURITY AND PRIVACY DEEP DIVE

### 11.1 Photo Data Security

**Questions to research:**
- Are photos encrypted at rest? (On device storage)
- Are photos encrypted in transit? (HTTPS? Encryption layer?)
- Can photos be permanently deleted on user request? (GDPR right to be forgotten)
- Are photos stored in logs or backups?
- Should there be a local encryption key derived from the user's password?
- What happens if the device is stolen? Can an attacker access stored photos?

**Research targets:**
- At-rest encryption on mobile devices
- In-transit encryption with HTTPS and TLS
- Key management and key derivation
- Device security and full-disk encryption

### 11.2 API Security

**Questions to research:**
- Is the API endpoint authenticated? (Any API key or token needed?)
- Can an attacker upload arbitrary files to the API?
- Is there rate limiting to prevent resource exhaustion?
- Can an attacker trigger expensive operations (OCR, solving) repeatedly?
- Should there be request signing to prevent tampering?
- Are there SQL injection risks? (Database queries in the backend)
- Are there XXE (XML External Entity) injection risks?

**Research targets:**
- OWASP Top 10: injection, authentication, access control
- API rate limiting and DDoS prevention
- Input validation and sanitization
- Request signing and integrity verification

### 11.3 OCR and Data Extraction Security

**Questions to research:**
- If using a third-party OCR service (e.g., Google Cloud Vision), is data sent in the clear?
- Should there be data minimization? (Only send crop of relevant area, not full photo)
- Are OCR results logged or cached?
- Can OCR results be reversed to reconstruct original images?
- Should sensitive fields (amounts, dates) be masked before logging?
- Are there compliance concerns with cloud OCR services? (HIPAA, PCI-DSS, GDPR)

**Research targets:**
- Third-party OCR service security
- Data minimization in computer vision
- Privacy-preserving machine learning
- Compliance and regulatory requirements

---

## 12. USER EXPERIENCE AND WORKFLOW

### 12.1 Scanning Workflow

**Questions to research:**
- What is the ideal user flow for receipt scanning?
  - Open app → Camera → Capture receipt → Review OCR results → Confirm → Submit
- At which steps can the user review or correct data?
- Should users be able to capture multiple receipts in one session?
- Should there be a "batch mode" for scanning multiple receipts quickly?
- How are scanned receipts grouped? (By date? By vendor? By category?)
- Can users add notes or metadata to receipts?
- Should there be a "undo" option if a receipt is scanned incorrectly?

**Research targets:**
- Mobile UX best practices for data capture
- Form validation and error handling in mobile apps
- Batch processing workflows
- Metadata and tagging systems

### 12.2 Feedback and Guidance

**Questions to research:**
- Should the app provide real-time guidance while capturing? ("Hold steady", "Move closer", "Better lighting")
- After OCR, should the app display confidence scores for extracted data?
- Should users be able to manually edit OCR results?
- Should there be a "tutorial" mode for first-time users?
- How should errors be communicated? (Dismissible alerts? Persistent errors?)
- Should there be a "success" confirmation when receipt is submitted?

**Research targets:**
- UX patterns for camera apps
- Real-time feedback and guidance
- Confidence visualization
- Error messaging and recovery

### 12.3 Accessibility

**Questions to research:**
- Is the app accessible to users with visual impairments? (Screen reader support)
- Is the app accessible to users with mobility impairments? (One-handed operation)
- Are colors chosen with colorblind users in mind?
- Is text large enough to read? (Font sizes)
- Are there alternative input methods? (Voice commands? Gesture controls?)
- Is the app localized for different languages?

**Research targets:**
- WCAG accessibility guidelines
- Mobile accessibility best practices
- Inclusive design principles

---

## 13. ALTERNATIVE ARCHITECTURES AND APPROACHES

### 13.1 On-Device Processing

**Questions to research:**
- Could OCR, image processing, and invoice parsing happen entirely on the device?
- Are there mobile OCR libraries? (Tesseract for Android/iOS?)
- What are the pros/cons of on-device vs. server-side processing?
- How does on-device processing impact battery, heat, and storage?
- Is on-device processing more private/secure? (No data leaves device)
- What is the accuracy difference between on-device and cloud OCR?

**Research targets:**
- Tesseract OCR for Android/iOS
- On-device machine learning models (TensorFlow Lite, Core ML)
- Privacy-first architectures
- Performance trade-offs

### 13.2 Queue-Based Processing

**Questions to research:**
- Instead of processing immediately, could photos be queued for batch processing?
- Would queuing improve performance (no need to wait for processing)?
- Would queuing improve reliability (retry failed processing)?
- Would queuing enable background processing (process while user does other things)?
- What is the UX like when processing is delayed? (How long do users wait?)
- Should there be progress notifications?

**Research targets:**
- Job queues in Laravel (Redis Queue, Database Queue)
- Async task processing
- Background jobs and scheduling
- Webhook notifications for async completion

### 13.3 Hybrid Approaches

**Questions to research:**
- Could OCR happen on-device, while invoice parsing happens on the server?
- Could a lightweight OCR (e.g., simple text detection) happen on-device, with full OCR on the server as a fallback?
- Could the app cache models locally to reduce network traffic?
- Could processing be distributed across multiple devices (e.g., peer-to-peer)?

**Research targets:**
- Hybrid client-server architectures
- Model compression and caching
- Distributed processing and P2P networks

---

## 14. TESTING AND VERIFICATION STRATEGIES

### 14.1 Image Processing Testing

**Questions to research:**
- How can the color detection algorithm be tested?
- What test cases are needed? (Different lighting conditions, cube angles, reflections)
- Should there be a benchmark set of "golden" photos with known results?
- How is accuracy measured? (Precision, recall, F1 score)
- Should there be regression testing to prevent performance degradation?
- How can edge cases be identified? (Worst-case scenarios)

**Research targets:**
- Computer vision testing frameworks
- Benchmark datasets for image processing
- Metrics for evaluating detection accuracy
- Continuous integration for vision pipelines

### 14.2 OCR Testing

**Questions to research:**
- How can OCR accuracy be measured?
- What is the expected accuracy for printed vs. handwritten text?
- Should there be different models for different receipt formats?
- How are edge cases tested? (Faded text, stamps, signatures)
- Should there be human review of OCR results?
- How is OCR quality tracked over time?

**Research targets:**
- OCR accuracy metrics (WER, CER)
- Tesseract training and fine-tuning
- Handwriting recognition
- OCR quality monitoring

### 14.3 Integration Testing

**Questions to research:**
- How can the end-to-end flow be tested? (Capture → Process → Extract → InvoicePlane)
- Should tests use real photos or synthetic/mock data?
- How can network failures be simulated?
- Should there be staging/sandbox environment for testing against InvoicePlane?
- How are permission scenarios tested? (Camera permission denied, storage permission denied)
- Should there be load testing? (How many concurrent users can the system handle?)

**Research targets:**
- End-to-end testing frameworks for mobile apps
- Test data generation and mocking
- Load testing and stress testing
- Staging environments and sandbox APIs

---

## 15. RESEARCH DRILLING QUESTIONS

### Deep-Dive Prompts for Further Investigation

These questions are designed to be asked of Claude or other AI assistants to explore each area more deeply:

1. **On NativePHP Architecture:**
   - "Explain in detail how NativePHP compiles PHP code into a native Android APK. What happens to the PHP source code?"
   - "How does NativePHP handle state management between native components and the server?"
   - "What are the performance implications of embedding a PHP runtime in an Android app?"

2. **On Camera Integration:**
   - "Describe the complete Android Intent flow when Camera::getPhoto() is called. What broadcasts occur? What permissions are checked?"
   - "How does iOS handle camera access differently from Android?"
   - "What is the maximum recommended photo resolution for mobile devices, and how does this affect processing time?"

3. **On Image Processing:**
   - "Explain the mathematical principles behind perspective transformation. How does it correct angle distortion?"
   - "How would you improve the Canny edge detection parameters for low-light conditions?"
   - "What HSV color ranges would you use for detecting worn Rubik's cube stickers, and how would you handle lighting variations?"

4. **On Receipt Scanning:**
   - "Design a complete receipt scanning pipeline from capture to InvoicePlane integration. What are the critical points of failure?"
   - "How would you validate OCR results to detect hallucinations or incorrect readings?"
   - "What data quality checks would you implement before creating an invoice in InvoicePlane?"

5. **On Scaling:**
   - "How would you architect this system to handle 1,000 concurrent users scanning receipts?"
   - "What are the infrastructure costs for OCR processing at scale (1M receipts/month)?"
   - "How would you handle geographic distribution of processing services?"

6. **On Security:**
   - "Design a threat model for a receipt scanning app. What are the most critical attack surfaces?"
   - "How would you implement end-to-end encryption for sensitive financial data?"
   - "What compliance requirements apply to storing receipt images? (GDPR, CCPA, HIPAA, PCI-DSS)"

7. **On Optimization:**
   - "Profile the current Rubik's cube scanning app. Where are the bottlenecks?"
   - "How would you optimize photo compression while maintaining OCR quality?"
   - "Design a caching strategy for OCR results to reduce API calls."

8. **On User Experience:**
   - "Describe the ideal user journey for capturing and submitting a receipt. What are the pain points?"
   - "How would you provide real-time feedback to guide users to take better photos?"
   - "Design a system for users to manually correct OCR errors and provide feedback for model improvement."

---

## 16. SUMMARY AND RESEARCH METHODOLOGY

This document outlines the complete NativePHP camera workflow from build to deployment to use case extension. To research each area deeply:

1. **Start with Architecture:** Understand the big picture before diving into details.
2. **Challenge Assumptions:** Question why each design decision was made. Are there better alternatives?
3. **Identify Bottlenecks:** Where are the performance, security, or reliability risks?
4. **Test Edge Cases:** How does the system behave under stress, poor connectivity, or adversarial conditions?
5. **Consider Scale:** How would this system perform if 10x-100x more users or data were added?
6. **Prioritize User Experience:** Ultimately, does the workflow serve the user's needs?

This prompt can be used to guide comprehensive research using Claude or other AI assistants, ensuring thorough exploration of the NativePHP camera integration ecosystem.

---

## 17. RELEVANT CODE REFERENCES

**In this repository:**
- `config/nativephp.php` - NativePHP configuration and permissions
- `app/NativeComponents/RubiksScan.php` - Camera capture and photo handling
- `routes/mobile.php` - API routes for image processing and solving
- `python/cube_scanner.py` - OpenCV image processing pipeline
- `resources/views/native/rubiks-scan.blade.php` - Native UI for camera

**External Resources:**
- [NativePHP Documentation](https://nativephp.com)
- [Laravel Documentation](https://laravel.com/docs)
- [OpenCV Documentation](https://docs.opencv.org/)
- [Android Developer Documentation](https://developer.android.com)
- [iOS Developer Documentation](https://developer.apple.com/ios)


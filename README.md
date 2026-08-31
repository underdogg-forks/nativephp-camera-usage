# Rubik's Cube NativePHP Solver

A fully native Android application built with [NativePHP Mobile](https://nativephp.com) that allows you to scan a physical Rubik's Cube using your device's camera, validates the scan, calculates the optimal solution, and displays an interactive step-by-step 3D animation to help you solve it.

## Features

- **Native Camera Scanning**: Use your phone's camera to scan all 6 faces of a Rubik's Cube.
- **Python OpenCV Integration**: Automatically extracts grid colors from the camera feed using Python OpenCV.
- **Manual Review**: A native grid UI allowing you to manually correct any colors that were scanned incorrectly due to lighting.
- **Advanced Solving Algorithm**: Connects to a local Node.js backend using `cubejs` to instantly compute the shortest solution.
- **Interactive 3D Solver**: An embedded WebView featuring a fully interactive 3D Rubik's Cube (powered by Three.js) that demonstrates the exact moves needed to solve your cube step-by-step.

## System Requirements

To run this project locally, you will need the following installed on your development machine:

1. **PHP 8.1+ & Composer**
2. **Node.js & NPM** (Required for the `cubejs` solver and compiling frontend assets)
3. **Python 3.x** (Required for the OpenCV camera scanner script)
4. **Android Studio** (With an Android Emulator set up, or a physical Android device connected via ADB)

## Setup Instructions

**1. Clone the repository**
```bash
git clone <repo-url>
cd Rubick
```

**2. Install PHP Dependencies**
```bash
composer install
```

**3. Install NPM Dependencies & Build Assets**
```bash
npm install
npm run build
```

**4. Install Python Dependencies**
The backend uses a Python script (`cube_scanner.py`) to process the images. You must install OpenCV and NumPy:
```bash
pip install opencv-python numpy
```

**5. Initialize NativePHP**
```bash
php artisan native:install
```

**6. Run the Application**
Launch the Android app via NativePHP:
```bash
php artisan native:run --watch
```
*(Ensure your Android Emulator is running before executing this command).*

## Architecture Details

- **App Frontend (Native)**: The main tabs, camera interface, and review screen are rendered entirely natively using Laravel Blade and NativePHP Mobile's `<native:*>` components. No WebViews are used for the core UI.
- **Solving Engine (Backend)**: When you hit "Solve", the NativePHP app sends the scanned cube string to the host machine's Laravel backend. The backend executes `node.exe` to run the `cubejs` solver algorithm, returning the sequence of moves.
- **3D Animation (WebView)**: The calculated solution is passed securely into a local `<native:webview>` which loads a Three.js environment. This allows for rich, 60fps 3D graphics inside the native app without overwhelming the native UI thread.

## License

MIT License

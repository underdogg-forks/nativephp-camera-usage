import * as THREE from 'three';
import Cube from 'cubejs';

// Setup CubeJS (must initialize solver with identity or something before use, usually precomputed or we use light mode)

export class RubiksCube3D {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        let width = this.container.clientWidth || window.innerWidth || 100;
        let height = this.container.clientHeight || window.innerHeight || 100;

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
        this.camera.position.set(5, 5, 8);
        this.camera.lookAt(0, 0, 0);

        this.renderer = new THREE.WebGLRenderer({ antialias: false, alpha: true });
        this.renderer.setSize(width, height);
        this.container.appendChild(this.renderer.domElement);

        // Lighting
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        this.scene.add(ambientLight);
        const dirLight = new THREE.DirectionalLight(0xffffff, 0.8);
        dirLight.position.set(10, 20, 10);
        this.scene.add(dirLight);

        this.cubes = [];
        this.cubeGroup = new THREE.Group();
        this.scene.add(this.cubeGroup);

        this.isAnimating = false;
        this.moveQueue = [];
        this.currentMove = null;
        this.animationProgress = 0;
        
        // standard rubiks colors: U(White), D(Yellow), F(Green), B(Blue), L(Orange), R(Red)
        // Note: order in three.js BoxGeometry materials: right(x+), left(x-), top(y+), bottom(y-), front(z+), back(z-)
        const colors = [
            0xb90000, // Right (Red)
            0xff5900, // Left (Orange)
            0xffffff, // Top (White)
            0xffd500, // Bottom (Yellow)
            0x009b48, // Front (Green)
            0x0045ad, // Back (Blue)
        ];

        this.createCubelets(colors);

        // Handle resize
        window.addEventListener('resize', () => {
            this.camera.aspect = this.container.clientWidth / this.container.clientHeight;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
        });

        this.animate();
    }

    createCubelets(colors) {
        const spacing = 1.05;
        const geometry = new THREE.BoxGeometry(1, 1, 1);
        
        // Materials (colored faces + black interior)
        const materials = colors.map(c => new THREE.MeshLambertMaterial({ color: c }));
        const blackMat = new THREE.MeshBasicMaterial({ color: 0x000000 });

        for (let x = -1; x <= 1; x++) {
            for (let y = -1; y <= 1; y++) {
                for (let z = -1; z <= 1; z++) {
                    const mats = [];
                    mats.push(x === 1 ? materials[0] : blackMat);  // Right
                    mats.push(x === -1 ? materials[1] : blackMat); // Left
                    mats.push(y === 1 ? materials[2] : blackMat);  // Top
                    mats.push(y === -1 ? materials[3] : blackMat); // Bottom
                    mats.push(z === 1 ? materials[4] : blackMat);  // Front
                    mats.push(z === -1 ? materials[5] : blackMat); // Back

                    const mesh = new THREE.Mesh(geometry, mats);
                    mesh.position.set(x * spacing, y * spacing, z * spacing);
                    mesh.userData = { originalPosition: new THREE.Vector3(x, y, z) };
                    
                    // Edges
                    const edges = new THREE.EdgesGeometry(geometry);
                    const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0x000000, linewidth: 2 }));
                    mesh.add(line);

                    this.cubes.push(mesh);
                    this.cubeGroup.add(mesh);
                }
            }
        }
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        // Auto rotate slowly when not animating moves
        if (!this.isAnimating && this.moveQueue.length === 0) {
            this.cubeGroup.rotation.y += 0.005;
            this.cubeGroup.rotation.x += 0.002;
        }

        // Handle moves
        if (this.currentMove) {
            this.updateMoveAnimation();
        } else if (this.moveQueue.length > 0) {
            this.startNextMove();
        }

        this.renderer.render(this.scene, this.camera);
    }

    queueMoves(movesString) {
        if (!movesString) return;
        const moves = movesString.trim().split(' ');
        this.moveQueue.push(...moves);
    }

    applyInstantMoves(movesString) {
        if (!movesString) return;
        const moves = movesString.trim().split(' ');
        
        // Disable animation flag temporarily
        const wasAnimating = this.isAnimating;
        this.isAnimating = true;

        moves.forEach(move => {
            this.moveQueue.unshift(move);
            this.startNextMove();
            this.animationProgress = 1;
            this.updateMoveAnimation();
        });

        this.isAnimating = wasAnimating;
    }

    startNextMove() {
        this.currentMove = this.moveQueue.shift();
        this.animationProgress = 0;
        this.isAnimating = true;

        // Snap rotation group back to zero to avoid drift
        this.cubeGroup.rotation.set(0, 0, 0);

        // Create a temporary rotation group
        this.pivot = new THREE.Group();
        this.scene.add(this.pivot);

        // Identify which face is rotating
        const face = this.currentMove[0];
        const isPrime = this.currentMove.includes("'");
        const isDouble = this.currentMove.includes("2");

        let targetAngle = Math.PI / 2;
        if (isPrime) targetAngle *= -1;
        if (isDouble) targetAngle *= 2;

        this.currentMoveMeta = {
            face: face,
            targetAngle: targetAngle,
            axis: new THREE.Vector3(),
            cubesToRotate: []
        };

        const threshold = 0.5;

        // Select cubes based on face
        // Faces: U (Top, y=1), D (Bottom, y=-1), R (Right, x=1), L (Left, x=-1), F (Front, z=1), B (Back, z=-1)
        this.cubes.forEach(cube => {
            // Update world matrix to get current world position
            this.cubeGroup.updateMatrixWorld();
            const pos = new THREE.Vector3();
            cube.getWorldPosition(pos);

            let shouldRotate = false;
            if (face === 'U' && pos.y > threshold) { shouldRotate = true; this.currentMoveMeta.axis.set(0, -1, 0); }
            if (face === 'D' && pos.y < -threshold) { shouldRotate = true; this.currentMoveMeta.axis.set(0, 1, 0); }
            if (face === 'R' && pos.x > threshold) { shouldRotate = true; this.currentMoveMeta.axis.set(-1, 0, 0); }
            if (face === 'L' && pos.x < -threshold) { shouldRotate = true; this.currentMoveMeta.axis.set(1, 0, 0); }
            if (face === 'F' && pos.z > threshold) { shouldRotate = true; this.currentMoveMeta.axis.set(0, 0, -1); }
            if (face === 'B' && pos.z < -threshold) { shouldRotate = true; this.currentMoveMeta.axis.set(0, 0, 1); }

            if (shouldRotate) {
                this.currentMoveMeta.cubesToRotate.push(cube);
                // Attach to pivot maintaining world transform
                this.pivot.attach(cube);
            }
        });
    }

    updateMoveAnimation() {
        const speed = 0.1;
        this.animationProgress += speed;

        if (this.animationProgress >= 1) {
            this.animationProgress = 1;
            // Finish rotation
            this.pivot.setRotationFromAxisAngle(this.currentMoveMeta.axis, this.currentMoveMeta.targetAngle);
            this.pivot.updateMatrixWorld();
            
            // Reattach to main group
            this.currentMoveMeta.cubesToRotate.forEach(cube => {
                this.cubeGroup.attach(cube);
            });
            
            this.scene.remove(this.pivot);
            this.currentMove = null;
            this.isAnimating = false;
        } else {
            // Tween rotation
            const currentAngle = this.currentMoveMeta.targetAngle * this.animationProgress;
            this.pivot.setRotationFromAxisAngle(this.currentMoveMeta.axis, currentAngle);
        }
    }
}

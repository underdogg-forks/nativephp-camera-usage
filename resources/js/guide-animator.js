import * as THREE from 'three';

const steps = [
    { title: "Step 1: Scan Top Face", desc: "Hold the cube so the <strong class='text-white'>White center</strong> faces you directly.", rx: Math.PI / 2, ry: 0, rz: 0 },
    { title: "Step 2: Scan Left Face", desc: "Rotate the cube to the left so the <strong class='text-orange-500'>Orange center</strong> faces you.", rx: 0, ry: Math.PI / 2, rz: 0 },
    { title: "Step 3: Scan Front Face", desc: "Rotate the cube back so the <strong class='text-green-500'>Green center</strong> faces you.", rx: 0, ry: 0, rz: 0 },
    { title: "Step 4: Scan Right Face", desc: "Rotate the cube to the right so the <strong class='text-red-500'>Red center</strong> faces you.", rx: 0, ry: -Math.PI / 2, rz: 0 },
    { title: "Step 5: Scan Bottom Face", desc: "Rotate the cube up so the <strong class='text-yellow-400'>Yellow center</strong> faces you.", rx: -Math.PI / 2, ry: 0, rz: 0 },
    { title: "Step 6: Scan Back Face", desc: "Rotate the cube completely around so the <strong class='text-blue-500'>Blue center</strong> faces you.", rx: 0, ry: Math.PI, rz: 0 }
];

let currentStep = 0;
let targetRotation = { x: steps[0].rx, y: steps[0].ry, z: steps[0].rz };

const container = document.getElementById('cube-container');
let width = container.clientWidth || window.innerWidth;
let height = container.clientHeight || window.innerHeight;

const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
camera.position.set(0, 0, 8); // Look directly at the front face

const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
renderer.setSize(width, height);
container.appendChild(renderer.domElement);

const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
scene.add(ambientLight);
const dirLight = new THREE.DirectionalLight(0xffffff, 0.6);
dirLight.position.set(5, 5, 10);
scene.add(dirLight);

const cubeGroup = new THREE.Group();
scene.add(cubeGroup);

// standard rubiks colors: right(x+), left(x-), top(y+), bottom(y-), front(z+), back(z-)
const colors = [
    0xb90000, // Right (Red)
    0xff5900, // Left (Orange)
    0xffffff, // Top (White)
    0xffd500, // Bottom (Yellow)
    0x009b48, // Front (Green)
    0x0045ad, // Back (Blue)
];

const spacing = 1.05;
const geometry = new THREE.BoxGeometry(1, 1, 1);
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
            
            const edges = new THREE.EdgesGeometry(geometry);
            const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0x000000, linewidth: 2 }));
            mesh.add(line);

            cubeGroup.add(mesh);
        }
    }
}

// Initial rotation
cubeGroup.rotation.x = targetRotation.x;
cubeGroup.rotation.y = targetRotation.y;
cubeGroup.rotation.z = targetRotation.z;

function animate() {
    requestAnimationFrame(animate);
    
    // Smoothly interpolate to target rotation using slerp
    const currentQuat = cubeGroup.quaternion;
    const targetEuler = new THREE.Euler(targetRotation.x, targetRotation.y, targetRotation.z, 'XYZ');
    const targetQuat = new THREE.Quaternion().setFromEuler(targetEuler);
    
    currentQuat.slerp(targetQuat, 0.08);

    // Add a slight continuous rotation so it looks 3D and dynamic
    // Apply local rotation to make it spin slightly around its own axes, not world axes
    cubeGroup.rotateY(0.003);
    cubeGroup.rotateX(0.001);

    renderer.render(scene, camera);
}
animate();

window.addEventListener('resize', () => {
    width = container.clientWidth;
    height = container.clientHeight;
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height);
});

// UI Logic
const titleEl = document.getElementById('guide-title');
const descEl = document.getElementById('guide-desc');
const counterEl = document.getElementById('step-counter');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');

function updateUI() {
    const step = steps[currentStep];
    titleEl.innerHTML = step.title;
    descEl.innerHTML = step.desc;
    counterEl.innerText = `${currentStep + 1} / 6`;
    
    // Reset the target rotation to the pure face without the slight drift
    targetRotation = { x: step.rx, y: step.ry, z: step.rz };
    
    // Immediately snap back near the target to ensure the slight drift doesn't mess up the slerp logic too much
    cubeGroup.quaternion.slerp(new THREE.Quaternion().setFromEuler(new THREE.Euler(step.rx, step.ry, step.rz, 'XYZ')), 0.5);

    prevBtn.disabled = currentStep === 0;
    
    if (currentStep === 5) {
        nextBtn.innerText = "Done";
        nextBtn.classList.remove('bg-blue-600', 'hover:bg-blue-500');
        nextBtn.classList.add('bg-green-600', 'hover:bg-green-500');
    } else {
        nextBtn.innerText = "Next Step";
        nextBtn.classList.add('bg-blue-600', 'hover:bg-blue-500');
        nextBtn.classList.remove('bg-green-600', 'hover:bg-green-500');
    }
}

prevBtn.addEventListener('click', () => {
    if (currentStep > 0) {
        currentStep--;
        updateUI();
    }
});

nextBtn.addEventListener('click', () => {
    if (currentStep < 5) {
        currentStep++;
        updateUI();
    } else {
        // If they click done, we can just reset to 0 or leave it
        currentStep = 0;
        updateUI();
    }
});

updateUI();

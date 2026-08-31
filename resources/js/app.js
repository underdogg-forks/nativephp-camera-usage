
import { RubiksCube3D } from './cube-animator';

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('cube-container')) return;

    const cubeApp = new RubiksCube3D('cube-container');
    const playBtn = document.getElementById('play-btn');
    const errorOverlay = document.getElementById('error-overlay');

    const state = window.rubiksCubeState || "UUUUUUUUURRRRRRRRRFFFFFFFFFDDDDDDDDDLLLLLLLLLBBBBBBBBB";
    let solvedMoves = "";

    // Invert a move sequence (used to scramble the 3D cube to match real world)
    function inverseMoves(solutionStr) {
        if (!solutionStr) return "";
        return solutionStr.split(' ').filter(Boolean).reverse().map(move => {
            if (move.endsWith("'")) return move[0];
            if (move.endsWith("2")) return move;
            return move + "'";
        }).join(' ');
    }

    // Validate cube has exactly 9 of each face color
    function isValidCubeState(str) {
        if (!str || str.length !== 54) return false;
        if (str.includes('?')) return false;
        const counts = {};
        for (let i = 0; i < str.length; i++) {
            counts[str[i]] = (counts[str[i]] || 0) + 1;
        }
        for (const f of ['U', 'R', 'F', 'D', 'L', 'B']) {
            if ((counts[f] || 0) !== 9) return false;
        }
        return true;
    }

    function showError(msg) {
        if (playBtn) { playBtn.textContent = "Error"; playBtn.disabled = true; }
        if (errorOverlay) {
            errorOverlay.textContent = msg;
            errorOverlay.classList.remove('hidden');
        }
    }

    if (!isValidCubeState(state)) {
        showError("Invalid cube! The scanned colors do not add up to exactly 9 of each face. Please rescan or fix in the Review tab.");
        return;
    }

    // Solve on the backend (avoids crashing the Android WebView with heavy computation)
    if (playBtn) { playBtn.textContent = "Calculating..."; playBtn.disabled = true; }

    fetch('/api/solve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ cube: state })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            showError("Could not solve: " + data.error + ". Please check the Review tab.");
            return;
        }

        // data.solution is a string like "R U' F2 ..."
        solvedMoves = data.solution;
        console.log("Solution:", solvedMoves);

        // Scramble the 3D cube to visually match the real physical cube
        const scrambleMoves = inverseMoves(solvedMoves);
        if (scrambleMoves) cubeApp.applyInstantMoves(scrambleMoves);

        if (playBtn) { playBtn.textContent = "▶ Solve"; playBtn.disabled = false; }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        showError("Network error. Please make sure the app is running and try again.");
    });

    // Play button: animate the solution
    if (playBtn) {
        playBtn.addEventListener('click', () => {
            if (solvedMoves) {
                cubeApp.queueMoves(solvedMoves);
            }
        });
    }
});

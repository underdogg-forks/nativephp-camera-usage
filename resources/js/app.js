
import { RubiksCube3D } from './cube-animator';

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('cube-container')) return;

    const playBtn = document.getElementById('play-btn');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const errorOverlay = document.getElementById('error-overlay');

    const state = window.rubiksCubeState || "UUUUUUUUURRRRRRRRRFFFFFFFFFDDDDDDDDDLLLLLLLLLBBBBBBBBB";
    let solvedMoves = "";
    let movesArray = [];
    let currentMoveIndex = 0;

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

    const cubeApp = new RubiksCube3D('cube-container');

    function initializeSolution(solutionStr) {
        solvedMoves = solutionStr;
        movesArray = solvedMoves.split(' ').filter(Boolean);
        currentMoveIndex = 0;
        console.log("Solution:", solvedMoves);

        const scrambleMoves = inverseMoves(solvedMoves);
        if (scrambleMoves) cubeApp.applyInstantMoves(scrambleMoves);

        if (playBtn) { playBtn.textContent = "Play All"; playBtn.disabled = false; }
        if (prevBtn) { prevBtn.disabled = false; }
        if (nextBtn) { nextBtn.disabled = false; }
    }

    if (window.rubiksSolution) {
        initializeSolution(window.rubiksSolution);
    } else {
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
            initializeSolution(data.solution);
        })
        .catch(err => {
            console.error("Fetch error:", err);
            showError("Network error. Please make sure the app is running and try again.");
        });
    }

    // Play button: animate the solution
    if (playBtn) {
        playBtn.addEventListener('click', () => {
            if (currentMoveIndex < movesArray.length) {
                const remainingMoves = movesArray.slice(currentMoveIndex).join(' ');
                cubeApp.queueMoves(remainingMoves);
                currentMoveIndex = movesArray.length;
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (cubeApp.isAnimating || cubeApp.moveQueue.length > 0) return; // wait for current animation
            if (currentMoveIndex < movesArray.length) {
                cubeApp.queueMoves(movesArray[currentMoveIndex]);
                currentMoveIndex++;
            }
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (cubeApp.isAnimating || cubeApp.moveQueue.length > 0) return; // wait for current animation
            if (currentMoveIndex > 0) {
                currentMoveIndex--;
                const prevMove = movesArray[currentMoveIndex];
                const invMove = inverseMoves(prevMove);
                cubeApp.queueMoves(invMove);
            }
        });
    }
});

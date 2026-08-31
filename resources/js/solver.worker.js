import Cube from 'cubejs';

self.onmessage = function(e) {
    try {
        Cube.initSolver();
        const cube = Cube.fromString(e.data);
        const solution = cube.solve();
        self.postMessage({ success: true, solution: solution });
    } catch (err) {
        self.postMessage({ success: false, error: err.toString() });
    }
};

<?php
$allFaces = [
    0 => ['U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'F'],
    1 => ['L', 'L', 'L', 'L', 'L', 'L', 'B', 'B', 'L'],
    2 => ['F', 'F', 'D', 'F', 'F', 'L', 'F', 'F', 'L'],
    3 => ['R', 'R', 'R', 'D', 'R', 'R', 'D', 'F', 'F'],
    4 => ['D', 'D', 'B', 'D', 'D', 'R', 'D', 'D', 'R'],
    5 => ['B', 'B', 'B', 'B', 'B', 'B', 'U', 'R', 'R']
];
$safeFaces = [];
for ($i = 0; $i < 6; $i++) {
    $safeFaces[$i] = $allFaces[$i] ?? array_fill(0, 9, "U");
}
$cubeString = implode("", $safeFaces[0]) . implode("", $safeFaces[3]) . implode("", $safeFaces[2]) . implode("", $safeFaces[4]) . implode("", $safeFaces[1]) . implode("", $safeFaces[5]);
echo "String: " . $cubeString . "\n";
$counts = array_count_values(str_split($cubeString));
print_r($counts);
foreach (["U", "R", "F", "D", "L", "B"] as $face) {
    if (($counts[$face] ?? 0) !== 9) {
        echo "INVALID: " . $face . " has " . ($counts[$face] ?? 0) . "\n";
    }
}
$nodePath = 'C:\\Program Files\\nodejs\\node.exe';
$script = "const Cube = require('cubejs'); Cube.initSolver(); try { const c = Cube.fromString('"
    .addslashes($cubeString)
    ."'); const sol = c.solve(); console.log(JSON.stringify({success:true,solution:sol})); } catch(e) { console.log(JSON.stringify({success:false,error:e.toString()})); }";
$command = escapeshellarg($nodePath).' -e '.escapeshellarg($script);
echo "Executing: " . $command . "\n";
$output = shell_exec($command);
echo "Output: " . $output . "\n";

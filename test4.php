<?php

$palette = [
    'U' => [255, 255, 255], // White
    'D' => [255, 213, 0],   // Yellow
    'F' => [0, 155, 72],    // Green
    'B' => [0, 69, 173],    // Blue
    'L' => [255, 89, 0],    // Orange
    'R' => [185, 0, 0],      // Red
];
function mapToRubiksColor($r, $g, $b, $palette)
{
    $minDistance = PHP_INT_MAX;
    $closestFace = '';

    foreach ($palette as $face => $color) {
        $distance = pow($color[0] - $r, 2) + pow($color[1] - $g, 2) + pow($color[2] - $b, 2);
        if ($distance < $minDistance) {
            $minDistance = $distance;
            $closestFace = $face;
        }
    }

    return $closestFace;
}

$samples = [
    [204, 193, 5],
    [131, 199, 18],
    [118, 118, 22],
    [96, 154, 166],
    [134, 197, 30],
    [108, 186, 14],
    [219, 197, 0],
    [224, 200, 2],
    [123, 163, 155],
];

foreach ($samples as $rgb) {
    echo mapToRubiksColor($rgb[0], $rgb[1], $rgb[2], $palette)."\n";
}

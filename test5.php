<?php

function rgbToHsl($r, $g, $b)
{
    $r /= 255;
    $g /= 255;
    $b /= 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l = ($max + $min) / 2;
    $h = 0;
    $s = 0;

    if ($max != $min) {
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        switch ($max) {
            case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                break;
            case $g: $h = ($b - $r) / $d + 2;
                break;
            case $b: $h = ($r - $g) / $d + 4;
                break;
        }
        $h /= 6;
    }

    return [$h * 360, $s, $l];
}

function mapToRubiksColor($r, $g, $b)
{
    [$h, $s, $l] = rgbToHsl($r, $g, $b);

    // White/Gray detection (low saturation or very high/low brightness but uncolored)
    if ($s < 0.15) {
        return 'U';
    }

    if ($h >= 340 || $h <= 15) {
        return 'R';
    }
    if ($h > 15 && $h <= 45) {
        return 'L';
    } // Orange
    if ($h > 45 && $h <= 75) {
        return 'D';
    } // Yellow
    if ($h > 75 && $h <= 160) {
        return 'F';
    } // Green
    if ($h > 160 && $h <= 260) {
        return 'B';
    } // Blue
    if ($h > 260 && $h < 340) {
        return 'R';
    } // Purple-ish red

    return 'U';
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
    [$h, $s, $l] = rgbToHsl($rgb[0], $rgb[1], $rgb[2]);
    echo mapToRubiksColor($rgb[0], $rgb[1], $rgb[2]).' (H: '.round($h).', S: '.round($s, 2).', L: '.round($l, 2).")\n";
}

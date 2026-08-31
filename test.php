<?php

$path = 'C:/Users/JontyRulz/.gemini/antigravity-ide/brain/a1589636-3cbe-470f-80ea-062ea80ab12b/.user_uploaded/media_1788017271578.jpg';
$img = imagecreatefromjpeg($path);

// Check EXIF
$exif = @exif_read_data($path);
$orientation = isset($exif['Orientation']) ? $exif['Orientation'] : 1;
echo "EXIF Orientation: $orientation\n";

$width = imagesx($img);
$height = imagesy($img);
echo "Image dimensions: $width x $height\n";

$squareSize = min($width, $height) * 0.40;
$startX = ($width - $squareSize) / 2;
$startY = ($height - $squareSize) / 2;
$tileSize = $squareSize / 3;

echo "Square size: $squareSize, StartX: $startX, StartY: $startY, TileSize: $tileSize\n";

for ($y = 0; $y < 3; $y++) {
    for ($x = 0; $x < 3; $x++) {
        $sampleX = (int) floor($startX + ($x + 0.5) * $tileSize);
        $sampleY = (int) floor($startY + ($y + 0.5) * $tileSize);

        $rgb = imagecolorat($img, $sampleX, $sampleY);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        echo "Tile [$y, $x] @ ($sampleX, $sampleY): RGB($r, $g, $b) \n";
    }
}

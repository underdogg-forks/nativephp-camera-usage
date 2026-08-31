<?php

$dir = 'C:/Users/JontyRulz/.gemini/antigravity-ide/brain/a1589636-3cbe-470f-80ea-062ea80ab12b/.user_uploaded';
$files = glob("$dir/*.jpg");
foreach ($files as $path) {
    $img = @imagecreatefromjpeg($path);
    if ($img) {
        $w = imagesx($img);
        $h = imagesy($img);
        echo basename($path).": $w x $h\n";
    }
}

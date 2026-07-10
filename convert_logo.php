<?php
$img = imagecreatefromwebp(__DIR__ . '/public/logo.webp');
imagepng($img, __DIR__ . '/public/logo.png');
imagedestroy($img);
echo "Done";

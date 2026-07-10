<?php
$srcPath = __DIR__ . '/public/logo.png';
$srcImg = imagecreatefrompng($srcPath);
$srcW = imagesx($srcImg);
$srcH = imagesy($srcImg);

$targetSize = 1200;
$newLogoSize = 550;

$destImg = imagecreatetruecolor($targetSize, $targetSize);
imagesavealpha($destImg, true);
$transparent = imagecolorallocatealpha($destImg, 0, 0, 0, 127);
imagefill($destImg, 0, 0, $transparent);

$dstX = ($targetSize - $newLogoSize) / 2;
$dstY = ($targetSize - $newLogoSize) / 2;

// Use high-quality resampling to scale up the logo
imagecopyresampled($destImg, $srcImg, $dstX, $dstY, 0, 0, $newLogoSize, $newLogoSize, $srcW, $srcH);

imagepng($destImg, __DIR__ . '/public/logo_padded.png');
imagedestroy($srcImg);
imagedestroy($destImg);
echo "High-res padded logo created.\n";

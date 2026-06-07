<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$iconDir = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'icons';
if (!is_dir($iconDir) && !mkdir($iconDir, 0775, true) && !is_dir($iconDir)) {
    fwrite(STDERR, "Cannot create icon directory\n");
    exit(1);
}

if (!function_exists('imagecreatetruecolor')) {
    fwrite(STDERR, "PHP GD extension is required to generate the icon\n");
    exit(1);
}

$size = 256;
$image = imagecreatetruecolor($size, $size);
imagesavealpha($image, true);
imagealphablending($image, true);

$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
$white = imagecolorallocate($image, 255, 255, 255);
$red = imagecolorallocate($image, 215, 25, 32);
$redLight = imagecolorallocate($image, 239, 43, 45);
$redDark = imagecolorallocate($image, 159, 17, 24);
$rim = imagecolorallocate($image, 184, 15, 24);
$highlight = imagecolorallocate($image, 255, 122, 125);

imagefill($image, 0, 0, $transparent);
imagefilledrectangle($image, 30, 30, 226, 226, $white);
imagefilledellipse($image, 30, 30, 78, 78, $white);
imagefilledellipse($image, 226, 30, 78, 78, $white);
imagefilledellipse($image, 30, 226, 78, 78, $white);
imagefilledellipse($image, 226, 226, 78, 78, $white);
imagefilledrectangle($image, 30, 69, 226, 187, $white);
imagefilledrectangle($image, 69, 30, 187, 226, $white);

imagesetthickness($image, 18);
imagearc($image, 128, 94, 92, 92, 205, 335, $redDark);
imageline($image, 86, 97, 106, 63, $redDark);
imageline($image, 170, 97, 150, 63, $redDark);

$basket = [
    63, 99,
    193, 99,
    176, 182,
    151, 202,
    105, 202,
    80, 182,
];
imagefilledpolygon($image, $basket, 6, $red);

$inner = [
    76, 107,
    180, 107,
    167, 168,
    149, 183,
    107, 183,
    89, 168,
];
imagefilledpolygon($image, $inner, 6, $redLight);

imagefilledrectangle($image, 52, 91, 204, 123, $rim);
imagefilledellipse($image, 52, 107, 32, 32, $rim);
imagefilledellipse($image, 204, 107, 32, 32, $rim);
imageline($image, 70, 96, 186, 96, $highlight);

imagesetthickness($image, 13);
$whiteBar = imagecolorallocate($image, 255, 255, 255);
imageline($image, 94, 137, 94, 176, $whiteBar);
imageline($image, 128, 137, 128, 176, $whiteBar);
imageline($image, 162, 137, 162, 176, $whiteBar);

$pngPath = $iconDir . DIRECTORY_SEPARATOR . 'gharib-market.png';
$icoPath = $iconDir . DIRECTORY_SEPARATOR . 'gharib-market.ico';

if (!imagepng($image, $pngPath)) {
    fwrite(STDERR, "Cannot write PNG icon\n");
    imagedestroy($image);
    exit(1);
}
imagedestroy($image);

$pngData = file_get_contents($pngPath);
if ($pngData === false) {
    fwrite(STDERR, "Cannot read generated PNG icon\n");
    exit(1);
}

$ico = pack('vvv', 0, 1, 1);
$ico .= pack('CCCCvvVV', 0, 0, 0, 0, 1, 32, strlen($pngData), 22);
$ico .= $pngData;

if (file_put_contents($icoPath, $ico) === false) {
    fwrite(STDERR, "Cannot write ICO icon\n");
    exit(1);
}

echo $icoPath . PHP_EOL;

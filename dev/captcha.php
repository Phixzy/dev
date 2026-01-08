<?php
session_start();

// Generate a new captcha every time
$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// Set background color
$bg = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $bg);

// Generate random text
$text = substr(md5(rand() . time()), 0, 6);

// Store in session
$_SESSION['captcha'] = $text;

// Add some noise for security
for ($i = 0; $i < 50; $i++) {
    $color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
    imagesetpixel($image, rand(0, $width), rand(0, $height), $color);
}

// Add the text
$color = imagecolorallocate($image, 0, 0, 0);
imagestring($image, 5, 20, 10, $text, $color);

header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

imagepng($image);
imagedestroy($image);
?>

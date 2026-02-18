<?php
/**
 * PWA Icon Generator - generates all required icon sizes
 * Run once: php generate.php
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$dir = __DIR__;

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    
    // Background circle with gradient effect
    $bg1 = imagecolorallocate($img, 26, 54, 93);   // #1a365d
    $bg2 = imagecolorallocate($img, 44, 82, 130);   // #2c5282
    imagefill($img, 0, 0, imagecolorallocate($img, 0, 0, 0));
    
    // Draw filled circle
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    imagefilledellipse($img, $size/2, $size/2, $size, $size, $bg1);
    
    // Lighter overlay for gradient feel
    for ($i = 0; $i < $size/2; $i++) {
        $alpha = intval(127 - ($i / ($size/2)) * 30);
        $overlay = imagecolorallocatealpha($img, 44, 82, 130, $alpha);
        imagefilledellipse($img, $size/2 + $i/3, $size/2 + $i/3, $size - $i, $size - $i, $overlay);
    }
    
    // Cross - gold color
    $gold = imagecolorallocate($img, 212, 168, 67); // #d4a843
    $cx = $size / 2;
    $cy = $size / 2 - $size * 0.05;
    $crossH = intval($size * 0.38);
    $crossW = intval($size * 0.09);
    $armW = intval($size * 0.28);
    
    // Vertical bar
    imagefilledrectangle($img, 
        intval($cx - $crossW/2), intval($cy - $crossH/2),
        intval($cx + $crossW/2), intval($cy + $crossH/2),
        $gold
    );
    // Horizontal bar
    imagefilledrectangle($img,
        intval($cx - $armW/2), intval($cy - $crossH/4),
        intval($cx + $armW/2), intval($cy - $crossH/4 + $crossW),
        $gold
    );
    
    // Text "HTP"
    $white = imagecolorallocate($img, 255, 255, 255);
    $fontSize = max(intval($size * 0.08), 8);
    $textY = intval($cy + $crossH/2 + $size * 0.12);
    
    // Use built-in font for smaller sizes, TTF for larger
    if ($size <= 96) {
        $font = 5;
        $tw = imagefontwidth($font) * 3;
        imagestring($img, $font, intval($cx - $tw/2), intval($textY - imagefontheight($font)/2), 'HTP', $white);
    } else {
        // Try to use a system font
        $fontFile = '/System/Library/Fonts/Helvetica.ttc';
        if (!file_exists($fontFile)) {
            $fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        }
        if (file_exists($fontFile)) {
            $bbox = imagettfbbox($fontSize, 0, $fontFile, 'HTP');
            $tw = $bbox[2] - $bbox[0];
            imagettftext($img, $fontSize, 0, intval($cx - $tw/2), $textY, $white, $fontFile, 'HTP');
        } else {
            $font = 5;
            $tw = imagefontwidth($font) * 3;
            imagestring($img, $font, intval($cx - $tw/2), intval($textY - imagefontheight($font)/2), 'HTP', $white);
        }
    }
    
    $filename = "{$dir}/icon-{$size}x{$size}.png";
    imagepng($img, $filename);
    imagedestroy($img);
    echo "Generated: icon-{$size}x{$size}.png\n";
}

// Generate screenshot-wide (1280x720)
$img = imagecreatetruecolor(1280, 720);
$bg = imagecolorallocate($img, 15, 23, 42);
imagefill($img, 0, 0, $bg);
$gold = imagecolorallocate($img, 212, 168, 67);
$white = imagecolorallocate($img, 255, 255, 255);
$gray = imagecolorallocate($img, 148, 163, 184);

$fontFile = '/System/Library/Fonts/Helvetica.ttc';
if (!file_exists($fontFile)) $fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

if (file_exists($fontFile)) {
    imagettftext($img, 48, 0, 580, 300, $gold, $fontFile, '✝');
    imagettftext($img, 32, 0, 430, 370, $white, $fontFile, 'Holy Trinity Parish');
    imagettftext($img, 16, 0, 410, 420, $gray, $fontFile, 'A Community of Faith, Hope & Love — Kabwe, Zambia');
} else {
    imagestring($img, 5, 600, 300, '+', $gold);
    imagestring($img, 5, 500, 350, 'Holy Trinity Parish', $white);
    imagestring($img, 3, 460, 400, 'A Community of Faith, Hope & Love - Kabwe, Zambia', $gray);
}
imagepng($img, "{$dir}/screenshot-wide.png");
imagedestroy($img);
echo "Generated: screenshot-wide.png\n";

// Generate screenshot-mobile (390x844)
$img = imagecreatetruecolor(390, 844);
$bg = imagecolorallocate($img, 15, 23, 42);
imagefill($img, 0, 0, $bg);
$gold = imagecolorallocate($img, 212, 168, 67);
$white = imagecolorallocate($img, 255, 255, 255);
$gray = imagecolorallocate($img, 148, 163, 184);

if (file_exists($fontFile)) {
    imagettftext($img, 36, 0, 170, 350, $gold, $fontFile, '✝');
    imagettftext($img, 20, 0, 80, 410, $white, $fontFile, 'Holy Trinity Parish');
    imagettftext($img, 12, 0, 120, 450, $gray, $fontFile, 'Kabwe, Zambia');
} else {
    imagestring($img, 5, 185, 340, '+', $gold);
    imagestring($img, 5, 100, 400, 'Holy Trinity Parish', $white);
    imagestring($img, 3, 130, 440, 'Kabwe, Zambia', $gray);
}
imagepng($img, "{$dir}/screenshot-mobile.png");
imagedestroy($img);
echo "Generated: screenshot-mobile.png\n";

echo "\nAll icons generated successfully!\n";

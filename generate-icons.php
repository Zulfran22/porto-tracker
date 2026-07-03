<?php

// Generate PWA icons - run once: php generate-icons.php
foreach ([192, 512] as $size) {
    $img = imagecreatetruecolor($size, $size);
    $bg = imagecolorallocate($img, 9, 9, 11);      // zinc-950
    $acc = imagecolorallocate($img, 234, 179, 8);    // yellow-500
    $blk = imagecolorallocate($img, 0, 0, 0);

    // Rounded background (circle approximation)
    imagefilledellipse($img, $size / 2, $size / 2, $size, $size, $acc);

    // "PT" text
    $fontSize = intval($size * 0.28);
    $fontPath = __DIR__.'/resources/fonts/figtree.ttf';
    if (! file_exists($fontPath)) {
        // fallback: use built-in font
        $fw = imagefontwidth(5) * 2;
        $fh = imagefontheight(5);
        imagestring($img, 5, ($size - $fw) / 2, ($size - $fh) / 2, 'PT', $blk);
    } else {
        $bbox = imagettfbbox($fontSize, 0, $fontPath, 'PT');
        $tw = $bbox[2] - $bbox[0];
        $th = abs($bbox[7] - $bbox[1]);
        imagettftext($img, $fontSize, 0, ($size - $tw) / 2, ($size + $th) / 2, $blk, $fontPath, 'PT');
    }

    imagepng($img, __DIR__."/public/icons/icon-{$size}.png");
    imagedestroy($img);
    echo "Generated icon-{$size}.png\n";
}

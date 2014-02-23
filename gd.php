<?php

function area($r, $R, $d) {

    $a = bcpow($R, 2) * acos((bcpow($R, 2) - bcpow($r, 2) + bcpow($d, 2)) / 
            (2.0 * $d * $R)) + (bcpow($r, 2)) * acos((-bcpow($R, 2) + bcpow($r, 2) + 
                    bcpow($d, 2)) / (2.0 * $d * $r)) - bcdiv(bcsqrt((-$R + $r + $d) * 
                            ($R - $r + $d) * ($R + $r - $d) * ($R + $r + $d)), 2.0);
    return $a;
} 

function sgn($what) {
    return ($what >= 0) ? (1.0) : (-1.0);
}

function makeImage($s1, $s2, $s3, $filename, $artists1, $artists2, $artists3) {

    bcscale(10);

    $r1 = bcsqrt($s1 / M_PI);
    $r2 = bcsqrt($s2 / M_PI);

    $min = floatval(min($r1, $r2));
    $max = floatval(max($r1, $r2));
    $dif = $min / $max;
    $zoom = 150.0 / $max;

    if ($s1 == $s3 && $s2 == $s3) {
        $d = 0;
    }
    else {
        
        $s4 = 0;        // square of intersection
        $d = $r1 + $r2; // distance between the centers
        $prev = 1;      // for sign change
        $step = 1.0;    // precision
        while (abs(bcsub($s3, $s4)) > 0.1) {
            if ($prev != sgn(bcsub($s3, $s4))) {
                $step = bcdiv($step, 2.0);
                $prev = sgn(bcsub($s3, $s4));
            }
            $d = $d - $step * $prev;
            $s4 = area($r1, $r2, $d);
        //	echo abs($s3 - $s4) . "<br>";
        }
    }

    // 640x300
    $im = imagecreatetruecolor(640, 340);

    $red    = imagecolorallocate($im, 0xCC, 0x00, 0x00);
    $blue   = imagecolorallocate($im, 0x00, 0x00, 0xCC);
    $white  = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);

    imagefill($im, 1, 1, $white);
    
    imageellipse($im, 240                         , 170, 300                 , 300         , $red );
    imageellipse($im, intval(240.0 + ($d * $zoom)), 170, intval(300.0 * $dif), 300.0 * $dif, $blue);

    $font = 'FreeSans.ttf';
    $font_size = 8; 

    $i = 356 - (300.0 * $dif);
    foreach ($artists3 as $key => $value) {
        $box = imageftbbox($font_size, 0, $font, $key);
        $x = (intval(480.0 + ($d * $zoom)) - ($box[2] - $box[0])) / 2; 
        imagettftext($im, $font_size, 0, $x, $i, $red, $font, $key);
        $i += 16;
        if ($i >= (300.0 * $dif)) {
            break;
        }
    }
//    imagettftext($im, $font_size, 0, intval(240.0 + ($d * $zoom)), 170, $blue, $font, $artists2[0]);

    imagepng($im, $filename);
}
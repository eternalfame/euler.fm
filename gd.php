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

    $min_r = floatval(min($r1, $r2));
    $max_r = floatval(max($r1, $r2));
    $dif = $min_r / $max_r;
    $zoom = 150.0 / $max_r;

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
        }
    }

    $border = 5;
    
    $c1_d = 400.0;               // diameter of big circle
    $c1_x = $border + $c1_d / 2; // x coord  of big circle
    $c1_y = $c1_x;               // y coord  of big circle
    $c2_x = intval($c1_x + ($d * $zoom));
    $c2_d = intval($c1_d  * $dif);
    
    $inters_r = (($c1_d + $c2_d) / 2 - ($c2_x - $c1_x)) / 2;  // length of 
                                // two circles' radiuses intersection   
    
    $im_w = $c2_x + $c2_d / 2 + $border;
    $im_h = $c1_d + $border * 2;
    $im = imagecreatetruecolor($im_w, $im_h);

    $red    = imagecolorallocate     ($im, 0xCC, 0x00, 0x00);
    $red_a  = imagecolorallocatealpha($im, 0xCC, 0x00, 0x00, 96);
    $blue   = imagecolorallocate     ($im, 0x00, 0x00, 0xCC);
    $blue_a = imagecolorallocatealpha($im, 0x00, 0x00, 0xCC, 96);
    $white  = imagecolorallocate     ($im, 0xFF, 0xFF, 0xFF);
    $purple = imagecolorallocate     ($im, 0x10, 0x1C, 0x10);
            
    imagefill($im, 1, 1, $white);
    
    imagefilledellipse($im, $c1_x, $c1_y, $c1_d, $c1_d, $red_a );
    imageellipse      ($im, $c1_x, $c1_y, $c1_d, $c1_d, $red );
    
    imagefilledellipse($im, $c2_x, $c1_y, $c2_d, $c2_d, $blue_a);
    imageellipse      ($im, $c2_x, $c1_y, $c2_d, $c2_d, $blue);

    $font = 'FreeSans.ttf';
    $font_size = 8; 
    
    $margin = 16;
    $y = ($im_h - $c2_d) / 2 + $margin * 3; // it's y position of first string
    // it's not very logic 

    foreach ($artists3 as $key => $value) {
        $box = imageftbbox($font_size, 0, $font, $key);
        // text is center-aligned between two circles
        $x = ($c1_x + ($c1_d / 2) - $inters_r) - (($box[2] - $box[0]) / 2); 
        imagettftext($im, $font_size, 0, $x, $y, $purple, $font, $key);
        $y += $margin;
        if ($y >= $c2_d + (($im_h - $c2_d) / 2) - $margin * 2) {
            break;
        }
    }
    imagealphablending($im, true);
    imagepng($im, $filename);
}
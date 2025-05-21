<?php

function area($r, $R, $d) {
    if ($r == 0 || $R == 0 || $d == 0) return 0;

    $R2 = $R * $R;
    $r2 = $r * $r;
    $d2 = $d * $d;

    $part1 = $R2 * acos(($R2 - $r2 + $d2) / (2.0 * $d * $R));
    $part2 = $r2 * acos((- $R2 + $r2 + $d2) / (2.0 * $d * $r));
    $part3 = 0.5 * sqrt((-$R + $r + $d) * ($R - $r + $d) * ($R + $r - $d) * ($R + $r + $d));

    return $part1 + $part2 - $part3;
}

function intersection_distance($R1, $R2, $d) {
	if ($d == 0) {
		return 0;
	}
	
    if ($d > $R1 + $R2 || $d < abs($R1 - $R2)) {
        return $R1;
    }

    $a = (pow($R1, 2) - pow($R2, 2) + pow($d, 2)) / (2 * $d);
    return $a;
}

function drawCircle($im, $x, $y, $diameter, $fillColor, $strokeColor) {
	imagefilledellipse($im, $x, $y, $diameter, $diameter, $fillColor );
    imageellipse      ($im, $x, $y, $diameter, $diameter, $strokeColor );
}

function makeImage($s1, $s2, $s3, $artists1, $artists2, $artists3) {
	if ($s1 < $s2) {
		list($s1, $s2) = [$s2, $s1];
		list($artists1, $artists2) = [$artists2, $artists1];
	}
	
    $r1 = sqrt($s1 / M_PI);
    $r2 = sqrt($s2 / M_PI);

    $min_r = floatval(min($r1, $r2));
    $max_r = floatval(max($r1, $r2));
    $dif = $min_r / $max_r;
	$zoom = 200.0 / $r1;
	
    if ($s1 == $s3 && $s2 == $s3) {
        $distance = 0;
    }
    else {
        $distance = $r1 + $r2; // distance between the centers
		$s4 = 0;        // square of intersection
        $prev_diff = $s1 > $s2 ? 1 : -1;      // for sign change
        $step = 1.0;    // precision
        
		$diff = $s3 - $s4;
		while (abs($diff) > 0.1) { // if s3 == 0 that means no intersection, so the distance between centers of circles stays $r1 + $r2
			$distance += ($diff > 0 ? -$step : $step);
			$s4 = area($r1, $r2, $distance);
			$diff = $s3 - $s4;
			
			if ($diff * $prev_diff < 0) {
				$step /= 2.0;
			}
			$prev_diff = $diff;
		}
    }
				
    $border = 5;
    
    $c1_d = 400.0;               // diameter of big circle
    $c1_x = $border + $c1_d / 2; // x coord  of big circle
    $c1_y = $c1_x;               // y coord  of big circle
    $c2_x = intval($c1_x + $distance * $zoom);
    $c2_d = intval($c1_d  * $dif);
    
	if ($distance == 0) {
		$inters_r = $c2_d / 2.0;
	} else if ($distance == $r1 + $r2) {
		$inters_r = 0;
	} else {
		$inters_r = (($c1_d + $c2_d) / 2.0 - ($c2_x - $c1_x)) / 2.0;  // length of 
                                // two circles' radiuses intersection   
    }
	
    $im_w = max($c2_x + $c2_d / 2, $c1_d) + $border * 2;
    $im_h = $c1_d + $border * 2;
    $im = @imagecreatetruecolor($im_w, $im_h);

    $red    = imagecolorallocate     ($im, 0xCC, 0x00, 0x00);
    $red_a  = imagecolorallocatealpha($im, 0xCC, 0x00, 0x00, 96);
    $blue   = imagecolorallocate     ($im, 0x00, 0x00, 0xCC);
    $blue_a = imagecolorallocatealpha($im, 0x00, 0x00, 0xCC, 96);
    $white  = imagecolorallocate     ($im, 0xFF, 0xFF, 0xFF);
    $purple = imagecolorallocate     ($im, 0x10, 0x1C, 0x10);
            
    imagefill($im, 1, 1, $white);
    
	drawCircle($im, $c1_x, $c1_y, $c1_d, $red_a, $red);
	drawCircle($im, $c2_x, $c1_y, $c2_d, $blue_a, $blue);

    $font = 'FreeSans.ttf';
    $font_size = 8; 
    
    $margin = 16;
    $y = ($im_h - $c2_d) / 2 + $margin * 3; // it's y position of first string
    // it's not very logical

	$intersection_distance = intersection_distance($c1_d / 2, $c2_d / 2, $distance * $zoom);

    foreach ($artists3 as $key => $value) {
        $box = imageftbbox($font_size, 0, $font, "$key ($value)");
        // text is center-aligned between two circles
		$x = $c1_x + $intersection_distance - ($box[2] - $box[0]) / 2;
        imagettftext($im, $font_size, 0, $x, $y, $purple, $font, "$key ($value)");
        $y += $margin;
        if ($y >= $c2_d + (($im_h - $c2_d) / 2) - $margin * 2) {
            break;
        }
    }
    imagealphablending($im, true);
	
	ob_start();
    imagepng($im);
	$imgData=ob_get_clean();
	imagedestroy($im);
	//Echo the data inline in an img tag with the common src-attribute
	echo '<img src="data:image/png;base64,'.base64_encode($imgData).'" />';
	
}
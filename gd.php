<?php

class SVG {
	private $content = "";
	
	function __construct($im_w, $im_h) {
		$this->content = '<?xml version="1.0" encoding="utf-8"?>
			<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="' . $im_w . '" height="' . $im_h . '">';
	}
	
	function drawCircle($class, $x, $y, $radius, $fillColor, $strokeColor) {
		$this->content .= '<circle class="' . $class . '"cx="' . $x . '" cy="' . $y . '" r="' . $radius . '" style="fill: ' . $fillColor . '; stroke: ' . $strokeColor . '; stroke-width: 1"/>';
	}
	
	function drawText($x, $y, $text, $fillColor) {
		$this->content .= '<text font-size="12" font="Open Sans" text-anchor="middle" x="' . $x . '" y="' . $y . '" style="fill: ' . $fillColor . '">' . $text . '</text>';
	}
	
	function drawLink($x, $y, $url, $text, $fillColor) {
		$this->content .= '<a href="' . $url . '">';
		$this->drawText($x, $y, $text, $fillColor);
		$this->content .= '</a>';
	}
	
	function output() {
		return $this->content . '</svg>';
	}
}

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

function makeImage($s1, $s2, $s3, $artists1, $artists2, $artists3) {
	if ($s1 == 0 || $s2 == 0) {
		echo "It seems one of the users didn't listen to anything in this period :(";
		return;
	}
	
	$invert = false;
	if ($s1 < $s2) {
		$invert = true;
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
	
    $im_w = max($c2_x + $c2_d / 2, $c1_d) + $border * 2;
    $im_h = $c1_d + $border * 2;	
	$im = new SVG($im_w, $im_h);

    $color1  = "rgba(252, 116, 47, 1)";
    $color1a = "rgba(252, 116, 47, 0.5)";
    $color2  = "rgba(235, 44, 81, 1)";
    $color2a = "rgba(235, 44, 81, 0.5)";
    $white   = "rgba(255, 255, 255, 1)";
                
	if ($invert) {	
		$im->drawCircle('red', $im_w - $c1_x, $c1_y, $c1_d / 2, $color2a, $color2);
		$im->drawCircle('orange', $im_w - $c2_x, $c1_y, $c2_d / 2, $color1a, $color1);
	} else {
		$im->drawCircle('red', $c2_x, $c1_y, $c2_d / 2, $color2a, $color2);
		$im->drawCircle('orange', $c1_x, $c1_y, $c1_d / 2, $color1a, $color1);
	}
    
    $margin = 16;
	// it's not very logical
	// todo: fix the y-positioning of the text
	$start_of_text = ($im_h - $c2_d) / 2 + $margin * 4; // it's y position of first string
	$end_of_text = $c2_d + (($im_h - $c2_d) / 2) - $margin * 3;
    $y = $start_of_text;

	$intersection_distance = intersection_distance($c1_d / 2, $c2_d / 2, $distance * $zoom);
	
	// text is center-aligned between two circles
	if ($invert) {
		$x = $im_w - ($c1_x + $intersection_distance);
	} else {
		$x = $c1_x + $intersection_distance;
	}
	
	if (count($artists3) == 0) {
		$im->drawText($x, $im_h - $c1_d / 2, "No common artists :(", $white);
	}
    foreach ($artists3 as $key => $value) {
		$im->drawLink($x, $y, $value['url'], $key . " (" . $value['playcount'] . ")", $white);

        $y += $margin;
        if ($y >= $end_of_text) {
            break;
        }
    }
	
	//Echo the data inline in an img tag with the common src-attribute
	echo $im->output();
}
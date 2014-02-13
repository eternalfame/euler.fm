<?php

function area($r, $R, $d) {
    $a = bcpow($R, 2) * acos((bcpow($R, 2) - bcpow($r, 2) + bcpow($d, 2)) / 
            (2.0 * $d * $R)) + (bcpow($r, 2)) * acos((-bcpow($R, 2) + bcpow($r, 2) + 
                    bcpow($d, 2)) / (2.0 * $d * $r)) - bcdiv(bcsqrt((-$R + $r + $d) * 
                            ($R - $r + $d) * ($R + $r - $d) * ($R + $r + $d)) ,2.0);
    return $a;
} 

function sgn($what) {
    return ($what >= 0) ? (1.0) : (-1.0);
}

function makeImage($s1, $s2, $s3, $filename) {

    bcscale(10);

    $r1 = bcsqrt($s1 / M_PI);
    $r2 = bcsqrt($s2 / M_PI);

    $min = floatval(min($r1, $r2));
    $max = floatval(max($r1, $r2));
    $dif = $min / $max;
    $zoom = 150.0 / $max;

    if ($s1 == $s3 && $s2 == $s3) $d = 0;
    else {
        $s4 = 0;
        $d = $r1 + $r2;
        $prev = 1;
        $step = 1.0;

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

    // PNG изображение
//    header('Content-type: image/png');

    // 150x100
    $im = imagecreatetruecolor(640, 340);

    // Определяем красный цвет
    $red = imagecolorallocate($im, 0xCC, 0x00, 0x00);
    $blue = imagecolorallocate($im, 0x00, 0x00, 0xCC);
    // Определяем белый цвет
    $white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);

    // Делаем фон белым (по-умолчанию черный)
    imagefill($im, 1, 1, $white);
    // Рисуем круг красного цвета
    imageellipse($im, 240, 170, 300, 300, $red);
    // ну и синего тоже
    imageellipse($im, intval(240.0 + ($d * $zoom)), 170, intval(300.0 * $dif), 300.0 * $dif, $blue);

    // Выводим изображение
    imagepng($im, $filename);
}
<?php
// Utilidades compartidas por seed.php y ctl.php: dibujo de las fotografías ficticias con GD.
// Todas las imágenes son ilustraciones generadas aquí mismo; no hay fotografías de animales reales.
declare(strict_types=1);

/** Paletas de pelaje: [pelaje, sombra, fondo superior, fondo inferior]. */
const DEMO_PALETTES = [
    [[196, 142, 82], [150, 100, 56], [255, 236, 200], [248, 200, 150]],
    [[88, 88, 96], [56, 56, 64], [214, 230, 246], [160, 190, 226]],
    [[240, 234, 224], [200, 190, 176], [255, 226, 232], [244, 170, 190]],
    [[60, 48, 44], [34, 26, 24], [226, 244, 226], [150, 210, 160]],
    [[222, 164, 96], [178, 120, 62], [236, 226, 255], [190, 170, 240]],
    [[170, 170, 170], [120, 120, 126], [255, 244, 204], [250, 214, 120]],
];

/**
 * Devuelve el contenido PNG de una ilustración de perro o gato (800x600).
 *
 * @param string $species 'dog' o 'cat'
 * @param int    $variant número que elige paleta y detalles; la misma pareja da siempre la misma imagen
 */
function demoPetPng(string $species, int $variant): string
{
    $w = 800; $h = 600;
    $palette = DEMO_PALETTES[$variant % count(DEMO_PALETTES)];
    $im = imagecreatetruecolor($w, $h);
    imageantialias($im, true);
    // Fondo en degradado vertical.
    for ($y = 0; $y < $h; $y++) {
        $t = $y / $h;
        $c = [];
        for ($i = 0; $i < 3; $i++) { $c[] = (int) round($palette[2][$i] * (1 - $t) + $palette[3][$i] * $t); }
        imageline($im, 0, $y, $w, $y, imagecolorallocate($im, ...$c));
    }
    $fur = imagecolorallocate($im, ...$palette[0]);
    $shade = imagecolorallocate($im, ...$palette[1]);
    $white = imagecolorallocate($im, 252, 250, 246);
    $dark = imagecolorallocate($im, 38, 30, 30);
    $pink = imagecolorallocate($im, 232, 130, 140);
    $ground = imagecolorallocate($im, max(0, $palette[3][0] - 40), max(0, $palette[3][1] - 40), max(0, $palette[3][2] - 40));
    imagefilledellipse($im, 400, 560, 640, 120, $ground);

    // Cuerpo y patas.
    imagefilledellipse($im, 400, 440, 360, 270, $fur);
    imagefilledellipse($im, 330, 545, 90, 56, $shade);
    imagefilledellipse($im, 470, 545, 90, 56, $shade);
    imagefilledellipse($im, 400, 470, 170, 190, $white);

    // Cola.
    for ($a = -80; $a <= 50; $a += 4) {
        $rad = deg2rad($a);
        imagefilledellipse($im, (int) (560 + 95 * cos($rad)), (int) (440 + 105 * sin($rad)), 26, 26, $shade);
    }

    // Cabeza.
    $hx = 400; $hy = 230;
    if ($species === 'cat') {
        imagefilledpolygon($im, [290, 190, 300, 80, 380, 140], $fur);
        imagefilledpolygon($im, [510, 190, 500, 80, 420, 140], $fur);
        imagefilledpolygon($im, [310, 170, 312, 112, 360, 146], $pink);
        imagefilledpolygon($im, [490, 170, 488, 112, 440, 146], $pink);
    } else {
        imagefilledellipse($im, 285, 245, 90, 170, $shade);
        imagefilledellipse($im, 515, 245, 90, 170, $shade);
    }
    imagefilledellipse($im, $hx, $hy, 260, 230, $fur);
    imagefilledellipse($im, $hx, $hy + 55, 150, 110, $white);
    if ($variant % 2 === 0) { imagefilledellipse($im, $hx - 60, $hy - 40, 70, 80, $shade); }

    // Ojos, nariz, boca.
    foreach ([-52, 52] as $dx) {
        imagefilledellipse($im, $hx + $dx, $hy - 12, 44, 48, $white);
        imagefilledellipse($im, $hx + $dx, $hy - 8, 26, 30, $dark);
        imagefilledellipse($im, $hx + $dx + 6, $hy - 16, 9, 9, $white);
    }
    imagefilledpolygon($im, [$hx - 20, $hy + 28, $hx + 20, $hy + 28, $hx, $hy + 52], $species === 'cat' ? $pink : $dark);
    imagesetthickness($im, 4);
    imagearc($im, $hx - 16, $hy + 54, 32, 30, 20, 160, $dark);
    imagearc($im, $hx + 16, $hy + 54, 32, 30, 20, 160, $dark);
    imagesetthickness($im, 1);
    if ($species === 'dog' && $variant % 3 !== 0) { imagefilledellipse($im, $hx, $hy + 82, 40, 40, $pink); }
    if ($species === 'cat') {
        imagesetthickness($im, 3);
        foreach ([-1, 1] as $s) {
            foreach ([-8, 6, 20] as $dy) { imageline($im, $hx + $s * 40, $hy + 44 + $dy / 2, $hx + $s * 130, $hy + 40 + $dy * 2, $dark); }
        }
        imagesetthickness($im, 1);
    }

    ob_start();
    imagepng($im, null, 7);
    $png = (string) ob_get_clean();
    imagedestroy($im);
    return $png;
}

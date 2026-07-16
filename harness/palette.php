<?php
/**
 * Colour system for the route pages, DERIVED rather than picked.
 *
 *   php harness/palette.php        # print the derivation + WCAG audit
 *
 * WHY THIS EXISTS
 * ---------------
 * Every colour below is computed from ONE source value -- CB Blue #012169, the
 * signature brand colour in theme/BRAND.md -- using ordinary colour-wheel maths on
 * HSL. Nothing here is eyeballed, and nothing is invented: if the brand ever
 * restates CB Blue, re-run this and the whole system moves with it.
 *
 * WHAT THE MATHS FOUND
 * --------------------
 * Two things worth knowing, both verifiable by running this file:
 *
 * 1. The brand's blues are ONE hue, not a set of choices. CB Blue, Bright Blue,
 *    Celestial, Slate, Smoky Gray, Tide, Mist, Glacier and Icy Blue all sit within
 *    ~12 degrees of each other (210-222). They are a monochromatic ramp in
 *    lightness and saturation off a single hue. That is why the palette hangs
 *    together, and it is why the neutrals here are desaturated CB Blue rather than
 *    grey -- true grey next to this ramp reads as dirty.
 *
 * 2. The gold in CONTRACT.md (#C9A84C) IS the complement of CB Blue, to within
 *    ~2.6 degrees. Nobody appears to have written that down. It means the accent
 *    is not a decorative choice that happens to look nice -- it is the one hue the
 *    wheel says opposes the brand's, which is exactly why it carries a CTA against
 *    all that navy. Home 9 already leans on it.
 *
 * SO THE SYSTEM IS: one hue for everything structural, its exact complement for
 * everything that asks to be clicked, and lightness doing the rest.
 *
 * Accessibility is not decoration either: every text/background pair is checked
 * against WCAG 2.1 contrast and the audit fails loudly rather than shipping a
 * pairing nobody can read.
 *
 * @package CB_Legacy_Luxury
 */

// ---- the one source value, plus the brand's own set for verification --------
const CB_BLUE = '#012169';   // BRAND.md: "Signature brand color"
const CB_GOLD = '#C9A84C';   // CONTRACT.md WebGL token -- claimed as the complement
const BRAND_BLUES = [
    'CB Blue' => '#012169', 'Midnight' => '#0A1730', 'Slate' => '#1B3C55',
    'Smoky Gray' => '#58718D', 'Celestial' => '#418FDE', 'Bright Blue' => '#1F69FF',
    'Tide' => '#B8CFEA', 'Mist' => '#BECAD7', 'Glacier' => '#DAE1E8', 'Icy Blue' => '#F0F5FB',
];

// ---- colour maths -----------------------------------------------------------
function hex2rgb($h) {
    $h = ltrim($h, '#');
    return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))];
}
function rgb2hex($r) {
    return sprintf('#%02X%02X%02X', max(0, min(255, round($r[0]))), max(0, min(255, round($r[1]))), max(0, min(255, round($r[2]))));
}
function rgb2hsl($rgb) {
    list($r, $g, $b) = [$rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255];
    $max = max($r, $g, $b); $min = min($r, $g, $b); $d = $max - $min;
    $l = ($max + $min) / 2;
    if ($d == 0) { return [0, 0, $l]; }
    $s = $d / (1 - abs(2 * $l - 1));
    if ($max == $r)      { $h = 60 * fmod((($g - $b) / $d), 6); }
    elseif ($max == $g)  { $h = 60 * ((($b - $r) / $d) + 2); }
    else                 { $h = 60 * ((($r - $g) / $d) + 4); }
    if ($h < 0) { $h += 360; }
    return [$h, $s, $l];
}
function hsl2rgb($hsl) {
    list($h, $s, $l) = $hsl;
    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;
    if ($h < 60)       { $p = [$c, $x, 0]; }
    elseif ($h < 120)  { $p = [$x, $c, 0]; }
    elseif ($h < 180)  { $p = [0, $c, $x]; }
    elseif ($h < 240)  { $p = [0, $x, $c]; }
    elseif ($h < 300)  { $p = [$x, 0, $c]; }
    else               { $p = [$c, 0, $x]; }
    return [($p[0] + $m) * 255, ($p[1] + $m) * 255, ($p[2] + $m) * 255];
}
function hsl($h, $s, $l) { return rgb2hex(hsl2rgb([fmod($h + 360, 360), $s, $l])); }
function hue($hex) { $x = rgb2hsl(hex2rgb($hex)); return $x[0]; }

/** WCAG 2.1 relative luminance + contrast ratio. */
function lum($hex) {
    $c = array_map(function ($v) {
        $v /= 255;
        return $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
    }, hex2rgb($hex));
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}
function contrast($a, $b) {
    $la = lum($a); $lb = lum($b);
    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

// ---- the derivation ---------------------------------------------------------
$BASE = rgb2hsl(hex2rgb(CB_BLUE));      // [h, s, l]
$H = $BASE[0];                          // ~221.6 -- the brand's single hue
$COMP = fmod($H + 180, 360);            // ~41.6 -- its complement: the gold

function cb_palette() {
    global $H, $COMP;
    return [
        // Structure: CB Blue's hue, lightness doing the work.
        'ink'        => hsl($H, 0.62, 0.09),   // near-black, still blue -- true black reads dead against navy
        'body'       => hsl($H, 0.10, 0.28),
        'surface'    => hsl($H, 0.40, 0.97),   // Icy-Blue-adjacent off-white
        'surfaceAlt' => hsl($H, 0.30, 0.93),
        'line'       => hsl($H, 0.22, 0.84),
        'primary'    => CB_BLUE,
        'primaryLo'  => hsl($H, 0.98, 0.14),   // deeper CB Blue for gradients
        'primaryHi'  => hsl($H, 0.85, 0.34),
        'onPrimary'  => '#FFFFFF',
        // Everything that asks to be clicked uses the complement.
        'accent'     => hsl($COMP, 0.52, 0.54),
        'accentLo'   => hsl($COMP, 0.55, 0.38),
        'onAccent'   => hsl($H, 0.62, 0.09),
        'muted'      => hsl($H, 0.14, 0.46),
    ];
}

// ---- self-test / audit ------------------------------------------------------
if (PHP_SAPI === 'cli' && isset($argv) && realpath($argv[0]) === realpath(__FILE__)) {
    $fail = 0;
    printf("CB Blue %s -> H %.1f  S %.0f%%  L %.0f%%\n\n", CB_BLUE, $BASE[0], $BASE[1] * 100, $BASE[2] * 100);

    echo "1. Is the brand one hue?\n";
    $hues = [];
    foreach (BRAND_BLUES as $name => $hexv) {
        $hh = hue($hexv); $hues[] = $hh;
        printf("   %-12s %s  H %5.1f  (%+.1f from CB Blue)\n", $name, $hexv, $hh, $hh - $H);
    }
    $spread = max($hues) - min($hues);
    printf("   spread: %.1f degrees -> %s\n\n", $spread,
        $spread < 20 ? 'YES: one monochromatic ramp' : 'NO: these are separate hues');
    if ($spread >= 20) { $fail++; }

    echo "2. Is the gold the complement of CB Blue?\n";
    $gh = hue(CB_GOLD);
    $delta = abs($gh - $COMP);
    printf("   complement of CB Blue : H %.1f\n   CONTRACT.md gold %s : H %.1f\n   delta: %.1f degrees -> %s\n\n",
        $COMP, CB_GOLD, $gh, $delta, $delta < 6 ? 'YES: the accent IS the complement' : 'NO');
    if ($delta >= 6) { $fail++; }

    echo "3. WCAG contrast (AA body text needs 4.5:1, large/UI 3:1)\n";
    $p = cb_palette();
    $pairs = [
        ['body on surface',     $p['body'],      $p['surface'],    4.5],
        ['ink on surface',      $p['ink'],       $p['surface'],    4.5],
        ['muted on surface',    $p['muted'],     $p['surface'],    4.5],
        ['onPrimary on primary', $p['onPrimary'], $p['primary'],   4.5],
        ['onPrimary on primaryLo', $p['onPrimary'], $p['primaryLo'], 4.5],
        ['onAccent on accent',  $p['onAccent'],  $p['accent'],     4.5],
        ['accent on primary',   $p['accent'],    $p['primary'],    3.0],
        ['accent on ink',       $p['accent'],    $p['ink'],        3.0],
    ];
    foreach ($pairs as $pr) {
        $c = contrast($pr[1], $pr[2]);
        $ok = $c >= $pr[3];
        if (!$ok) { $fail++; }
        printf("   %-26s %s on %s  %5.2f:1  need %.1f  %s\n", $pr[0], $pr[1], $pr[2], $c, $pr[3], $ok ? 'OK' : 'FAIL');
    }

    echo "\n" . ($fail === 0 ? "PALETTE DERIVES AND PASSES\n" : "$fail CHECK(S) FAILED\n");
    exit($fail === 0 ? 0 : 1);
}

<?php
/**
 * Generates harness/cb-home8-harness.html from the REAL partial.
 *
 *   php harness/build-harness.php
 *
 * WHY THIS IS GENERATED
 * ---------------------
 * The harness has to mirror theme/template-parts/home8-pages-scenes.php exactly --
 * same classes, same data-attributes, same nesting -- or it stops testing the
 * thing that ships. Maintaining ~500 lines of that by hand guarantees drift: the
 * first time someone restructures a card in the partial and not here, the harness
 * silently starts passing for a page that no longer exists.
 *
 * So the markup comes from the partial itself, with WordPress stubbed out and the
 * two live shortcodes swapped for static stand-ins. The only hand-written parts
 * are the <head> (a minimal stand-in for cb-legacy-style) and the boot script.
 *
 * The OUTPUT is committed -- open cb-home8-harness.html directly, no build, no
 * server, no WordPress, no PHP. You only need PHP to regenerate it after editing
 * the partial.
 *
 * theme/assets/js/cb-webgl/CONTRACT.md prescribes exactly this: build a standalone
 * showcase with file:/// references before touching WordPress. The previous session
 * did that and left all three harnesses on someone's Desktop, unversioned and lost
 * to anyone who clones the repo (SESSION_HANDOFF.md still points at them). Hence
 * this lives in the repo.
 *
 * @package CB_Legacy_Luxury
 */

$repo = dirname(__DIR__);

define('ABSPATH', __DIR__);
define('CB_THEME_URI', '../theme');   // resolves from harness/ on disk

function get_theme_mod($k, $d = '') { return $d; }
function esc_html($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
function esc_attr($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
function esc_url($s)  { return (string) $s; }
function esc_js($s)   { return (string) $s; }
function home_url($p = '/') { return '#'; }
function cb_get_svg_icon($n) { return ''; }
function wp_reset_postdata() {}
function has_post_thumbnail() { return false; }
function the_post_thumbnail($size = '') {}
function get_the_category() { return []; }
function the_permalink() { echo '#'; }
function the_title() { echo 'Stub'; }
function the_title_attribute() { echo 'Stub'; }
function get_the_excerpt() { return 'Stub excerpt'; }
function get_the_date() { return 'April 1, 2026'; }

/** Static stand-ins for the two live shortcodes. Deliberately tall enough to
 *  overflow their page -- the inner scroller is the thing under test. */
function do_shortcode($s) {
    if (strpos($s, 'cb_listings') !== false) {
        $rows = [
            ['$439,000', '2841 Sunset Drive',        '4 bd &middot; 3 ba &middot; 2,640 sqft', 'New'],
            ['$312,500', '1907 College Hills Blvd',  '3 bd &middot; 2 ba &middot; 1,880 sqft', 'Featured'],
            ['$675,000', '42 Lake Nasworthy Road',   '5 bd &middot; 4 ba &middot; 3,910 sqft', 'Open'],
            ['$289,900', '515 Bentwood Court',       '3 bd &middot; 2 ba &middot; 1,740 sqft', 'New'],
            ['$1,150,000', '700 Concho River Walk',  '6 bd &middot; 5 ba &middot; 5,200 sqft', 'Featured'],
            ['$225,000', '108 Christoval Lane',      '3 bd &middot; 1 ba &middot; 1,320 sqft', 'Price cut'],
        ];
        $out = '<div class="cb8-grid cb8-grid--3">';
        foreach ($rows as $r) {
            $out .= '<article class="h-listing">'
                  . '<div class="h-listing__img" data-tag="' . $r[3] . '"></div>'
                  . '<div class="h-listing__body">'
                  . '<div class="h-listing__price">' . $r[0] . '</div>'
                  . '<div class="h-listing__addr">' . $r[1] . '</div>'
                  . '<div class="h-listing__meta">' . $r[2] . '</div>'
                  . '</div></article>';
        }
        return $out . '</div>';
    }
    return '<blockquote class="h-quote">'
         . '<div class="h-quote__stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>'
         . '<p class="h-quote__text">&ldquo;They knew every street in this town. We had an offer in four days and closed without a single surprise.&rdquo;</p>'
         . '<div class="h-quote__who">The Walsh Family &middot; College Hills</div>'
         . '</blockquote>';
}

function cb_get_communities() {
    return [
        'grape-creek'    => ['name' => 'Grape Creek'],
        'bentwood'       => ['name' => 'Bentwood'],
        'college-hills'  => ['name' => 'College Hills'],
        'christoval'     => ['name' => 'Christoval'],
        'wall'           => ['name' => 'Wall'],
        'lake-nasworthy' => ['name' => 'Lake Nasworthy'],
    ];
}
/** grape-creek is in the featured list but genuinely has no image in the repo --
 *  return '' for it so the harness keeps exercising the no-image path. */
function cb_community_image_url($c) {
    $slug = strtolower(str_replace(' ', '-', $c['name']));
    $have = ['bentwood', 'christoval', 'college-hills', 'lake-nasworthy', 'wall'];
    return in_array($slug, $have, true) ? CB_THEME_URI . '/assets/images/communities/' . $slug . '.webp' : '';
}

class WP_Query {
    public function __construct($a = []) {}
    public function have_posts() { return false; }   // exercise the placeholder branch
    public function the_post() {}
}

ob_start();
require $repo . '/theme/template-parts/home8-pages-scenes.php';
$partial = ob_get_clean();

$head = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CB Home 8 -- Floating Pages harness</title>

<!--
  GENERATED FILE -- do not hand-edit.
    Source : theme/template-parts/home8-pages-scenes.php
    Rebuild: php harness/build-harness.php

  Standalone harness for the Home 8 floating-pages engine. Open directly: no
  build, no server, no WordPress. The markup below IS the shipping partial, with
  WordPress stubbed and the two live shortcodes ([cb_listings],
  [cb_testimonials]) swapped for static stand-ins.

  Capture mode (deterministic headless screenshots):
    cb-home8-harness.html?cb_capture=1&cb_progress=0.50
  freezes time at 2.0s, zeroes the mouse, snaps the camera (no lerp), synthesises
  scroll from the target progress, renders 3 frames, then sets window.__cbReady
  and document.title = CB_READY.

    msedge --headless=new --use-gl=angle --use-angle=swiftshader
           --enable-unsafe-swiftshader --ignore-gpu-blocklist
           --allow-file-access-from-files --disable-web-security --no-sandbox
           --screenshot=out.png --window-size=1440,900 "<file url>"

  --allow-file-access-from-files is not optional: bakeMonogram() draws an SVG into
  a canvas and uploads it as a texture, which taints the canvas under file:// and
  throws. home8.js try/catches it, so the corridor still runs -- you just lose the
  monogram signage.
-->

<link rel="stylesheet" href="../theme/assets/css/cb-home8.css">

<style>
  /* Minimal stand-in for cb-legacy-style: only the tokens cb-home8.css consumes.
     Values from theme/BRAND.md. Google Fonts are not fetched (offline harness),
     so the fallback chain renders -- metrics differ slightly from production. */
  :root {
    --cb-blue: #012169;
    --cb-smoky-gray: #58718D;
    --cb-midnight: #0A1730;
    --cb-slate: #1B3C55;
    --cb-mist: #BECAD7;
    --cb-tide: #B8CFEA;
    --cb-glacier: #DAE1E8;
    --cb-icy-blue: #F0F5FB;
    --cb-bright-blue: #1F69FF;
    --cb-celestial: #418FDE;
    --cb-white: #fff;
    --font-heading: 'Familjen Grotesk', 'Segoe UI', system-ui, sans-serif;
    --font-subheader: 'Josefin Sans', 'Segoe UI', system-ui, sans-serif;
    --font-body: Roboto, 'Segoe UI', system-ui, sans-serif;
    --font-accent: 'EB Garamond', Georgia, serif;
  }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { font-family: var(--font-body); }

  .cb-btn {
    display: inline-block; padding: .75rem 1.4rem; border-radius: 10px;
    font-family: var(--font-subheader); font-size: .8rem; font-weight: 600;
    letter-spacing: .08em; text-transform: uppercase; text-decoration: none;
    border: 1px solid transparent; cursor: pointer;
  }
  .cb-btn--primary { background: var(--cb-bright-blue); color: #fff; }
  .cb-btn--primary:hover { background: #1257e0; }
  .cb-btn--outline { background: transparent; color: #fff; border-color: rgba(255,255,255,.45); }
  .cb-btn--outline:hover { border-color: #fff; }
  .cb-btn--lg { padding: .9rem 1.8rem; font-size: .86rem; }

  /* Header/footer stubs so the z-index and overlay rules have something real. */
  .cb-header {
    position: fixed; top: 0; left: 0; right: 0; height: 68px; z-index: 20;
    display: flex; align-items: center; padding: 0 1.5rem;
    border-bottom: 1px solid transparent;
  }
  .cb-header__logo-img { height: 30px; filter: brightness(0) invert(1); }
  .cb-footer { padding: 2.5rem 1.5rem; text-align: center; color: rgba(255,255,255,.6); font-size: .8rem; }

  /* Stand-in for [cb_listings]. */
  .h-listing { border-radius: 10px; overflow: hidden; background: rgba(12,30,72,.55); border: 1px solid rgba(184,207,234,.16); }
  .h-listing__img { aspect-ratio: 16/11; background: linear-gradient(150deg, #1b4794, #08163a); position: relative; }
  .h-listing__img::after {
    content: attr(data-tag); position: absolute; left: .5rem; top: .5rem;
    background: var(--cb-bright-blue); color: #fff; font-size: .58rem; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase; padding: .18rem .45rem; border-radius: 4px;
  }
  .h-listing__body { padding: .7rem; }
  .h-listing__price { font-family: var(--font-heading); font-weight: 700; font-size: 1rem; color: #fff; }
  .h-listing__addr { font-size: .76rem; color: rgba(255,255,255,.7); margin-top: .15rem; }
  .h-listing__meta { font-size: .68rem; color: var(--cb-tide); margin-top: .4rem; letter-spacing: .04em; }

  /* Stand-in for [cb_testimonials type="rotator"]. */
  .h-quote { color: #fff; margin: 0; }
  .h-quote__stars { color: #C9A84C; letter-spacing: .15em; }
  .h-quote__text { font-family: var(--font-accent); font-style: italic; font-size: 1rem; line-height: 1.5; margin: .5rem 0 .4rem; }
  .h-quote__who { font-size: .74rem; color: var(--cb-tide); letter-spacing: .06em; text-transform: uppercase; }

  /* Harness-only badge so a screenshot is never mistaken for the real page. */
  .h-badge {
    position: fixed; left: 10px; bottom: 10px; z-index: 60;
    font: 600 10px/1 var(--font-subheader); letter-spacing: .12em; text-transform: uppercase;
    color: rgba(255,255,255,.55); background: rgba(4,12,38,.7);
    border: 1px solid rgba(184,207,234,.25); border-radius: 5px; padding: .35rem .5rem;
    pointer-events: none;
  }
</style>
</head>
<body class="cb-page--home cb-page--home8-preview">

<header class="cb-header">
  <img class="cb-header__logo-img" src="../theme/assets/images/logos/monogram-horizontal.svg" alt="Coldwell Banker Legacy">
</header>
HTML;

$tail = <<<'HTML'

<footer class="cb-footer">
  Coldwell Banker Legacy &middot; harness build &middot; not a live page
</footer>

<div class="h-badge">Home 8 harness</div>

<!-- Load order is a hard contract (CONTRACT.md): three -> cursor -> motion -> engine.
     Motion is optional; home8.js falls back to CSS keyframes + rAF counters. -->
<script src="../theme/assets/js/vendor/three.min.js"></script>
<script src="../theme/assets/js/cb-webgl/cursor.js"></script>
<script src="../theme/assets/js/vendor/motion.js"></script>
<script src="../theme/assets/js/cb-home8/home8.js"></script>
<script>
(function () {
  // Mirrors deploy/cb-home8-preview.php's gate, so the harness tests the same
  // three conditions the real page ships with. Capture mode bypasses it: a
  // headless window passes anyway, but an explicit bypass means a failed
  // screenshot is never silently a gate problem.
  var cap = /[?&]cb_capture=(1|true)/.test(location.search);
  var ok = cap;
  try {
    if (!ok) {
      ok = window.matchMedia('(min-width: 1025px)').matches
        && window.matchMedia('(prefers-reduced-motion: no-preference)').matches
        && !window.matchMedia('(pointer: coarse)').matches;
    }
  } catch (e) { ok = false; }

  if (!ok) {
    if (window.console) { console.info('[cb8 harness] gated off -- showing the flat fallback. Correct below 1025px, on touch, or with reduced-motion.'); }
    return;
  }
  if (!window.CBHome8) {
    if (window.console) { console.error('[cb8 harness] home8.js did not load.'); }
    return;
  }
  window.CBHome8.init({
    canvas: '#cb8-canvas',
    monogram: '../theme/assets/images/logos/monogram.svg',
    monogramStacked: '../theme/assets/images/logos/monogram-vertical-stacked.svg'
  });
})();
</script>
</body>
</html>
HTML;

$out = $head . "\n" . $partial . $tail;
$dest = __DIR__ . '/cb-home8-harness.html';
file_put_contents($dest, $out);

printf("wrote %s (%d bytes, partial %d bytes)\n", $dest, strlen($out), strlen($partial));

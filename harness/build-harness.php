<?php
/**
 * Generates a standalone harness from the REAL partial, for either variant.
 *
 *   php harness/build-harness.php          # Home 8 (blueprint) -- the default
 *   php harness/build-harness.php 9        # Home 9 (the house)
 *   php harness/build-harness.php all      # both
 *
 * Home 8 and Home 9 are the same walk through two different worlds, so they share
 * one generator rather than two that drift apart. Everything that differs between
 * them lives in $VARIANTS below; if you find yourself adding an `if ($n === 9)`
 * anywhere else, it probably belongs there instead.
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

// The route pages' colour system, derived from CB Blue by colour-wheel maths.
require_once __DIR__ . '/palette.php';

/**
 * Everything that differs between the two variants. The walk, the pages, the
 * cards and the content are identical -- only the world and its palette change.
 */
$VARIANTS = [
    8 => [
        'ns'      => 'cb8',
        'partial' => 'theme/template-parts/home8-pages-scenes.php',
        'css'     => '../theme/assets/css/cb-home8.css',
        'engine'  => '../theme/assets/js/cb-home8/home8.js',
        'global'  => 'CBHome8',
        'out'     => 'cb-home8-harness.html',
        'title'   => 'CB Home 8 -- Floating Pages harness',
        'badge'   => 'Home 8 harness',
        'body'    => 'cb-page--home cb-page--home8-preview',
        // Home 8's brand tokens are the cool set; the page field is navy.
        'field'   => 'radial-gradient(130% 115% at 50% -5%, #16409c 0%, #0c2a72 30%, #071c52 58%, #040c2c 100%) fixed',
        'ink'     => '#fff',
        'accent'  => 'var(--cb-bright-blue)',
        'basePath' => null,
    ],
    9 => [
        'ns'      => 'cb9',
        'partial' => 'theme/template-parts/home9-house-scenes.php',
        'css'     => '../theme/assets/css/cb-home9.css',
        'engine'  => '../theme/assets/js/cb-home9/home9.js',
        'global'  => 'CBHome9',
        'out'     => 'cb-home9-harness.html',
        'title'   => 'CB Home 9 -- The House harness',
        'badge'   => 'Home 9 harness',
        'body'    => 'cb-page--home cb-page--home9-preview',
        // Must match home9.js's fog (0x20140c) -- see cb-home9.css.
        'field'   => 'radial-gradient(130% 115% at 50% -8%, #3a2517 0%, #2b1a10 34%, #20140c 62%, #150c07 100%) fixed',
        'ink'     => 'var(--cb-cream)',
        'accent'  => 'var(--cb-gold)',
        // Home 9 hangs Home 2's plates as framed art on the room walls.
        'basePath' => '../theme/assets/images/webgl/',
    ],
];

$arg = isset($argv[1]) ? strtolower($argv[1]) : '8';
$which = ($arg === 'all') ? [8, 9] : [(int) $arg];
foreach ($which as $n) {
    if (!isset($VARIANTS[$n])) { fwrite(STDERR, "unknown variant: $n\n"); exit(1); }
}

define('ABSPATH', __DIR__);
define('CB_THEME_URI', '../theme');   // resolves from harness/ on disk

function get_theme_mod($k, $d = '') { return $d; }
function esc_html($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
function esc_attr($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
function esc_url($s)  { return (string) $s; }
function esc_js($s)   { return (string) $s; }
/**
 * Where the links go.
 *
 * These destinations ALREADY EXIST as real theme templates -- the theme ships
 * template-find-home.php, template-home-value.php, template-agents.php,
 * template-contact.php, template-testimonials.php, template-community.php and
 * single-cb_*.php. In WordPress, a Page is created per route and assigned the
 * template, and the links resolve. Nothing about that needed building.
 *
 * What does not work is the HARNESS: it is static, so home_url() had nowhere to
 * point and every href collapsed to '#'. Clicking did nothing, which reads as
 * broken even though the markup is correct. So each route gets a generated
 * stand-in page that names the template that really serves it, and links back.
 * They are previews of the ROUTING, not of the pages -- the real ones need WP.
 */
/*
 * `h1` and `sections` are lifted from the templates THEMSELVES, not invented and
 * not taken from coldwellbanker.com. Each route's real template is right there in
 * the repo, so its own headings are the accurate answer to "what is on this page"
 * -- and copying the national site's copy would be reproducing someone else's
 * content into a brokerage's own build.
 */
$ROUTES = [
    '/find-a-home/' => [
        'file' => 'find-a-home', 'title' => 'Find a Home', 'tpl' => 'templates/template-find-home.php',
        'h1' => 'Find a Home', 'kicker' => 'San Angelo MLS Search',
        'blurb' => 'Search the live San Angelo MLS &mdash; filtered by neighbourhood, price and beds, with map view.',
        'sections' => ['Your Search Advantage', 'Can&rsquo;t Find What You&rsquo;re Looking For?'],
        'live' => 'Builds a live Spark <code>_filter</code> expression from the query string and scopes it to San Angelo and the Concho Valley.',
    ],
    '/home-value/' => [
        'file' => 'home-value', 'title' => 'Home Value', 'tpl' => 'templates/template-home-value.php',
        'h1' => 'What Is Your Home Worth?', 'kicker' => 'For Sellers',
        'blurb' => 'A free, no-obligation valuation grounded in live San Angelo market data.',
        'sections' => ['Get a Free CB Estimate Now', 'Want a More Accurate Estimate?', 'Current Market Snapshot'],
        'live' => 'Pulls the market snapshot from <code>[cb_market_stats]</code>.',
    ],
    '/our-team/' => [
        'file' => 'our-team', 'title' => 'Our Team', 'tpl' => 'templates/template-agents.php',
        'h1' => 'Meet Our Team', 'kicker' => 'Coldwell Banker Legacy',
        'blurb' => 'The agents who know San Angelo inside and out.',
        'sections' => ['Ready to Get Started?'],
        'live' => 'Each agent deepens into <code>single-cb_agent.php</code>.',
    ],
    '/office/' => [
        'file' => 'office', 'title' => 'Contact', 'tpl' => 'templates/template-contact.php',
        'h1' => 'Contact Us', 'kicker' => 'Knickerbocker Road',
        'blurb' => 'Directions, hours, and how to reach us.',
        'sections' => ['Send Us a Message'],
        'live' => '',
    ],
    '/communities/' => [
        'file' => 'communities', 'title' => 'Communities', 'tpl' => 'template-community.php',
        'h1' => 'Explore the Area', 'kicker' => 'Concho Valley',
        'blurb' => 'From downtown San Angelo to the shores of Lake Nasworthy.',
        'sections' => ['Featured Communities'],
        'live' => 'Each community deepens into <code>single-cb_community.php</code>, scoped by its own MLS expression.',
    ],
    '/testimonials/' => [
        'file' => 'testimonials', 'title' => 'Client Stories', 'tpl' => 'templates/template-testimonials.php',
        'h1' => 'What Our Clients Say', 'kicker' => 'Client Stories',
        'blurb' => 'Verified reviews from Coldwell Banker Legacy San Angelo clients.',
        'sections' => ['Ready to Work With Us?'],
        'live' => 'Rendered by <code>[cb_testimonials type="list"]</code> via Testimonial Tree.',
    ],
    '/blog/' => [
        'file' => 'blog', 'title' => 'Blog', 'tpl' => 'index.php',
        'h1' => 'Local Insight &amp; Market News', 'kicker' => 'From Our Blog',
        'blurb' => 'Market reports, buying guides, and news from the Concho Valley.',
        'sections' => ['Latest Posts'],
        'live' => '',
    ],
];

/**
 * The harness links point at the REAL, LIVE pages.
 *
 * homes-sanangelo.com is live, and its routes are exactly the ones this partial
 * already asks for -- /find-a-home/, /home-value/, /our-team/, /office/,
 * /communities/{slug}/, /blog/ -- so home_url() only has to become absolute.
 * In WordPress this function is WordPress's own and already resolves correctly;
 * only the static harness needed a base, because file:// has no site root.
 *
 * Note this is the brokerage's OWN site, not coldwellbanker.com. Pointing a
 * brokerage's nav at the franchisor would hand every "Find a Home" click to the
 * national portal, and the lead with it -- while template-find-home.php's live
 * Spark MLS search sat unused.
 */
const LIVE_BASE = 'http://homes-sanangelo.com';

function home_url($p = '/') {
    return LIVE_BASE . $p;
}
function cb_get_svg_icon($n) { return ''; }
function wp_reset_postdata() {}

/**
 * Blog posts, stubbed WITH featured images.
 *
 * The obvious stub -- have_posts() false -- exercises the no-thumbnail branch, but
 * it also means the harness renders three grey gradient boxes where production
 * renders three photographs. That is not what the page looks like, so it is not
 * worth previewing. These are the CC-licensed San Angelo landmark photos already
 * in the repo, credited by the .cb8-credits block the partial emits.
 *
 * The gradient fallback still ships and is still correct -- it is just not the
 * common case, so the harness should not present it as one.
 */
$GLOBALS['h_posts'] = [
    ['title' => '12 San Angelo Secrets Only Locals Know',      'cat' => 'Community',     'date' => 'April 10, 2026', 'img' => 'alt-fortconcho.jpg'],
    ['title' => 'Spring 2026 San Angelo Market Report',        'cat' => 'Market Update', 'date' => 'April 5, 2026',  'img' => 'alt-river-41.jpg'],
    ['title' => 'First-Time Home Buyer Guide for West Texas',  'cat' => 'Buying Tips',   'date' => 'March 28, 2026', 'img' => 'alt-waterlily.jpg'],
];
$GLOBALS['h_i'] = -1;
function h_post() { return $GLOBALS['h_posts'][max(0, $GLOBALS['h_i'])]; }

/**
 * A post's link.
 *
 * This echoed '#', which is worse than useless in a scroll-driven walkthrough:
 * '#' navigates to the top of the DOCUMENT, scroll goes to 0, and the camera
 * walks all the way back to the threshold. Clicking a blog headline in the last
 * room sent you back to the start of the house.
 *
 * These three posts are harness fixtures, not real posts, so there is no real
 * permalink to point at -- the blog index is the honest destination. In WordPress
 * the_permalink() is WordPress's own function and already returns the real post
 * URL; only this stub was ever wrong.
 */
function the_permalink() { echo home_url('/blog/'); }

function has_post_thumbnail() { return true; }
function the_post_thumbnail($size = '') {
    $p = h_post();
    printf(
        '<img src="%s/assets/images/scroll/_src/%s" alt="%s" decoding="async">',
        CB_THEME_URI, $p['img'], htmlspecialchars($p['title'], ENT_QUOTES)
    );
}
function get_the_category() { $c = new stdClass(); $c->name = h_post()['cat']; return [$c]; }
function the_title() { echo htmlspecialchars(h_post()['title'], ENT_QUOTES); }
function the_title_attribute() { the_title(); }
function get_the_excerpt() { return 'Discover the latest insights about San Angelo real estate and community living.'; }
function get_the_date() { return h_post()['date']; }

/**
 * Static stand-ins for the two live shortcodes. Deliberately tall enough to
 * overflow their page -- the inner scroller is the thing under test.
 *
 * The listing photos are the four unused home shots sitting in
 * assets/images/scroll/_src/ (home-clayton/collyns/mason/walsh), plus two CC
 * landmark stills. They stand in for [cb_listings]' live MLS photography, which
 * only exists inside WordPress. Gradient boxes made the harness look like a
 * wireframe of itself, which is not what needed previewing.
 */
function do_shortcode($s) {
    if (strpos($s, 'cb_listings') !== false) {
        $rows = [
            ['$439,000',   '2841 Sunset Drive',       '4 bd &middot; 3 ba &middot; 2,640 sqft', 'New',       'home-walsh.jpg'],
            ['$312,500',   '1907 College Hills Blvd', '3 bd &middot; 2 ba &middot; 1,880 sqft', 'Featured',  'home-collyns.jpg'],
            ['$675,000',   '42 Lake Nasworthy Road',  '5 bd &middot; 4 ba &middot; 3,910 sqft', 'Open',      'home-mason.jpg'],
            ['$289,900',   '515 Bentwood Court',      '3 bd &middot; 2 ba &middot; 1,740 sqft', 'New',       'home-clayton.jpg'],
            ['$1,150,000', '700 Concho River Walk',   '6 bd &middot; 5 ba &middot; 5,200 sqft', 'Featured',  'alt-river-42.jpg'],
            ['$225,000',   '108 Christoval Lane',     '3 bd &middot; 1 ba &middot; 1,320 sqft', 'Price cut', 'alt-fortconcho.jpg'],
        ];
        $out = '<div class="' . $GLOBALS['NS'] . '-grid ' . $GLOBALS['NS'] . '-grid--3">';
        foreach ($rows as $r) {
            $out .= '<article class="h-listing">'
                  . '<div class="h-listing__img" data-tag="' . $r[3] . '">'
                  . '<img src="' . CB_THEME_URI . '/assets/images/scroll/_src/' . $r[4] . '" alt="' . $r[1] . '" decoding="async">'
                  . '</div>'
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
    public function have_posts() { return $GLOBALS['h_i'] + 1 < count($GLOBALS['h_posts']); }
    public function the_post() { $GLOBALS['h_i']++; }
}

$head = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{TITLE}}</title>

<!--
  GENERATED FILE -- do not hand-edit.
    Source : {{PARTIAL}}
    Rebuild: php harness/build-harness.php {{N}}

  Standalone harness for the {{TITLE}} engine. Open directly: no
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

<link rel="stylesheet" href="{{CSS}}">

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
    /* Home 9 set decoration -- deliberately NOT brand tokens. Gold and cream are
       CONTRACT.md's two WebGL-only values; the woods are new to Home 9. */
    --cb-gold: #C9A84C;
    --cb-cream: #F0EBE0;
    --cb-walnut: #2B1A10;
    --font-heading: 'Familjen Grotesk', 'Segoe UI', system-ui, sans-serif;
    --font-subheader: 'Josefin Sans', 'Segoe UI', system-ui, sans-serif;
    --font-body: Roboto, 'Segoe UI', system-ui, sans-serif;
    --font-accent: 'EB Garamond', Georgia, serif;
  }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { font-family: var(--font-body); background: {{FIELD}}; color: {{INK}}; }

  .cb-btn {
    display: inline-block; padding: .75rem 1.4rem; border-radius: 10px;
    font-family: var(--font-subheader); font-size: .8rem; font-weight: 600;
    letter-spacing: .08em; text-transform: uppercase; text-decoration: none;
    border: 1px solid transparent; cursor: pointer;
  }
  .cb-btn--primary { background: {{ACCENT}}; color: #fff; }
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
  .h-listing__img {
    aspect-ratio: 16/11; position: relative; overflow: hidden;
    background: linear-gradient(150deg, #1b4794, #08163a);   /* shows through until the photo decodes */
  }
  .h-listing__img img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .h-listing__img::after {
    content: attr(data-tag); position: absolute; z-index: 1; left: .5rem; top: .5rem;
    background: {{ACCENT}}; color: #fff; font-size: .58rem; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase; padding: .18rem .45rem; border-radius: 4px;
  }
  .h-listing__body { padding: .7rem; }
  .h-listing__price { font-family: var(--font-heading); font-weight: 700; font-size: 1rem; color: #fff; }
  .h-listing__addr { font-size: .76rem; color: rgba(255,255,255,.7); margin-top: .15rem; }
  .h-listing__meta { font-size: .68rem; color: {{ACCENT}}; margin-top: .4rem; letter-spacing: .04em; }

  /* Stand-in for [cb_testimonials type="rotator"]. */
  .h-quote { color: #fff; margin: 0; }
  .h-quote__stars { color: #C9A84C; letter-spacing: .15em; }
  .h-quote__text { font-family: var(--font-accent); font-style: italic; font-size: 1rem; line-height: 1.5; margin: .5rem 0 .4rem; }
  .h-quote__who { font-size: .74rem; color: {{ACCENT}}; letter-spacing: .06em; text-transform: uppercase; }

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
<body class="{{BODY}}">

<header class="cb-header">
  <img class="cb-header__logo-img" src="../theme/assets/images/logos/monogram-horizontal.svg" alt="Coldwell Banker Legacy">
</header>
HTML;

$tail = <<<'HTML'

<footer class="cb-footer">
  Coldwell Banker Legacy &middot; harness build &middot; not a live page
</footer>

<div class="h-badge">{{BADGE}}</div>

<!-- Load order is a hard contract (CONTRACT.md): three -> cursor -> motion -> engine.
     Motion is optional; the engine falls back to CSS keyframes + rAF counters. -->
<script src="../theme/assets/js/vendor/three.min.js"></script>
<script src="../theme/assets/js/cb-webgl/cursor.js"></script>
<script src="../theme/assets/js/vendor/motion.js"></script>
<script src="{{ENGINE}}"></script>
<script>
(function () {
  // Mirrors the variant's deploy/ loader gate, so the harness tests the same
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
    if (window.console) { console.info('[{{NS}} harness] gated off -- showing the flat fallback. Correct below 1025px, on touch, or with reduced-motion.'); }
    return;
  }
  if (!window.{{GLOBAL}}) {
    if (window.console) { console.error('[{{NS}} harness] engine did not load.'); }
    return;
  }
  window.{{GLOBAL}}.init({
    canvas: '#{{NS}}-canvas',{{BASEPATH}}
    monogram: '../theme/assets/images/logos/monogram.svg',
    monogramStacked: '../theme/assets/images/logos/monogram-vertical-stacked.svg'
  });
})();
</script>
</body>
</html>
HTML;

/**
 * The route stand-in. Deliberately plain and deliberately honest: it says which
 * real template serves the route in WordPress rather than pretending to BE that
 * page. Inventing content for /find-a-home/ here would only produce something
 * that has to be thrown away, and would misrepresent what is built.
 */
/**
 * The route page.
 *
 * COLOUR: every value comes from harness/palette.php, which derives the whole
 * system from CB Blue #012169 by colour-wheel maths -- one hue for structure, its
 * exact complement (the CONTRACT.md gold, which turns out to BE that complement to
 * within 2.6 degrees) for anything that asks to be clicked, lightness doing the
 * rest. Nothing here is eyeballed. Run `php harness/palette.php` for the
 * derivation and the WCAG audit.
 *
 * STRUCTURE: taken from each route's real template in this repo, not from the
 * national Coldwell Banker site. The templates are right here, so their own
 * headings are the accurate answer -- and they are the brokerage's own work,
 * unlike coldwellbanker.com's.
 *
 * Whitespace-first, per BRAND.md: "CB Blue + lots of white ... use Bright Blue +
 * Celestial sparingly as energy accents."
 */
$SUB = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ROUTE_TITLE}} — Coldwell Banker Legacy</title>
<!-- GENERATED -- do not hand-edit.
     Rebuild : php harness/build-harness.php all
     Colour  : harness/palette.php (derived from CB Blue; run it for the audit)
     Content : {{ROUTE_TPL}} -- the real template that serves this route -->
<style>
  :root {
    --ink: {{P_INK}}; --body: {{P_BODY}}; --surface: {{P_SURFACE}};
    --surface-alt: {{P_SURFACEALT}}; --line: {{P_LINE}};
    --primary: {{P_PRIMARY}}; --primary-lo: {{P_PRIMARYLO}}; --primary-hi: {{P_PRIMARYHI}};
    --on-primary: {{P_ONPRIMARY}}; --accent: {{P_ACCENT}}; --accent-lo: {{P_ACCENTLO}};
    --on-accent: {{P_ONACCENT}}; --muted: {{P_MUTED}};
    --font-heading: 'Familjen Grotesk', 'Segoe UI', system-ui, sans-serif;
    --font-subheader: 'Josefin Sans', 'Segoe UI', system-ui, sans-serif;
    --font-body: Roboto, 'Segoe UI', system-ui, sans-serif;
  }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { font-family: var(--font-body); background: var(--surface); color: var(--body); }
  .wrap { max-width: 68rem; margin: 0 auto; padding: 0 1.5rem; }

  .hd {
    background: var(--primary); color: var(--on-primary);
    height: 68px; display: flex; align-items: center;
  }
  .hd img { height: 28px; filter: brightness(0) invert(1); }

  /* CB Blue at two lightnesses off the SAME hue -- a gradient inside one hue
     rather than a blend between two colours. */
  .hero {
    background: linear-gradient(160deg, var(--primary-hi), var(--primary) 45%, var(--primary-lo));
    color: var(--on-primary); padding: clamp(3.5rem, 9vw, 6.5rem) 0;
  }
  .kick {
    font-family: var(--font-subheader); font-size: .76rem; font-weight: 600;
    letter-spacing: .2em; text-transform: uppercase; color: var(--accent);
  }
  h1 { font-family: var(--font-heading); font-weight: 600; font-size: clamp(2.1rem, 5vw, 3.6rem); line-height: 1.04; margin: .7rem 0 0; letter-spacing: -.02em; }
  .lede { font-size: clamp(1rem, 1.4vw, 1.16rem); line-height: 1.6; margin: 1.1rem 0 0; max-width: 46ch; opacity: .9; }
  .btn {
    display: inline-block; margin-top: 1.8rem; padding: .85rem 1.7rem; border-radius: 10px;
    background: var(--accent); color: var(--on-accent); text-decoration: none;
    font-family: var(--font-subheader); font-size: .8rem; font-weight: 700;
    letter-spacing: .09em; text-transform: uppercase;
  }
  .btn:hover { background: var(--accent-lo); color: #fff; }

  .sec { padding: clamp(2.5rem, 6vw, 4.5rem) 0; }
  h2 { font-family: var(--font-heading); font-weight: 600; font-size: clamp(1.3rem, 2.4vw, 1.9rem); color: var(--ink); margin: 0 0 1.2rem; letter-spacing: -.01em; }
  .grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); }
  .card {
    background: #fff; border: 1px solid var(--line); border-radius: 14px;
    padding: 1.4rem; box-shadow: 0 1px 2px rgba(1,33,105,.04);
  }
  .card h3 { font-family: var(--font-heading); font-size: 1.02rem; margin: 0 0 .4rem; color: var(--ink); }
  .card p { margin: 0; font-size: .9rem; line-height: 1.6; color: var(--muted); }
  .rule { height: 3px; width: 44px; background: var(--accent); border-radius: 2px; margin-bottom: 1rem; }

  .note {
    background: var(--surface-alt); border-left: 3px solid var(--accent);
    border-radius: 0 10px 10px 0; padding: 1.1rem 1.3rem; font-size: .88rem; line-height: 1.65;
  }
  .note code { font-family: ui-monospace, Consolas, monospace; color: var(--primary); font-weight: 600; }

  .ft { background: var(--ink); color: rgba(255,255,255,.62); padding: 2.2rem 0; font-size: .8rem; }
  .ft a { color: var(--accent); text-decoration: none; }
  .back {
    display: inline-block; font-family: var(--font-subheader); font-size: .76rem; font-weight: 700;
    letter-spacing: .1em; text-transform: uppercase; color: var(--accent); text-decoration: none;
  }
</style>
</head>
<body>

<header class="hd"><div class="wrap"><img src="../../theme/assets/images/logos/monogram-horizontal.svg" alt="Coldwell Banker Legacy"></div></header>

<section class="hero">
  <div class="wrap">
    <span class="kick">{{ROUTE_KICKER}}</span>
    <h1>{{ROUTE_H1}}</h1>
    <p class="lede">{{ROUTE_BLURB}}</p>
    <a class="btn" href="{{BACK}}">&larr; Back to the walkthrough</a>
  </div>
</section>

<section class="sec">
  <div class="wrap">
    <div class="rule"></div>
    <h2>On this page</h2>
    <div class="grid">{{ROUTE_SECTIONS}}</div>
  </div>
</section>

<section class="sec" style="padding-top:0">
  <div class="wrap">
    <p class="note">
      <strong>Harness stand-in.</strong> This route is already built: WordPress serves
      <code>{{ROUTE_PATH}}</code> from <code>{{ROUTE_TPL}}</code>. {{ROUTE_LIVE}}
      That needs PHP, the database and the live MLS feed, none of which exist under
      <code>file://</code> &mdash; so this page mirrors the real template's structure
      and colour system, and exists so the walkthrough's links are followable while
      previewing.
    </p>
  </div>
</section>

<footer class="ft">
  <div class="wrap">
    <a class="back" href="{{BACK}}">&larr; Back to the walkthrough</a>
    <p style="margin:.9rem 0 0">Coldwell Banker Legacy &middot; harness build &middot; not a live page.
      Colour derived from CB Blue by <a href="#">harness/palette.php</a>.</p>
  </div>
</footer>

</body>
</html>
HTML;

foreach ($which as $n) {
    $V = $VARIANTS[$n];
    $GLOBALS['NS'] = $V['ns'];
    $GLOBALS['h_i'] = -1;          // rewind the stubbed blog loop for each variant

    ob_start();
    require $repo . '/' . $V['partial'];
    $partial = ob_get_clean();

    $basePath = $V['basePath'] ? ("\n    basePath: '" . $V['basePath'] . "',") : '';
    $subs = [
        '{{TITLE}}'    => $V['title'],
        '{{PARTIAL}}'  => $V['partial'],
        '{{N}}'        => (string) $n,
        '{{CSS}}'      => $V['css'],
        '{{ENGINE}}'   => $V['engine'],
        '{{GLOBAL}}'   => $V['global'],
        '{{BODY}}'     => $V['body'],
        '{{BADGE}}'    => $V['badge'],
        '{{NS}}'       => $V['ns'],
        '{{FIELD}}'    => $V['field'],
        '{{INK}}'      => $V['ink'],
        '{{ACCENT}}'   => $V['accent'],
        '{{BASEPATH}}' => $basePath,
    ];
    $page = strtr($head, $subs) . "\n" . $partial . strtr($tail, $subs);

    $dest = __DIR__ . '/' . $V['out'];
    file_put_contents($dest, $page);
    printf("wrote %s (%d bytes, partial %d bytes)\n", $V['out'], strlen($page), strlen($partial));

}




<?php
/**
 * CB Legacy Luxury Theme Functions
 *
 * @package CB_Legacy_Luxury
 */

define('CB_THEME_VERSION', '1.1.0');
define('CB_THEME_DIR', get_template_directory());
define('CB_THEME_URI', get_template_directory_uri());

// Spark Platform API (Flexmls) credentials.
// Stored as constants so they live with the theme; can be overridden via wp_options.
if (!defined('CB_SPARK_KEY'))    { define('CB_SPARK_KEY',    'sa_bmccleery_key_1'); }
if (!defined('CB_SPARK_SECRET')) { define('CB_SPARK_SECRET', 'bgfxuihol1kalpv7zpkwnehyl'); }

require_once CB_THEME_DIR . '/inc/class-spark-client.php';
require_once CB_THEME_DIR . '/inc/class-spark-shortcodes.php';

/* ==========================================================================
   THEME SETUP
   ========================================================================== */
function cb_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', [
        'height'      => 100,
        'width'       => 300,
        'flex-width'  => true,
        'flex-height' => true,
    ]);
    add_theme_support('editor-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');

    add_image_size('cb-hero', 1920, 1080, true);
    add_image_size('cb-property', 800, 500, true);
    add_image_size('cb-agent', 400, 500, true);
    add_image_size('cb-community', 600, 450, true);
    add_image_size('cb-blog-thumb', 600, 340, true);

    register_nav_menus([
        'primary'   => __('Primary Navigation', 'cb-legacy'),
        'footer'    => __('Footer Navigation', 'cb-legacy'),
        'mobile'    => __('Mobile Navigation', 'cb-legacy'),
    ]);
}
add_action('after_setup_theme', 'cb_theme_setup');

/* ==========================================================================
   ENQUEUE SCRIPTS & STYLES
   ========================================================================== */
function cb_enqueue_assets() {
    // Main stylesheet
    wp_enqueue_style('cb-legacy-style', get_stylesheet_uri(), [], CB_THEME_VERSION);

    // GSAP Core + ScrollTrigger (vendored locally)
    wp_enqueue_script('gsap-core', CB_THEME_URI . '/assets/js/gsap.min.js', [], '3.12.5', true);
    wp_enqueue_script('gsap-scroll-trigger', CB_THEME_URI . '/assets/js/ScrollTrigger.min.js', ['gsap-core'], '3.12.5', true);

    // Safety net: if a local GSAP file is ever missing/corrupt, pull from CDN so
    // the homepage (which fades content in) can never be left blank.
    wp_add_inline_script(
        'gsap-scroll-trigger',
        "window.gsap||document.write('<script src=\"https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js\"><\\/script>');" .
        "window.ScrollTrigger||document.write('<script src=\"https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js\"><\\/script>');",
        'after'
    );

    // Theme animation controller
    wp_enqueue_script('cb-gsap-init', CB_THEME_URI . '/assets/js/gsap-init.js', ['gsap-core', 'gsap-scroll-trigger'], CB_THEME_VERSION, true);

    // The front page no longer loads the cinematic scroll stack.
    //
    // front-page.php renders Home 10 now, so scroll-home.css and home.js have no
    // .cb-scene elements to act on -- but LENIS IS THE REAL REASON THIS HAD TO GO.
    // Lenis replaces native scrolling with its own interpolated position, and
    // home10.js decides which room you are in by reading window.pageYOffset every
    // frame. Left enqueued, the two fight: the walk triggers late, section
    // boundaries drift, and the panel fades chase a scroll position that is still
    // being smoothed toward its target.
    //
    // The cinematic stack is NOT dead code. cb_enqueue_home2_webgl() below loads
    // the identical bundle for the "Home 2 — Cinematic WebGL Preview" template,
    // which still renders template-parts/home-scenes.php. Nothing was lost; it
    // just stopped being the homepage. Restoring this block plus front-page.php
    // from git is all a revert takes.
    //
    // Home 10's own assets come from deploy/cb-home10-preview.php, which enqueues
    // the two stylesheets and client-gates the engine.

    // Inner page styles (loaded on all pages for shared components)
    wp_enqueue_style('cb-pages-style', CB_THEME_URI . '/assets/css/pages/pages.css', ['cb-legacy-style'], CB_THEME_VERSION);

    // Flexmls IDX style overrides
    wp_enqueue_style('cb-idx-overrides', CB_THEME_URI . '/assets/css/idx-overrides.css', ['cb-legacy-style'], CB_THEME_VERSION);

    // Page interactions (tabs, accordion, multi-step, agent search)
    wp_enqueue_script('cb-pages', CB_THEME_URI . '/assets/js/modules/pages.js', ['cb-gsap-init'], CB_THEME_VERSION, true);

    // Header scroll behavior
    wp_enqueue_script('cb-header', CB_THEME_URI . '/assets/js/modules/header.js', [], CB_THEME_VERSION, true);

    // Form handlers
    wp_enqueue_script('cb-forms', CB_THEME_URI . '/assets/js/modules/forms.js', [], CB_THEME_VERSION, true);

    // Save/un-save listing cards (site-wide, no login)
    wp_enqueue_script('cb-favorites', CB_THEME_URI . '/assets/js/modules/cb-favorites.js', [], CB_THEME_VERSION, true);

    // Listing detail page lightbox (only on /listing/<slug>-<id>/ pages)
    if (get_query_var('cb_listing_id')) {
        wp_enqueue_script('cb-lightbox', CB_THEME_URI . '/assets/js/modules/lightbox.js', [], CB_THEME_VERSION, true);
    }

    // Leaflet map + split-view search on /find-a-home/ only.
    if (is_page_template('templates/template-find-home.php')) {
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );
        wp_enqueue_style(
            'leaflet-markercluster',
            'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',
            ['leaflet'],
            '1.5.3'
        );
        wp_enqueue_style(
            'leaflet-markercluster-default',
            'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css',
            ['leaflet'],
            '1.5.3'
        );
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );
        wp_enqueue_script(
            'leaflet-markercluster',
            'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js',
            ['leaflet'],
            '1.5.3',
            true
        );
        wp_enqueue_script(
            'cb-map-search',
            CB_THEME_URI . '/assets/js/modules/cb-map-search.js',
            ['leaflet', 'leaflet-markercluster'],
            CB_THEME_VERSION,
            true
        );
    }

    // Localize for AJAX
    wp_localize_script('cb-gsap-init', 'cbLegacy', [
        'ajaxUrl'  => admin_url('admin-ajax.php'),
        'restUrl'  => rest_url('cb/v1/'),
        'nonce'    => wp_create_nonce('wp_rest'),
        'themeUri' => CB_THEME_URI,
    ]);
}
add_action('wp_enqueue_scripts', 'cb_enqueue_assets');

/* ==========================================================================
   HOME 2 — CINEMATIC WEBGL PREVIEW (un-linked staging page)
   Loads the full cinematic scroll stack on the "Home 2 — Cinematic WebGL
   Preview" page template ONLY, so the WebGL homepage can be reviewed live
   without touching the published front page. Three modes are wired:
     • WebGL-capable desktop  → Version B (Three.js canvas) via main.js
     • Desktop, no WebGL      → Version A (GSAP/CSS scroll) via home.js
     • Mobile / reduced-motion→ clean stacked layout (accessible baseline)
   cb_home2_capability_script() guarantees only ONE controller ever runs.
   ========================================================================== */
define('CB_HOME2_TEMPLATE', 'templates/template-home2-webgl.php');

function cb_enqueue_home2_webgl() {
    if (!is_page_template(CB_HOME2_TEMPLATE)) { return; }

    // --- Shared cinematic base (same as the front page) ---
    // Lenis smooth scroll + scene controller + styles. home.js drives the
    // Version-A fallback; it stays dormant when WebGL takes over (no .cb-cinematic).
    wp_enqueue_script('cb-lenis', CB_THEME_URI . '/assets/js/vendor/lenis.min.js', [], '1.1.18', true);
    wp_enqueue_style('cb-scroll-home', CB_THEME_URI . '/assets/css/scroll-home.css', ['cb-legacy-style'], CB_THEME_VERSION);
    wp_enqueue_script('cb-home-animations', CB_THEME_URI . '/assets/js/page-animations/home.js', ['cb-gsap-init', 'cb-lenis'], CB_THEME_VERSION, true);

    // --- WebGL cinematic layer (vendored Three.js + modules). Order matters. ---
    wp_enqueue_script('three',         CB_THEME_URI . '/assets/js/vendor/three.min.js', [], '0.160.0', true);
    wp_enqueue_script('cb-wg-shaders', CB_THEME_URI . '/assets/js/cb-webgl/shaders.js', ['three'], CB_THEME_VERSION, true);
    wp_enqueue_script('cb-wg-engine',  CB_THEME_URI . '/assets/js/cb-webgl/engine.js',  ['three', 'cb-wg-shaders'], CB_THEME_VERSION, true);
    wp_enqueue_script('cb-wg-scenes',  CB_THEME_URI . '/assets/js/cb-webgl/scenes.js',  ['three', 'cb-wg-engine'], CB_THEME_VERSION, true);
    wp_enqueue_script('cb-wg-cursor',  CB_THEME_URI . '/assets/js/cb-webgl/cursor.js',  ['three'], CB_THEME_VERSION, true);
    wp_enqueue_script('cb-wg-main',    CB_THEME_URI . '/assets/js/cb-webgl/main.js',    ['three', 'cb-wg-shaders', 'cb-wg-engine', 'cb-wg-scenes', 'cb-wg-cursor'], CB_THEME_VERSION, true);
    wp_enqueue_style('cb-webgl',       CB_THEME_URI . '/assets/css/cb-webgl.css', ['cb-scroll-home'], CB_THEME_VERSION);

    // Same-origin images → no crossOrigin needed (main.js loader handles this).
    // Gate init() to the SAME "eligible desktop" definition used by header.php and
    // cb_home2_capability_script() (>=1025px, motion ok, fine pointer) so all three
    // layers agree on one breakpoint — WebGL never starts in the tablet band the
    // head logic treats as a fallback. Wrapped in try/catch so any unexpected throw
    // restores the DOM/CSS fallback instead of leaving html.cb-webgl-on stuck on.
    $cb_wg_base = esc_js(CB_THEME_URI . '/assets/images/webgl/');
    wp_add_inline_script(
        'cb-wg-main',
        "(function(){try{" .
        "var ok=window.matchMedia('(min-width: 1025px)').matches" .
        "&&window.matchMedia('(prefers-reduced-motion: no-preference)').matches" .
        "&&!window.matchMedia('(pointer: coarse)').matches;" .
        "if(ok&&window.CBWebGL){window.CBWebGL.init({basePath:'{$cb_wg_base}',canvas:'#cb-webgl'});}" .
        "}catch(e){document.documentElement.classList.remove('cb-webgl-on');" .
        "if(window.console){console.warn('[cb] WebGL init failed; using fallback.',e);}}})();",
        'after'
    );
}
add_action('wp_enqueue_scripts', 'cb_enqueue_home2_webgl');

/* ==========================================================================
   HOME 7 — CORRIDOR WALKTHROUGH PREVIEW (un-linked staging page)
   Loads the 3D dust-nebula corridor walkthrough on the "Home 7 — Corridor
   Walkthrough Preview" page template ONLY.
   ========================================================================== */
define('CB_HOME7_TEMPLATE', 'templates/template-home7-corridor.php');

function cb_enqueue_home7_corridor() {
    if (!is_page_template(CB_HOME7_TEMPLATE)) { return; }

    // Three.js core
    wp_enqueue_script('three', CB_THEME_URI . '/assets/js/vendor/three.min.js', [], '0.160.0', true);

    // Custom cursor (shared with Home 2 WebGL)
    wp_enqueue_script('cb-wg-cursor', CB_THEME_URI . '/assets/js/cb-webgl/cursor.js', ['three'], CB_THEME_VERSION, true);

    // Corridor engine
    wp_enqueue_script('cb-corridor', CB_THEME_URI . '/assets/js/cb-corridor/corridor.js', ['three', 'cb-wg-cursor'], CB_THEME_VERSION, true);

    // Corridor styles
    wp_enqueue_style('cb-corridor', CB_THEME_URI . '/assets/css/cb-corridor.css', ['cb-legacy-style'], CB_THEME_VERSION);

    // Init inline — gates on capable desktop so it never runs on mobile/tablet.
    $cb_mono    = esc_js(CB_THEME_URI . '/assets/images/logos/monogram-horizontal-stacked.svg');
    $cb_monoStk = esc_js(CB_THEME_URI . '/assets/images/logos/monogram-vertical-stacked.svg');
    wp_add_inline_script(
        'cb-corridor',
        "(function(){try{" .
        "var ok=window.matchMedia('(min-width: 1025px)').matches" .
        "&&window.matchMedia('(prefers-reduced-motion: no-preference)').matches" .
        "&&!window.matchMedia('(pointer: coarse)').matches;" .
        "if(ok&&window.CBCorridor){window.CBCorridor.init({" .
        "canvas:'#cb-corridor-canvas'," .
        "monogram:'{$cb_mono}'," .
        "monogramStacked:'{$cb_monoStk}'" .
        "});}" .
        "}catch(e){document.documentElement.classList.remove('cb-corridor-on');" .
        "if(window.console){console.warn('[cb-corridor] init failed; using fallback.',e);}}})();",
        'after'
    );
}
add_action('wp_enqueue_scripts', 'cb_enqueue_home7_corridor');

/**
 * Pre-paint mode arbitration for the Home 2 preview.
 *
 * header.php sets html.cb-cinematic synchronously for every capable desktop.
 * On the preview page we must hand the experience to WebGL when it is genuinely
 * available — otherwise home.js (Version A) and main.js (Version B) would both
 * run and instantiate Lenis twice, fighting over the scroll. This script runs in
 * <head> AFTER header.php's class script (it is printed via wp_head, which fires
 * later in the head) and BEFORE any footer script, so the decision is race-free:
 *
 *   • WebGL-capable desktop → remove .cb-cinematic. home.js then early-returns
 *     (only wires Property Watch); main.js adds .cb-webgl-on once the engine
 *     truly starts. If the engine bails, the page degrades to the clean stacked
 *     layout (reveals are visible by default) — never blank.
 *   • Otherwise → leave header.php's decision intact (Version A or stacked).
 */
function cb_home2_capability_script() {
    if (!is_page_template(CB_HOME2_TEMPLATE)) { return; }
    ?>
<script>
/* Home 2 preview — choose ONE scroll controller (WebGL vs. CSS-cinematic). */
(function (d) {
    var h = d.documentElement;
    function webglOk() {
        try {
            if (!window.WebGLRenderingContext) { return false; }
            var c = d.createElement('canvas');
            var gl = c.getContext('webgl2') || c.getContext('webgl') || c.getContext('experimental-webgl');
            if (!gl) { return false; }
            // Release the probe context immediately (mirrors engine.js) so we don't
            // sit one context closer to the browser cap when the real renderer spins up.
            var lose = gl.getExtension('WEBGL_lose_context');
            if (lose) { lose.loseContext(); }
            return true;
        } catch (e) { return false; }
    }
    var desktop = false, motionOK = false, finePointer = false;
    try {
        desktop     = window.matchMedia('(min-width: 1025px)').matches;
        motionOK    = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
        finePointer = !window.matchMedia('(pointer: coarse)').matches;
    } catch (e) {}
    if (desktop && motionOK && finePointer && webglOk()) {
        // Hand off to WebGL: home.js sees no .cb-cinematic and stays dormant;
        // main.js adds .cb-webgl-on when the engine initializes.
        h.classList.remove('cb-cinematic');
    }
})(document);
</script>
    <?php
}
add_action('wp_head', 'cb_home2_capability_script', 5);

/**
 * Keep the Home 2 preview out of WordPress core's XML sitemap (/wp-sitemap.xml).
 * The runtime <meta robots> already prevents indexing, but core decides sitemap
 * membership from stored post state, not the runtime meta — so the URL would
 * otherwise still be advertised to crawlers. Excludes any page whose assigned
 * template is the preview template. (Yoast users: also tick "noindex" on the
 * page in the Yoast meta box, since Yoast runs its own sitemap.)
 */
add_filter('wp_sitemaps_posts_query_args', function ($args, $post_type) {
    if ($post_type !== 'page') { return $args; }
    $meta_query   = isset($args['meta_query']) ? $args['meta_query'] : [];
    $meta_query[] = [
        'relation' => 'OR',
        ['key' => '_wp_page_template', 'value' => CB_HOME2_TEMPLATE, 'compare' => '!='],
        ['key' => '_wp_page_template', 'compare' => 'NOT EXISTS'],
    ];
    $args['meta_query'] = $meta_query;
    return $args;
}, 10, 2);

/* ==========================================================================
   PARKED NEIGHBORHOOD DOMAIN REDIRECTS
   Maps owned neighborhood domains (bentwoodhomes.com, etc.) to their
   canonical community landing page on homes-sanangelo.com. Consolidates
   SEO equity to the main domain via 301.

   Add new domains by editing cb_get_domain_aliases() below.
   ========================================================================== */
function cb_get_domain_aliases() {
    // Format: 'incoming.domain' => '/path/on/main-domain/'
    return apply_filters('cb_domain_aliases', [
        // 'bentwoodhomes.com'       => '/communities/bentwood/',
        // 'lakenasworthyhomes.com'  => '/communities/lake-nasworthy/',
        // 'collegehillshomes.com'   => '/communities/college-hills/',
        // 'christovalhomes.com'     => '/communities/christoval/',
        // 'wallhomes.com'           => '/communities/wall/',
        // 'grapecreekhomes.com'     => '/communities/grape-creek/',
    ]);
}

add_action('template_redirect', function () {
    $aliases = cb_get_domain_aliases();
    if (empty($aliases)) { return; }

    $host = isset($_SERVER['HTTP_HOST']) ? strtolower(preg_replace('/[^a-z0-9.\-:]/i', '', $_SERVER['HTTP_HOST'])) : '';
    $host = preg_replace('/^www\./', '', $host);
    if (!$host) { return; }

    // Don't redirect the canonical host.
    $canonical_host = parse_url(home_url(), PHP_URL_HOST);
    $canonical_host = preg_replace('/^www\./', '', strtolower($canonical_host));
    if ($host === $canonical_host) { return; }

    if (isset($aliases[$host])) {
        wp_safe_redirect(home_url($aliases[$host]), 301);
        exit;
    }
}, 1);

/* ==========================================================================
   SEO META HELPER
   One call wires title + description + canonical + OG + Twitter for any
   page template. Routes through Yoast filters when active so we don't fight
   the plugin; falls back to direct <meta> emission otherwise.
   ========================================================================== */
function cb_set_seo_meta($args) {
    $args = wp_parse_args($args, [
        'title'       => '',
        'description' => '',
        'canonical'   => '',
        'og_image'    => '',
        'og_type'     => 'website',
        'robots'      => 'index, follow, max-image-preview:large',
    ]);

    $has_yoast = defined('WPSEO_VERSION');

    if ($has_yoast) {
        if ($args['title'])       { add_filter('wpseo_title',           function () use ($args) { return $args['title']; }, 99); }
        if ($args['description']) { add_filter('wpseo_metadesc',        function () use ($args) { return $args['description']; }, 99); }
        if ($args['canonical'])   { add_filter('wpseo_canonical',       function () use ($args) { return $args['canonical']; }, 99); }
        if ($args['title'])       { add_filter('wpseo_opengraph_title', function () use ($args) { return $args['title']; }, 99); }
        if ($args['description']) { add_filter('wpseo_opengraph_desc',  function () use ($args) { return $args['description']; }, 99); }
        if ($args['canonical'])   { add_filter('wpseo_opengraph_url',   function () use ($args) { return $args['canonical']; }, 99); }
        if ($args['og_image'])    { add_filter('wpseo_opengraph_image', function () use ($args) { return $args['og_image']; }, 99); }
        if ($args['title'])       { add_filter('wpseo_twitter_title',   function () use ($args) { return $args['title']; }, 99); }
        if ($args['description']) { add_filter('wpseo_twitter_description', function () use ($args) { return $args['description']; }, 99); }
        if ($args['og_image'])    { add_filter('wpseo_twitter_image',   function () use ($args) { return $args['og_image']; }, 99); }
        add_filter('wpseo_robots', function () use ($args) { return $args['robots']; }, 99);
    }

    // Always set the WP document title (works whether or not Yoast is active).
    if ($args['title']) {
        add_filter('pre_get_document_title', function () use ($args) { return $args['title']; }, 99);
    }

    // Fallback: emit head tags directly when Yoast is not active.
    if (!$has_yoast) {
        add_action('wp_head', function () use ($args) {
            echo "\n";
            if ($args['canonical'])   { echo '<link rel="canonical" href="' . esc_url($args['canonical']) . '">' . "\n"; }
            if ($args['description']) { echo '<meta name="description" content="' . esc_attr($args['description']) . '">' . "\n"; }
            if ($args['robots'])      { echo '<meta name="robots" content="' . esc_attr($args['robots']) . '">' . "\n"; }
            echo '<meta property="og:type" content="' . esc_attr($args['og_type']) . '">' . "\n";
            if ($args['title'])       { echo '<meta property="og:title" content="' . esc_attr($args['title']) . '">' . "\n"; }
            if ($args['description']) { echo '<meta property="og:description" content="' . esc_attr($args['description']) . '">' . "\n"; }
            if ($args['canonical'])   { echo '<meta property="og:url" content="' . esc_url($args['canonical']) . '">' . "\n"; }
            if ($args['og_image'])    { echo '<meta property="og:image" content="' . esc_url($args['og_image']) . '">' . "\n"; }
            echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
            if ($args['title'])       { echo '<meta name="twitter:title" content="' . esc_attr($args['title']) . '">' . "\n"; }
            if ($args['description']) { echo '<meta name="twitter:description" content="' . esc_attr($args['description']) . '">' . "\n"; }
            if ($args['og_image'])    { echo '<meta name="twitter:image" content="' . esc_url($args['og_image']) . '">' . "\n"; }
        });
    }
}

/* ==========================================================================
   GOOGLE ANALYTICS / GTM / SEARCH CONSOLE
   Set these constants in wp-config or via the customizer once you have IDs.
   ========================================================================== */
function cb_analytics_head() {
    // Don't pollute the client's analytics with internal preview traffic.
    if (defined('CB_HOME2_TEMPLATE') && is_page_template(CB_HOME2_TEMPLATE)) { return; }

    $ga4 = defined('CB_GA4_ID') ? CB_GA4_ID : get_theme_mod('cb_ga4_id', '');
    $gsc = defined('CB_GSC_VERIFICATION') ? CB_GSC_VERIFICATION : get_theme_mod('cb_gsc_verification', '');

    if ($gsc) {
        echo '<meta name="google-site-verification" content="' . esc_attr($gsc) . '">' . "\n";
    }
    if ($ga4) {
        $id = esc_js($ga4);
        echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id=$id\"></script>\n";
        echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','$id');</script>\n";
    }
}
add_action('wp_head', 'cb_analytics_head', 1);

/* ==========================================================================
   TESTIMONIAL TREE INTEGRATION
   Official Coldwell Banker-approved testimonial syndication.
   Widget IDs from CB office: 71288 (rotator) / 71289 (list).
   Per-agent filtering uses the agent_email post meta on single-cb_agent pages.

   Usage:
     [cb_testimonials]                              ← defaults to rotator on home, list elsewhere
     [cb_testimonials type="rotator"]               ← carousel
     [cb_testimonials type="list"]                  ← full list
     [cb_testimonials type="rotator" email="..."]   ← per-agent filtering
   ========================================================================== */
function cb_testimonials_shortcode($atts) {
    $atts = shortcode_atts([
        'type'  => 'rotator',
        'email' => '',
    ], $atts, 'cb_testimonials');

    $widget_ids = [
        'rotator' => '71288',
        'list'    => '71289',
    ];
    $widget_id = $widget_ids[$atts['type']] ?? $widget_ids['rotator'];

    $src = 'https://application.testimonialtree.com/api/v1/widgets/script?widgetid=' . urlencode($widget_id);
    if (!empty($atts['email']) && is_email($atts['email'])) {
        $src .= '&email=' . urlencode($atts['email']);
    }

    $container_id = 'TestimonialTree_Widget_' . $widget_id . ($atts['email'] ? '_' . substr(md5($atts['email']), 0, 8) : '');

    return sprintf(
        '<div class="cb-tt-widget cb-tt-widget--%s"><div id="%s"></div><script type="text/javascript" src="%s"></script></div>',
        esc_attr($atts['type']),
        esc_attr($container_id),
        esc_url($src)
    );
}
add_shortcode('cb_testimonials', 'cb_testimonials_shortcode');

/* ==========================================================================
   SITE-WIDE LocalBusiness / RealEstateAgent JSON-LD
   The single most important schema for local SEO. Emits on every page so
   Google reinforces the brokerage's NAP, geo, hours, and social links.
   ========================================================================== */
function cb_brokerage_schema() {
    // The footer-visible brokerage info (phone/address/email) lives in theme_mods,
    // so this schema mirrors whatever's shown publicly — keeping NAP consistent.
    $name    = 'Coldwell Banker Legacy';
    $phone   = get_theme_mod('cb_phone', '(325) 944-9559');
    $email   = get_theme_mod('cb_email', 'info@cbltexas.com');
    $address = get_theme_mod('cb_address', '3017 Knickerbocker, San Angelo, TX 76904');

    // Parse the address (very loose — works for "<street>, <city>, <state> <zip>" patterns).
    $street = $city = $state = $zip = '';
    if (preg_match('/^(.+),\s*([^,]+),\s*([A-Z]{2})\s+(\d{5})/', $address, $m)) {
        [, $street, $city, $state, $zip] = $m;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'RealEstateAgent',
        '@id'      => home_url('/#brokerage'),
        'name'     => $name,
        'alternateName'  => 'Coldwell Banker Legacy San Angelo',
        'url'      => home_url('/'),
        'image'    => CB_THEME_URI . '/assets/images/cb-logo.png',
        'logo'     => CB_THEME_URI . '/assets/images/cb-logo.png',
        'telephone'   => preg_replace('/[^0-9+]/', '', $phone),
        'email'       => $email,
        'description' => 'Coldwell Banker Legacy is the premier real estate brokerage serving San Angelo and the Concho Valley with luxury homes, MLS search, property management, and expert local agents.',
        'priceRange'  => '$$$',
        'address'  => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $street ?: $address,
            'addressLocality' => $city ?: 'San Angelo',
            'addressRegion'   => $state ?: 'TX',
            'postalCode'      => $zip ?: '76904',
            'addressCountry'  => 'US',
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => floatval(get_theme_mod('cb_geo_lat', '31.4377')),
            'longitude' => floatval(get_theme_mod('cb_geo_lng', '-100.4503')),
        ],
        'areaServed' => [
            ['@type' => 'City',     'name' => 'San Angelo'],
            ['@type' => 'AdministrativeArea', 'name' => 'Concho Valley'],
            ['@type' => 'AdministrativeArea', 'name' => 'Tom Green County'],
        ],
        'openingHoursSpecification' => [
            [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday'],
                'opens'     => '08:30',
                'closes'    => '17:30',
            ],
            [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Saturday'],
                'opens'     => '10:00',
                'closes'    => '15:00',
            ],
        ],
        'sameAs' => [
            'https://www.facebook.com/ColdwellBankerLegacySanAngelo',
            'https://www.instagram.com/cblegacysanangelotx/',
            'https://www.linkedin.com/company/coldwell-banker-legacy-san-angelo-texas',
        ],
        'parentOrganization' => [
            '@type' => 'Organization',
            'name'  => 'Coldwell Banker',
            'url'   => 'https://www.coldwellbanker.com/',
        ],
    ];

    // AggregateRating from testimonials CPT, if any are published.
    $tcounts = wp_count_posts('cb_testimonial');
    $count   = is_object($tcounts) ? (int) $tcounts->publish : 0;
    if ($count >= 3) {
        $ratings = get_posts([
            'post_type'      => 'cb_testimonial',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ]);
        $sum = 0; $valid = 0;
        foreach ($ratings as $tid) {
            $r = (float) get_post_meta($tid, 'testimonial_rating', true);
            if ($r > 0 && $r <= 5) { $sum += $r; $valid++; }
        }
        if ($valid >= 3) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => round($sum / $valid, 1),
                'reviewCount' => $valid,
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'cb_brokerage_schema', 5);

/* ==========================================================================
   COMMUNITIES — neighborhood filter pages at /communities/<slug>/
   ========================================================================== */
function cb_get_communities() {
    return [
        'san-angelo' => [
            'name'        => 'San Angelo',
            'tagline'     => 'The heart of the Concho Valley',
            'expr'        => "City Eq 'San Angelo'",
            'description' => "San Angelo is the largest city in the Concho Valley and the regional hub for West Texas, with a stable economy anchored by Goodfellow Air Force Base, Angelo State University, agriculture, and healthcare. The San Angelo housing market spans the full price spectrum — from affordable starter homes in established neighborhoods like College Hills and Bluffs to brand-new construction in luxury communities like Bentwood Country Club Estates and lakefront Lake Nasworthy. Buyers love San Angelo for its low cost of living, strong public and private schools, vibrant downtown arts district, year-round outdoor lifestyle along the Concho River, and a sense of community that's rare in cities of its size. Whether you're searching for a first home, a forever home, a luxury estate, or an investment property, San Angelo offers exceptional value and a quality of life that consistently surprises newcomers.",
            'image'       => '',
        ],
        'bentwood' => [
            'name'        => 'Bentwood',
            'tagline'     => 'Premier country-club living',
            'expr'        => "SubdivisionName Eq 'Bentwood Country Club Est'",
            'description' => "Bentwood Country Club Estates is San Angelo's most distinguished gated luxury community, built around an 18-hole championship golf course designed by the legendary Tom Fazio firm. Homes in Bentwood range from elegant 3,000-square-foot custom builds to sprawling 6,000+ sqft estates with pools, guest casitas, and golf-course frontage. Residents enjoy access to the Bentwood Country Club's golf, tennis, swimming, and fitness amenities, plus the security of a 24-hour gated entrance and a deeply established luxury neighborhood feel. The Bentwood zip code (76904) consistently records San Angelo's highest median sale prices, and homes here are typically zoned to Central High School and the top-rated elementary feeders in San Angelo ISD. For buyers seeking the area's most prestigious address — with golf, social club lifestyle, and a long history of luxury home appreciation — Bentwood is the answer.",
            'image'       => 'assets/images/communities/bentwood.webp',
        ],
        'college-hills' => [
            'name'        => 'College Hills',
            'tagline'     => 'Established neighborhoods near ASU',
            'expr'        => "SubdivisionName Eq 'College Hills'",
            'description' => "College Hills is one of San Angelo's most established and beloved residential neighborhoods, located immediately adjacent to Angelo State University. The area is defined by its massive mature pecan and live-oak tree canopy, mid-century brick ranch homes built in the 1950s through 1970s, manicured front lawns, and a true sidewalk-and-neighbors community feel. Most College Hills homes sit on generous quarter- to half-acre lots and feature 3 to 4 bedrooms with the classic Texas layouts and quality construction of their era — many recently updated for modern living. The neighborhood is walking distance to ASU, close to Texas Bank Sports Complex and Goodfellow AFB, and zoned to top-rated San Angelo ISD schools. College Hills is consistently in high demand from professors, professionals, growing families, and military families looking for character, walkable streets, and an established address in San Angelo.",
            'image'       => 'assets/images/communities/college-hills.webp',
        ],
        'lake-nasworthy' => [
            'name'        => 'Lake Nasworthy',
            'tagline'     => 'Waterfront luxury homes',
            'expr'        => "(SubdivisionName Eq 'Lake Nasworthy Group 1' Or SubdivisionName Eq 'Lake Nasworthy Group 2')",
            'description' => "Lake Nasworthy is San Angelo's premier waterfront community — a 1,600-acre lake on the city's south side offering year-round boating, fishing, water sports, and luxury lakefront living. Homes around Lake Nasworthy range from charming weekend cabins to multi-million-dollar custom estates with private docks, boathouses, panoramic water views, and direct deep-water access. The lake itself is unique to the region: warm-water inflow from a nearby power plant keeps Nasworthy ice-free year-round, making it the only Texas lake where boating, swimming, and skiing are practical even in mid-winter. Residents enjoy proximity to Mary E. Lee Park, scenic Knickerbocker Road, and an active community of fellow lake-lovers. For buyers seeking a true waterfront lifestyle without leaving the city limits, Lake Nasworthy is unmatched in San Angelo and the broader Concho Valley.",
            'image'       => 'assets/images/communities/lake-nasworthy.webp',
        ],
        'grape-creek' => [
            'name'        => 'Grape Creek',
            'tagline'     => 'Quiet country living',
            'expr'        => "City Eq 'Grape Creek'",
            'description' => "Grape Creek is a peaceful unincorporated rural community located just north of San Angelo along US Highway 87, offering the perfect balance of country living and city convenience. Property in Grape Creek typically sits on larger lots — often half-acre to multi-acre tracts — with rural-style homes, room for animals, gardens, workshops, and the kind of breathing room you simply can't find inside city limits. The Grape Creek ISD school district is small, well-regarded, and tight-knit, making the area especially attractive to families. Property taxes are typically lower than in the city, and the commute to San Angelo, Goodfellow AFB, or Angelo State University is an easy 15-minute drive. Buyers love Grape Creek for its affordability, slower pace, and authentic small-town Texas atmosphere — all while remaining a short trip from everything San Angelo has to offer.",
            'image'       => '',
        ],
        'christoval' => [
            'name'        => 'Christoval',
            'tagline'     => 'Hill country charm',
            'expr'        => "City Eq 'Christoval'",
            'description' => "Christoval is a small, picturesque town located just 20 minutes south of San Angelo along the South Concho River, where the Concho Valley starts blending into the Texas Hill Country. Christoval's defining features are the spring-fed South Concho River that runs through the heart of town, dramatic cedar-and-oak-covered hills, rolling pastureland, and the kind of star-filled night skies that draw photographers and astronomers from across Texas. Real estate in Christoval skews toward large-acreage country properties, river-frontage homes, ranchettes, and custom-built family homes, with most properties sitting on 1 to 20+ acres. The Christoval ISD school district is small and well-regarded, the community is famously friendly, and the area is popular with families seeking space, privacy, hunting and fishing access, and a true rural Texas lifestyle within easy reach of San Angelo's full city amenities.",
            'image'       => 'assets/images/communities/christoval.webp',
        ],
        'wall' => [
            'name'        => 'Wall',
            'tagline'     => 'Top-rated schools, small-town pace',
            'expr'        => "City Eq 'Wall'",
            'description' => "Wall is a thriving small community located just east of San Angelo, known across West Texas for one of the highest-rated public school districts in the region. Wall ISD consistently ranks among Texas's top-performing rural districts academically, athletically, and in college readiness — drawing families from across the Concho Valley who relocate specifically for the schools. Real estate in Wall ranges from new construction on small acreage to established family homes in mature neighborhoods like Windsor Estates, plus larger agricultural and ranch properties on the city's outskirts. The town has a tight-knit, friendly atmosphere with strong community traditions, low crime, and a steady identity rooted in agriculture and faith. Wall offers the rare combination of small-town quality of life with an easy 15-minute commute to San Angelo for work, shopping, and entertainment.",
            'image'       => 'assets/images/communities/wall.webp',
        ],
    ];
}

/**
 * Resolve a community's image URL or return '' if none configured.
 */
function cb_community_image_url($community) {
    if (empty($community['image'])) { return ''; }
    return CB_THEME_URI . '/' . ltrim($community['image'], '/');
}

add_action('init', function () {
    add_rewrite_rule('^communities/([a-z0-9-]+)/?$', 'index.php?cb_community_slug=$matches[1]', 'top');
    add_rewrite_rule('^communities/?$',              'index.php?cb_community_slug=index',         'top');
});

/* ==========================================================================
   SCHOOL-ZONE LANDING PAGES at /schools/<slug>/
   Targets high-intent queries like "homes in Central HS zone San Angelo".
   ========================================================================== */
function cb_get_schools() {
    return [
        'central-high-school' => [
            'name'    => 'Central High School',
            'short'   => 'Central HS',
            'district'=> 'San Angelo ISD',
            'expr'    => "HighSchool Eq 'Central'",
            'description' => "Central High School is San Angelo's flagship public high school, serving central and west San Angelo neighborhoods. Homes in the Central zone include established communities like College Hills, Bentwood, and Bluffs, with a wide range of price points from starter homes to luxury estates.",
        ],
        'lake-view-high-school' => [
            'name'    => 'Lake View High School',
            'short'   => 'Lake View HS',
            'district'=> 'San Angelo ISD',
            'expr'    => "HighSchool Eq 'Lakeview'",
            'description' => 'Lake View High School serves north and northeast San Angelo, including neighborhoods near the Concho River and lake areas. The zone offers an affordable mix of starter homes, family residences, and acreage properties.',
        ],
        'grape-creek-high-school' => [
            'name'    => 'Grape Creek High School',
            'short'   => 'Grape Creek HS',
            'district'=> 'Grape Creek ISD',
            'expr'    => "HighSchool Eq 'Grape Creek'",
            'description' => 'Grape Creek High School anchors the small, close-knit Grape Creek ISD just north of San Angelo. The community is known for excellent schools, lower property taxes, and affordable acreage homes.',
        ],
        'wall-high-school' => [
            'name'    => 'Wall High School',
            'short'   => 'Wall HS',
            'district'=> 'Wall ISD',
            'expr'    => "HighSchool Eq 'Wall'",
            'description' => 'Wall High School consistently ranks among the top-performing schools in West Texas. The Wall ISD attendance zone is one of the most sought-after in the region, drawing families from across the Concho Valley to its tight-knit small-town community.',
        ],
        'christoval-high-school' => [
            'name'    => 'Christoval High School',
            'short'   => 'Christoval HS',
            'district'=> 'Christoval ISD',
            'expr'    => "HighSchool Eq 'Christoval'",
            'description' => 'Christoval High School serves the small rural community 20 minutes south of San Angelo along the South Concho River. The Christoval ISD zone is popular with families seeking large-acreage country properties and a true small-town school experience.',
        ],
    ];
}

add_action('init', function () {
    add_rewrite_rule('^schools/([a-z0-9-]+)/?$', 'index.php?cb_school_slug=$matches[1]', 'top');
    add_rewrite_rule('^schools/?$',              'index.php?cb_school_slug=index',         'top');
    add_rewrite_rule('^recently-sold/?$',        'index.php?cb_recently_sold=1',         'top');
});
add_filter('query_vars', function ($vars) {
    $vars[] = 'cb_school_slug';
    $vars[] = 'cb_recently_sold';
    return $vars;
});
add_filter('template_include', function ($template) {
    if (get_query_var('cb_school_slug')) {
        $located = locate_template('template-school.php');
        if ($located) { return $located; }
    }
    if (get_query_var('cb_recently_sold')) {
        $located = locate_template('template-recently-sold.php');
        if ($located) { return $located; }
    }
    return $template;
});
add_filter('query_vars', function ($vars) {
    $vars[] = 'cb_community_slug';
    return $vars;
});
add_filter('template_include', function ($template) {
    if (get_query_var('cb_community_slug')) {
        $located = locate_template('template-community.php');
        if ($located) { return $located; }
    }
    return $template;
});

/* ==========================================================================
   LISTING DETAIL ROUTING (/listing/<slug>-<id>/)
   ========================================================================== */
add_action('init', function () {
    // Match /listing/<anything-with-trailing-id>/ where trailing id is 20+ alphanumeric chars.
    add_rewrite_rule(
        '^listing/[^/]*?([a-zA-Z0-9]{20,})/?$',
        'index.php?cb_listing_id=$matches[1]',
        'top'
    );
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'cb_listing_id';
    return $vars;
});

add_filter('template_include', function ($template) {
    if (get_query_var('cb_listing_id')) {
        $located = locate_template('single-cb_listing.php');
        if ($located) { return $located; }
    }
    return $template;
});

/* ==========================================================================
   LISTINGS SITEMAP (/listings-sitemap.xml)
   Adds every active SAARMLS listing's slugged URL to a custom sitemap so search
   engines can discover them. Cached for 6 hours.
   ========================================================================== */
add_action('init', function () {
    // Use /cb-listings.xml to avoid Yoast's "*-sitemap.xml" rewrite rule.
    add_rewrite_rule('^cb-listings\.xml/?$', 'index.php?cb_listings_sitemap=1', 'top');
});
add_filter('query_vars', function ($vars) {
    $vars[] = 'cb_listings_sitemap';
    return $vars;
});
add_action('template_redirect', function () {
    if (!get_query_var('cb_listings_sitemap')) { return; }

    $cache_key = 'cb_listings_sitemap_xml';
    $xml       = get_transient($cache_key);
    if ($xml === false) {
        $client = new CB_Spark_Client();
        $items  = [];

        // Always seed with community + school landing pages so they're indexed too.
        if (function_exists('cb_get_communities')) {
            foreach (cb_get_communities() as $slug => $c) {
                $items[] = ['loc' => home_url('/communities/' . $slug . '/'), 'lastmod' => gmdate('c')];
            }
        }
        if (function_exists('cb_get_schools')) {
            foreach (cb_get_schools() as $slug => $s) {
                $items[] = ['loc' => home_url('/schools/' . $slug . '/'), 'lastmod' => gmdate('c')];
            }
        }
        // Index pages
        $items[] = ['loc' => home_url('/communities/'),    'lastmod' => gmdate('c')];
        $items[] = ['loc' => home_url('/schools/'),        'lastmod' => gmdate('c')];
        $items[] = ['loc' => home_url('/recently-sold/'),  'lastmod' => gmdate('c')];

        $page   = 1;
        // Paginate through all active listings (cap at 20 pages × 50 = 1000 listings).
        do {
            $data = $client->get('listings', [
                '_filter'  => "StandardStatus Eq 'Active'",
                '_select'  => 'Id,UnparsedAddress,ListingUpdateTimestamp',
                '_limit'   => 50,
                '_page'    => $page,
                '_pagination' => 1,
            ]);
            if (is_wp_error($data) || empty($data['Results'])) { break; }
            foreach ($data['Results'] as $row) {
                $f = $row['StandardFields'] ?? [];
                $f['Id'] = $row['Id'] ?? '';
                if (!$f['Id']) { continue; }
                $items[] = [
                    'loc'     => CB_Spark_Client::detail_url($f),
                    'lastmod' => $f['ListingUpdateTimestamp'] ?? gmdate('c'),
                ];
            }
            $total_pages = $data['Pagination']['TotalPages'] ?? 1;
            $page++;
        } while ($page <= $total_pages && $page <= 50);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($items as $it) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . esc_url($it['loc']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . esc_html($it['lastmod']) . '</lastmod>' . "\n";
            $xml .= '    <changefreq>daily</changefreq>' . "\n";
            $xml .= '    <priority>0.8</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        set_transient($cache_key, $xml, 6 * HOUR_IN_SECONDS);
    }

    header('Content-Type: application/xml; charset=UTF-8');
    echo $xml;
    exit;
});

/* ==========================================================================
   CUSTOM POST TYPES
   ========================================================================== */
function cb_register_post_types() {
    // Agents
    register_post_type('cb_agent', [
        'labels' => [
            'name'          => __('Agents', 'cb-legacy'),
            'singular_name' => __('Agent', 'cb-legacy'),
            'add_new_item'  => __('Add New Agent', 'cb-legacy'),
            'edit_item'     => __('Edit Agent', 'cb-legacy'),
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'agents'],
        'menu_icon'    => 'dashicons-businessperson',
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,
    ]);

    // Communities
    register_post_type('cb_community', [
        'labels' => [
            'name'          => __('Communities', 'cb-legacy'),
            'singular_name' => __('Community', 'cb-legacy'),
            'add_new_item'  => __('Add New Community', 'cb-legacy'),
            'edit_item'     => __('Edit Community', 'cb-legacy'),
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'communities'],
        'menu_icon'    => 'dashicons-location',
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,
    ]);

    // Testimonials
    register_post_type('cb_testimonial', [
        'labels' => [
            'name'          => __('Testimonials', 'cb-legacy'),
            'singular_name' => __('Testimonial', 'cb-legacy'),
        ],
        'public'       => true,
        'has_archive'  => false,
        'rewrite'      => ['slug' => 'testimonials'],
        'menu_icon'    => 'dashicons-format-quote',
        'supports'     => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
    ]);

    // Events
    register_post_type('cb_event', [
        'labels' => [
            'name'          => __('Events', 'cb-legacy'),
            'singular_name' => __('Event', 'cb-legacy'),
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'events'],
        'menu_icon'    => 'dashicons-calendar-alt',
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'cb_register_post_types');

/* ==========================================================================
   CUSTOM TAXONOMIES
   ========================================================================== */
function cb_register_taxonomies() {
    register_taxonomy('agent_specialization', 'cb_agent', [
        'labels' => [
            'name'          => __('Specializations', 'cb-legacy'),
            'singular_name' => __('Specialization', 'cb-legacy'),
        ],
        'public'       => true,
        'hierarchical' => true,
        'rewrite'      => ['slug' => 'specialization'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('agent_team', 'cb_agent', [
        'labels' => [
            'name'          => __('Teams', 'cb-legacy'),
            'singular_name' => __('Team', 'cb-legacy'),
        ],
        'public'       => true,
        'hierarchical' => true,
        'rewrite'      => ['slug' => 'team'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('agent_language', 'cb_agent', [
        'labels' => [
            'name'          => __('Languages', 'cb-legacy'),
            'singular_name' => __('Language', 'cb-legacy'),
        ],
        'public'       => true,
        'hierarchical' => true,
        'rewrite'      => ['slug' => 'language'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('community_region', 'cb_community', [
        'labels' => [
            'name'          => __('Regions', 'cb-legacy'),
            'singular_name' => __('Region', 'cb-legacy'),
        ],
        'public'       => true,
        'hierarchical' => true,
        'rewrite'      => ['slug' => 'region'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'cb_register_taxonomies');

/* ==========================================================================
   REST API ENDPOINTS
   ========================================================================== */
function cb_register_rest_routes() {
    register_rest_route('cb/v1', '/agents', [
        'methods'             => 'GET',
        'callback'            => 'cb_get_agents',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('cb/v1', '/communities', [
        'methods'             => 'GET',
        'callback'            => 'cb_rest_get_communities',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('cb/v1', '/stats', [
        'methods'             => 'GET',
        'callback'            => 'cb_get_stats',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'cb_register_rest_routes');

function cb_get_agents($request) {
    $args = [
        'post_type'      => 'cb_agent',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];

    $search = $request->get_param('search');
    if ($search) {
        $args['s'] = sanitize_text_field($search);
    }

    $team = $request->get_param('team');
    if ($team) {
        $args['tax_query'] = [[
            'taxonomy' => 'agent_team',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($team),
        ]];
    }

    $query = new WP_Query($args);
    $agents = [];

    foreach ($query->posts as $post) {
        $agents[] = [
            'id'        => $post->ID,
            'name'      => $post->post_title,
            'excerpt'   => get_the_excerpt($post),
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'cb-agent'),
            'permalink' => get_permalink($post->ID),
            'meta'      => get_post_meta($post->ID),
        ];
    }

    return rest_ensure_response($agents);
}

function cb_rest_get_communities($request) {
    $query = new WP_Query([
        'post_type'      => 'cb_community',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    $communities = [];
    foreach ($query->posts as $post) {
        $communities[] = [
            'id'        => $post->ID,
            'name'      => $post->post_title,
            'excerpt'   => get_the_excerpt($post),
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'cb-community'),
            'permalink' => get_permalink($post->ID),
        ];
    }

    return rest_ensure_response($communities);
}

function cb_get_stats() {
    $cached = get_transient('cb_site_stats');
    if ($cached) {
        return rest_ensure_response($cached);
    }

    $stats = [
        'homes_sold'  => get_option('cb_homes_sold', '1200'),
        'agents'      => wp_count_posts('cb_agent')->publish ?: '30',
        // NOTE: get_option, but the Customizer writes cb_years_serving as a
        // THEME MOD -- so editing it in the Customizer never reaches this
        // endpoint and it falls through to the default. Same for cb_homes_sold
        // above. Left as-is rather than silently changing what the REST stats
        // endpoint returns; flagged separately.
        'years'       => get_option('cb_years_serving', '35'),
        'communities' => wp_count_posts('cb_community')->publish ?: '20',
    ];

    set_transient('cb_site_stats', $stats, HOUR_IN_SECONDS);

    return rest_ensure_response($stats);
}

/* ==========================================================================
   WIDGET AREAS
   ========================================================================== */
function cb_register_widgets() {
    register_sidebar([
        'name'          => __('Footer Widget 1', 'cb-legacy'),
        'id'            => 'footer-1',
        'before_widget' => '<div class="cb-footer-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="cb-footer__heading">',
        'after_title'   => '</h4>',
    ]);
}
add_action('widgets_init', 'cb_register_widgets');

/* ==========================================================================
   THEME CUSTOMIZER
   ========================================================================== */
function cb_customize_register($wp_customize) {
    // Hero Section
    $wp_customize->add_section('cb_hero', [
        'title'    => __('Homepage Hero', 'cb-legacy'),
        'priority' => 30,
    ]);

    $wp_customize->add_setting('cb_hero_title', [
        'default'           => 'Find Your Dream Home in San Angelo',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    $wp_customize->add_control('cb_hero_title', [
        'label'   => __('Hero Title', 'cb-legacy'),
        'section' => 'cb_hero',
        'type'    => 'text',
    ]);

    $wp_customize->add_setting('cb_hero_subtitle', [
        'default'           => 'Discover luxury living with Coldwell Banker Legacy. Your trusted partner in San Angelo real estate.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ]);

    $wp_customize->add_control('cb_hero_subtitle', [
        'label'   => __('Hero Subtitle', 'cb-legacy'),
        'section' => 'cb_hero',
        'type'    => 'textarea',
    ]);

    $wp_customize->add_setting('cb_hero_video', [
        'sanitize_callback' => 'esc_url_raw',
    ]);

    $wp_customize->add_control('cb_hero_video', [
        'label'   => __('Hero Video URL (MP4)', 'cb-legacy'),
        'section' => 'cb_hero',
        'type'    => 'url',
    ]);

    $wp_customize->add_setting('cb_hero_image', [
        'sanitize_callback' => 'absint',
    ]);

    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'cb_hero_image', [
        'label'     => __('Hero Background Image', 'cb-legacy'),
        'section'   => 'cb_hero',
        'mime_type' => 'image',
    ]));

    // Stats Section
    $wp_customize->add_section('cb_stats_section', [
        'title'    => __('Stats Section', 'cb-legacy'),
        'priority' => 35,
    ]);

    /*
     * GLOBAL (Coldwell Banker network) figures -- what the homepage Legacy panel
     * shows since the client asked for global rather than local stats.
     *
     * Editable rather than hardcoded because Coldwell Banker revises its
     * corporate boilerplate periodically; a number frozen in a template quietly
     * becomes a false brand claim. The defaults are CB's long-standing published
     * figures and should be confirmed against the current brand/press boilerplate
     * before being treated as approved marketing copy.
     *
     * The LOCAL settings below (cb_homes_sold, cb_years_serving) are intentionally
     * kept: the older home variants and other templates still render them.
     */
    $cb_global_stats = [
        'cb_global_agents'    => ['100000', __('Global: Affiliated Agents Worldwide', 'cb-legacy')],
        'cb_global_offices'   => ['2700',   __('Global: Offices Worldwide', 'cb-legacy')],
        'cb_global_countries' => ['40',     __('Global: Countries & Territories', 'cb-legacy')],
    ];
    foreach ($cb_global_stats as $cb_gs_key => $cb_gs) {
        $wp_customize->add_setting($cb_gs_key, [
            'default'           => $cb_gs[0],
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        $wp_customize->add_control($cb_gs_key, [
            'label'   => $cb_gs[1],
            'section' => 'cb_stats_section',
            'type'    => 'text',
        ]);
    }

    $wp_customize->add_setting('cb_homes_sold', [
        'default'           => '1200',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    $wp_customize->add_control('cb_homes_sold', [
        'label'   => __('Homes Sold Count', 'cb-legacy'),
        'section' => 'cb_stats_section',
        'type'    => 'text',
    ]);

    // 35, not 25. Coldwell Banker Legacy's own San Angelo office page says "For
    // over 35 years we have had the privilege of providing value-based real
    // estate services". 25 also contradicted the "Since 2000" eyebrow it shipped
    // beside (2026 - 2000 = 26). Confirmed correct by the client.
    $wp_customize->add_setting('cb_years_serving', [
        'default'           => '35',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    $wp_customize->add_control('cb_years_serving', [
        'label'   => __('Years Serving', 'cb-legacy'),
        'section' => 'cb_stats_section',
        'type'    => 'text',
    ]);

    // Contact Info
    $wp_customize->add_section('cb_contact', [
        'title'    => __('Contact Information', 'cb-legacy'),
        'priority' => 40,
    ]);

    $wp_customize->add_setting('cb_phone', [
        'default'           => '(325) 944-9559',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    $wp_customize->add_control('cb_phone', [
        'label'   => __('Phone Number', 'cb-legacy'),
        'section' => 'cb_contact',
        'type'    => 'text',
    ]);

    $wp_customize->add_setting('cb_address', [
        'default'           => '3017 Knickerbocker, San Angelo, TX 76904',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    $wp_customize->add_control('cb_address', [
        'label'   => __('Office Address', 'cb-legacy'),
        'section' => 'cb_contact',
        'type'    => 'text',
    ]);

    $wp_customize->add_setting('cb_email', [
        'default'           => 'info@cbltexas.com',
        'sanitize_callback' => 'sanitize_email',
    ]);

    $wp_customize->add_control('cb_email', [
        'label'   => __('Email Address', 'cb-legacy'),
        'section' => 'cb_contact',
        'type'    => 'email',
    ]);
}
add_action('customize_register', 'cb_customize_register');

/* ==========================================================================
   HELPER FUNCTIONS
   ========================================================================== */
function cb_get_svg_icon($icon) {
    $icons = [
        'search' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
        'home' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'sell' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
        'team' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'office' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>',
        'phone' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'email' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
        'map-pin' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'bed' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/></svg>',
        'bath' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16a1 1 0 0 1 1 1v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3a1 1 0 0 1 1-1z"/><path d="M6 12V5a2 2 0 0 1 2-2h3v2.25"/><path d="M4 21l1-1.5"/><path d="M20 21l-1-1.5"/></svg>',
        'sqft' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>',
        'chevron-down' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>',
        'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
        'linkedin' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'youtube' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
    ];

    return $icons[$icon] ?? '';
}

/* ==========================================================================
   EXCERPT LENGTH
   ========================================================================== */
function cb_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'cb_excerpt_length');

function cb_excerpt_more($more) {
    return '&hellip;';
}
add_filter('excerpt_more', 'cb_excerpt_more');

/* ==========================================================================
   BODY CLASSES
   ========================================================================== */
function cb_body_classes($classes) {
    if (is_front_page()) {
        $classes[] = 'cb-page--home';
    }
    // Home 2 preview shares the homepage's cinematic chrome (transparent header,
    // full-bleed hero) without being the front page.
    if (is_page_template(CB_HOME2_TEMPLATE)) {
        $classes[] = 'cb-page--home';
        $classes[] = 'cb-page--home2-preview';
    }
    // Home 7 — corridor walkthrough
    if (defined('CB_HOME7_TEMPLATE') && is_page_template(CB_HOME7_TEMPLATE)) {
        $classes[] = 'cb-page--home';
        $classes[] = 'cb-page--home7-preview';
    }
    return $classes;
}
add_filter('body_class', 'cb_body_classes');

/* ==========================================================================
   FORM HANDLERS
   ========================================================================== */
require_once CB_THEME_DIR . '/inc/form-handler.php';

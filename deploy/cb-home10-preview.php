<?php
/**
 * Plugin Name: CB — Home 10 Filmed Walkthrough Preview (loader)
 * Description: Self-contained loader for the un-linked "Home 10 — The House,
 *   filmed" concept — Home 9's content and walk, but the house is photoreal
 *   stills joined by filmed camera moves instead of real-time WebGL. Acts ONLY
 *   on the "Home 10 — The House, filmed" template. Theme files and all other
 *   previews left UNCHANGED.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (!defined('CB_HOME10_TEMPLATE')) {
    define('CB_HOME10_TEMPLATE', 'templates/template-home10-filmed.php');
}

if (!function_exists('cb_home10_enqueue')) {
    function cb_home10_enqueue() {
        if (!is_page_template(CB_HOME10_TEMPLATE)) { return; }
        $uri = get_template_directory_uri();
        $ver = (defined('CB_THEME_VERSION') ? CB_THEME_VERSION : '1.1.0') . '-h10';

        // BOTH stylesheets, in this order, and both unconditionally.
        //
        // cb-home9.css supplies the styling INSIDE each panel -- headings,
        // cards, buttons, grids -- because the panels are Home 9's markup with
        // only their outer wrapper renamed. Its floating layout is nested under
        // html.cb9-on, which this page never sets, so what we inherit is the
        // flat component look and none of Home 9's positioning.
        //
        // cb-home10.css then owns the shell: the fixed stage, panel placement
        // and the nav. Its floating rules are likewise nested under .cb10-on,
        // added by home10.js. Without JS this is a plain, readable, stacked
        // page -- which is the whole point of loading the CSS unconditionally.
        wp_enqueue_style('cb-home9', $uri . '/assets/css/cb-home9.css', ['cb-legacy-style'], $ver);
        wp_enqueue_style('cb-home10', $uri . '/assets/css/cb-home10.css', ['cb-home9'], $ver);

        // NOTE: home10.js is deliberately NOT enqueued. PHP cannot see the
        // viewport or the connection, and this page pulls ~16MB of video once
        // it starts. cb_home10_gate() injects it from the client, only after
        // the same gates below pass.
    }
    add_action('wp_enqueue_scripts', 'cb_home10_enqueue');
}

if (!function_exists('cb_home10_gate')) {
    /**
     * Client-side asset gate.
     *
     * Home 10 needs no vendor libraries at all -- no Three, no GLTFLoader, no
     * Motion -- so this loads exactly one file. The gates are Home 9's, plus one
     * Home 9 does not need:
     *
     *   min-width 1025px      desktop only, as Home 8 and 9 already are
     *   no-preference motion  a filmed walk is motion; honour the preference
     *   not coarse pointer    the walk is scroll-driven
     *   not Save-Data         home10.js sets preload="auto" on all eight clips,
     *                         so entering this page commits to roughly 16MB.
     *                         Serving that to someone who has explicitly asked
     *                         their browser to conserve data is indefensible,
     *                         and the flat fallback costs them nothing.
     *
     * Any gate failing is non-fatal: the stylesheets are already loaded and the
     * flat stacked page is what remains. home10.js's own init() also returns
     * false rather than half-starting if the stage element is missing.
     */
    function cb_home10_gate() {
        if (!is_page_template(CB_HOME10_TEMPLATE)) { return; }
        $uri  = get_template_directory_uri();
        $ver  = (defined('CB_THEME_VERSION') ? CB_THEME_VERSION : '1.1.0') . '-h10';
        $eng  = esc_js($uri . '/assets/js/cb-home10/home10.js?ver=' . $ver);
        $base = esc_js($uri . '/assets/');
        ?>
        <script id="cb-home10-gate">
        (function () {
          try {
            var ok = window.matchMedia('(min-width: 1025px)').matches
                  && window.matchMedia('(prefers-reduced-motion: no-preference)').matches
                  && !window.matchMedia('(pointer: coarse)').matches;
            var c = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            if (c && c.saveData) { ok = false; }
            if (!ok) { return; }
            var s = document.createElement('script');
            s.src = '<?php echo $eng; ?>';
            s.async = false;
            s.onload = function () {
              if (window.CBHome10) {
                window.CBHome10.init({
                  stage: '#cb10-stage',
                  basePath: '<?php echo $base; ?>'
                });
              }
            };
            s.onerror = function () {
              if (window.console) { console.warn('[cb10] engine failed to load; using flat fallback.'); }
            };
            document.head.appendChild(s);
          } catch (e) {
            document.documentElement.classList.remove('cb10-on');
            if (window.console) { console.warn('[cb10] gate failed; using flat fallback.', e); }
          }
        })();
        </script>
        <?php
    }
    add_action('wp_footer', 'cb_home10_gate', 20);
}

if (!function_exists('cb_home10_body_class')) {
    function cb_home10_body_class($classes) {
        if (is_page_template(CB_HOME10_TEMPLATE)) {
            $classes[] = 'cb-page--home';
            $classes[] = 'cb-page--home10-preview';
        }
        return $classes;
    }
    add_filter('body_class', 'cb_home10_body_class');
}

if (!function_exists('cb_home10_sitemap_exclude')) {
    function cb_home10_sitemap_exclude($args, $post_type) {
        if ($post_type !== 'page') { return $args; }
        $mq = isset($args['meta_query']) ? $args['meta_query'] : [];
        $mq[] = [
            'relation' => 'OR',
            ['key' => '_wp_page_template', 'value' => CB_HOME10_TEMPLATE, 'compare' => '!='],
            ['key' => '_wp_page_template', 'compare' => 'NOT EXISTS'],
        ];
        $args['meta_query'] = $mq;
        return $args;
    }
    add_filter('wp_sitemaps_posts_query_args', 'cb_home10_sitemap_exclude', 10, 2);
}

if (!function_exists('cb_home10_suppress_extras')) {
    function cb_home10_suppress_extras() {
        if (!is_page_template(CB_HOME10_TEMPLATE)) { return; }
        remove_action('wp_head', 'cb_analytics_head', 1);
        remove_action('wp_head', 'cb_brokerage_schema', 5);
    }
    add_action('template_redirect', 'cb_home10_suppress_extras');
}

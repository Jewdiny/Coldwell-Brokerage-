<?php
/**
 * Plugin Name: CB — Home 10 Filmed Walkthrough Preview (loader)
 * Description: Self-contained loader for the un-linked "Home 10 — The House,
 *   filmed" concept — Home 9's content and walk, but the house is photoreal
 *   stills joined by filmed camera moves instead of real-time WebGL. Acts on the
 *   "Home 10 — The House, filmed" template AND on the retired Home 9 template,
 *   which now renders Home 10 so pre-existing pages keep their URLs. Replaces
 *   cb-home9-preview.php, which was deleted with the Home 9 engine. Theme files
 *   and all other previews left UNCHANGED.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (!defined('CB_HOME10_TEMPLATE')) {
    define('CB_HOME10_TEMPLATE', 'templates/template-home10-filmed.php');
}

// The retired Home 9 template, which now renders Home 10. Pages published
// before the swap still carry it in _wp_page_template, so it has to load the
// same assets or those URLs get the markup with no stylesheet and no engine.
if (!defined('CB_HOME10_ALIAS_TEMPLATE')) {
    define('CB_HOME10_ALIAS_TEMPLATE', 'templates/template-home9-house.php');
}

if (!function_exists('cb_home10_is_target')) {
    /**
     * Everywhere Home 10 renders: the site's FRONT PAGE (front-page.php now
     * renders the Home 10 partial), the Home 10 page template, and the retired
     * Home 9 alias.
     *
     * is_front_page() is what makes the official homepage load the stylesheets
     * and the engine at all. Without it the homepage would render Home 10's
     * markup with neither, which is a stack of unstyled cards.
     */
    function cb_home10_is_target() {
        return is_front_page()
            || is_page_template(CB_HOME10_TEMPLATE)
            || is_page_template(CB_HOME10_ALIAS_TEMPLATE);
    }
}

if (!function_exists('cb_home10_enqueue')) {
    function cb_home10_enqueue() {
        if (!cb_home10_is_target()) { return; }
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
     * Motion -- so this loads exactly one file.
     *
     * RUNS ON PHONES AND TABLETS. The min-width:1025px and pointer:coarse gates
     * are gone. They were never about capability -- there is no WebGL here, just
     * h264, which every phone decodes in hardware. They were about BANDWIDTH:
     * home10.js used to set preload="auto" on all eight clips, so opening the
     * page committed to ~16MB before the reader scrolled once. It now preloads
     * metadata only and buffers the current clip plus the next as you walk, so
     * a phone fetches what it is about to watch and nothing more. That removed
     * the actual objection, so the gate came off.
     *
     * What is still checked:
     *
     *   no-preference motion  a filmed walk is motion; honour the preference
     *   not Save-Data         an explicit "conserve data" request. The flat
     *                         fallback is the whole page minus the video.
     *   not 2g               effectiveType 2g/slow-2g will not stream 1280x720
     *                         faster than it can be watched, so the walk would
     *                         be a slideshow of stalls. Better to skip it and
     *                         serve the readable page immediately.
     *
     * Any gate failing is non-fatal: the stylesheets are already loaded and the
     * flat stacked page is what remains. home10.js's own init() also returns
     * false rather than half-starting if the stage element is missing.
     */
    function cb_home10_gate() {
        if (!cb_home10_is_target()) { return; }
        $uri  = get_template_directory_uri();
        $ver  = (defined('CB_THEME_VERSION') ? CB_THEME_VERSION : '1.1.0') . '-h10';
        $eng  = esc_js($uri . '/assets/js/cb-home10/home10.js?ver=' . $ver);
        $base = esc_js($uri . '/assets/');
        ?>
        <script id="cb-home10-gate">
        (function () {
          try {
            var ok = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
            var c = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            if (c && c.saveData) { ok = false; }
            if (c && /(^|\b)(slow-)?2g$/.test(c.effectiveType || '')) { ok = false; }
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
        if (cb_home10_is_target()) {
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
        // BOTH templates. The retired Home 9 alias was previously kept out of
        // the sitemap by deploy/cb-home9-preview.php, which no longer exists --
        // miss it here and a noindex preview starts getting submitted to search
        // engines. Entries are ANDed at the top level, so one clause each.
        foreach ([CB_HOME10_TEMPLATE, CB_HOME10_ALIAS_TEMPLATE] as $tpl) {
            $mq[] = [
                'relation' => 'OR',
                ['key' => '_wp_page_template', 'value' => $tpl, 'compare' => '!='],
                ['key' => '_wp_page_template', 'compare' => 'NOT EXISTS'],
            ];
        }
        $args['meta_query'] = $mq;
        return $args;
    }
    add_filter('wp_sitemaps_posts_query_args', 'cb_home10_sitemap_exclude', 10, 2);
}

if (!function_exists('cb_home10_suppress_extras')) {
    function cb_home10_suppress_extras() {
        // PREVIEWS ONLY -- never the homepage.
        //
        // This exists so an un-linked preview does not pollute analytics or
        // publish duplicate LocalBusiness schema. Both of those reasons invert
        // on the real front page: it is the single most important page to
        // measure, and the brokerage schema is precisely what should be
        // emitted there. Guarding on cb_home10_is_target() -- which now
        // includes is_front_page() -- would silently strip both from the
        // homepage the moment Home 10 was promoted.
        if (is_front_page()) { return; }
        if (!cb_home10_is_target()) { return; }
        remove_action('wp_head', 'cb_analytics_head', 1);
        remove_action('wp_head', 'cb_brokerage_schema', 5);
    }
    add_action('template_redirect', 'cb_home10_suppress_extras');
}

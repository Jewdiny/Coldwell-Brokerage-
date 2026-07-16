<?php
/**
 * Plugin Name: CB â€” Home 9 Floating Pages Preview (loader)
 * Description: Self-contained loader for the un-linked /home-8/ "floating pages"
 *   concept â€” Home 2's content on Home 5's hallway camera, with each section
 *   floating in the corridor as its own page that zooms in as you scroll to it
 *   and scrolls its live content internally. Acts ONLY on the "Home 9 â€” Floating
 *   Pages" template. Theme files and all other previews left UNCHANGED.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (!defined('CB_HOME9_TEMPLATE')) {
    define('CB_HOME9_TEMPLATE', 'templates/template-home9-house.php');
}

if (!function_exists('cb_home9_enqueue')) {
    function cb_home9_enqueue() {
        if (!is_page_template(CB_HOME9_TEMPLATE)) { return; }
        $uri = get_template_directory_uri();
        $ver = (defined('CB_THEME_VERSION') ? CB_THEME_VERSION : '1.1.0') . '-h1';

        // The stylesheet is always loaded: without JS it IS the page. The flat
        // Home 2 layout is the default and every floating rule is nested under
        // html.cb9-on, which only home9.js adds.
        wp_enqueue_style('cb-home9', $uri . '/assets/css/cb-home9.css', ['cb-legacy-style'], $ver);

        // NOTE: three.min.js, motion.js, cursor.js and home9.js are deliberately
        // NOT enqueued here. PHP cannot see the viewport, so wp_enqueue_script
        // would make every phone download ~290kB gzipped of Three + Motion for a
        // house that is gated off below 1025px anyway. cb_home9_gate() injects
        // them from the client only once the same three gates pass.
    }
    add_action('wp_enqueue_scripts', 'cb_home9_enqueue');
}

if (!function_exists('cb_home9_gate')) {
    /**
     * Client-side asset gate. Loads the engine only on desktop + fine pointer +
     * motion-OK, in strict order (Three -> cursor -> Motion -> engine), then
     * inits. Any asset failing is non-fatal: home9.js treats Motion as optional
     * and, with no Three, init() returns false and the flat layout stands.
     */
    function cb_home9_gate() {
        if (!is_page_template(CB_HOME9_TEMPLATE)) { return; }
        $uri   = get_template_directory_uri();
        $ver   = (defined('CB_THEME_VERSION') ? CB_THEME_VERSION : '1.1.0') . '-h1';
        $three = esc_js($uri . '/assets/js/vendor/three.min.js?ver=0.160.0');
        $curs  = esc_js($uri . '/assets/js/cb-webgl/cursor.js?ver=' . $ver);
        $motio = esc_js($uri . '/assets/js/vendor/motion.js?ver=12.42.2');
        $eng   = esc_js($uri . '/assets/js/cb-home9/home9.js?ver=' . $ver);
        $mono  = esc_js($uri . '/assets/images/logos/monogram.svg');
        $monoS = esc_js($uri . '/assets/images/logos/monogram-vertical-stacked.svg');
        // Home 2's scene plates, which hang framed on the room walls. Same
        // `basePath` directory contract Home 2's own WebGL loader uses.
        $base  = esc_js($uri . '/assets/images/webgl/');
        ?>
        <script id="cb-home9-gate">
        (function () {
          try {
            var ok = window.matchMedia('(min-width: 1025px)').matches
                  && window.matchMedia('(prefers-reduced-motion: no-preference)').matches
                  && !window.matchMedia('(pointer: coarse)').matches;
            if (!ok) { return; }
            var urls = ['<?php echo $three; ?>', '<?php echo $curs; ?>', '<?php echo $motio; ?>', '<?php echo $eng; ?>'];
            (function next(i) {
              if (i >= urls.length) {
                if (window.CBHome9) {
                  window.CBHome9.init({
                    canvas: '#cb9-canvas',
                    basePath: '<?php echo $base; ?>',
                    monogram: '<?php echo $mono; ?>',
                    monogramStacked: '<?php echo $monoS; ?>'
                  });
                }
                return;
              }
              var s = document.createElement('script');
              s.src = urls[i];
              s.async = false;
              s.onload = function () { next(i + 1); };
              s.onerror = function () {
                if (window.console) { console.warn('[cb9] asset failed, continuing:', urls[i]); }
                next(i + 1);
              };
              document.head.appendChild(s);
            })(0);
          } catch (e) {
            document.documentElement.classList.remove('cb9-on');
            if (window.console) { console.warn('[cb9] gate failed; using flat fallback.', e); }
          }
        })();
        </script>
        <?php
    }
    add_action('wp_footer', 'cb_home9_gate', 20);
}

if (!function_exists('cb_home9_body_class')) {
    function cb_home9_body_class($classes) {
        if (is_page_template(CB_HOME9_TEMPLATE)) {
            $classes[] = 'cb-page--home';
            $classes[] = 'cb-page--home9-preview';
        }
        return $classes;
    }
    add_filter('body_class', 'cb_home9_body_class');
}

if (!function_exists('cb_home9_sitemap_exclude')) {
    function cb_home9_sitemap_exclude($args, $post_type) {
        if ($post_type !== 'page') { return $args; }
        $mq = isset($args['meta_query']) ? $args['meta_query'] : [];
        $mq[] = [
            'relation' => 'OR',
            ['key' => '_wp_page_template', 'value' => CB_HOME9_TEMPLATE, 'compare' => '!='],
            ['key' => '_wp_page_template', 'compare' => 'NOT EXISTS'],
        ];
        $args['meta_query'] = $mq;
        return $args;
    }
    add_filter('wp_sitemaps_posts_query_args', 'cb_home9_sitemap_exclude', 10, 2);
}

if (!function_exists('cb_home9_suppress_extras')) {
    function cb_home9_suppress_extras() {
        if (!is_page_template(CB_HOME9_TEMPLATE)) { return; }
        remove_action('wp_head', 'cb_analytics_head', 1);
        remove_action('wp_head', 'cb_brokerage_schema', 5);
    }
    add_action('template_redirect', 'cb_home9_suppress_extras');
}


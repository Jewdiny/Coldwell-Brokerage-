<?php
/**
 * Plugin Name: CB — Home 4 Fusion Preview (loader)
 * Description: Self-contained loader for the un-linked /home-4/ "fusion" homepage
 *   concept (Three.js WebGL dust-nebula + custom cursor + glass cards that crumble
 *   into the field). Lives in wp-content/mu-plugins/ and acts ONLY on pages using
 *   the "Home 4 — Fusion Preview" template — the active theme's existing files and
 *   the Home 2 / Home 3 previews are NOT modified. To fully remove: delete this
 *   file and the "Home 4" page. When the full theme is later deployed, DELETE this
 *   loader (functions.php would then own the logic).
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (!defined('CB_HOME4_TEMPLATE')) {
    define('CB_HOME4_TEMPLATE', 'templates/template-home4-fusion.php');
}

/* Enqueue the fusion stack ON THE PREVIEW TEMPLATE ONLY. Reuses the theme's
   vendored Three.js (already present for Home 2) and the standalone cursor.js. */
if (!function_exists('cb_home4_enqueue')) {
    function cb_home4_enqueue() {
        if (!is_page_template(CB_HOME4_TEMPLATE)) { return; }
        $uri = get_template_directory_uri();
        // Suffix bumped per cb-fusion asset change so browsers/CDN fetch fresh.
        $ver = (defined('CB_THEME_VERSION') ? CB_THEME_VERSION : '1.1.0') . '-f3';

        wp_enqueue_style('cb-fusion', $uri . '/assets/css/cb-fusion.css', ['cb-legacy-style'], $ver);

        // Three.js (r160, already vendored for Home 2) + the standalone cursor +
        // the fusion engine. Order matters: three -> cursor -> fusion.
        wp_enqueue_script('three',      $uri . '/assets/js/vendor/three.min.js',  [], '0.160.0', true);
        wp_enqueue_script('cb-cursor',  $uri . '/assets/js/cb-webgl/cursor.js',   [], $ver, true);
        wp_enqueue_script('cb-fusion',  $uri . '/assets/js/cb-fusion/fusion.js',  ['three', 'cb-cursor'], $ver, true);

        // Gate init() to >=1025px desktop + motion + fine pointer (same eligibility
        // rule as Home 2 / Home 3); try/catch restores the static fallback.
        wp_add_inline_script(
            'cb-fusion',
            "(function(){try{var ok=window.matchMedia('(min-width: 1025px)').matches"
            . "&&window.matchMedia('(prefers-reduced-motion: no-preference)').matches"
            . "&&!window.matchMedia('(pointer: coarse)').matches;"
            . "if(ok&&window.CBFusion){window.CBFusion.init({canvas:'#cb-fusion-canvas'});}"
            . "}catch(e){document.documentElement.classList.remove('cb-fusion-on');"
            . "if(window.console){console.warn('[cb-fusion] init failed; using fallback.',e);}}})();",
            'after'
        );
    }
    add_action('wp_enqueue_scripts', 'cb_home4_enqueue');
}

/* Body classes for the overlay header + preview-scoped styling. */
if (!function_exists('cb_home4_body_class')) {
    function cb_home4_body_class($classes) {
        if (is_page_template(CB_HOME4_TEMPLATE)) {
            $classes[] = 'cb-page--home';
            $classes[] = 'cb-page--home4-preview';
        }
        return $classes;
    }
    add_filter('body_class', 'cb_home4_body_class');
}

/* Keep the preview out of WordPress core's /wp-sitemap.xml. */
if (!function_exists('cb_home4_sitemap_exclude')) {
    function cb_home4_sitemap_exclude($args, $post_type) {
        if ($post_type !== 'page') { return $args; }
        $mq = isset($args['meta_query']) ? $args['meta_query'] : [];
        $mq[] = [
            'relation' => 'OR',
            ['key' => '_wp_page_template', 'value' => CB_HOME4_TEMPLATE, 'compare' => '!='],
            ['key' => '_wp_page_template', 'compare' => 'NOT EXISTS'],
        ];
        $args['meta_query'] = $mq;
        return $args;
    }
    add_filter('wp_sitemaps_posts_query_args', 'cb_home4_sitemap_exclude', 10, 2);
}

/* Don't pollute analytics or emit brokerage JSON-LD on the noindex preview. */
if (!function_exists('cb_home4_suppress_extras')) {
    function cb_home4_suppress_extras() {
        if (!is_page_template(CB_HOME4_TEMPLATE)) { return; }
        remove_action('wp_head', 'cb_analytics_head', 1);
        remove_action('wp_head', 'cb_brokerage_schema', 5);
    }
    add_action('template_redirect', 'cb_home4_suppress_extras');
}

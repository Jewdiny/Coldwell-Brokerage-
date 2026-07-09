<?php
/**
 * Plugin Name: CB — Home 2 Cinematic WebGL Preview (loader)
 * Description: Self-contained loader for the un-linked /home-2/ cinematic WebGL preview.
 *   Lives in wp-content/mu-plugins/ and acts ONLY on pages using the
 *   "Home 2 — Cinematic WebGL Preview" template — the active theme's existing files
 *   (functions.php, header.php, front-page.php, home.js) are NOT modified, so no
 *   current page changes. To fully remove the preview: delete this file and the
 *   "Home 2" page. NOTE: when you later deploy the full cinematic homepage from the
 *   theme, DELETE this loader (the theme's functions.php then owns this logic).
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (!defined('CB_HOME2_TEMPLATE')) {
    define('CB_HOME2_TEMPLATE', 'templates/template-home2-webgl.php');
}

/* Enqueue the cinematic stack ON THE PREVIEW TEMPLATE ONLY. All handles/files are
   additive; the same handle enqueued twice is a no-op, and main.js guards re-init,
   so this stays safe even if the theme later registers the same assets. */
if (!function_exists('cb_home2_enqueue')) {
    function cb_home2_enqueue() {
        if (!is_page_template(CB_HOME2_TEMPLATE)) { return; }
        $uri = get_template_directory_uri();
        $ver = defined('CB_THEME_VERSION') ? CB_THEME_VERSION : '1.1.0';

        // Scene layout (new file; unused elsewhere on the live site).
        wp_enqueue_style('cb-scroll-home', $uri . '/assets/css/scroll-home.css', ['cb-legacy-style'], $ver);
        // NOTE: Lenis is intentionally NOT enqueued. main.js then uses native scroll
        // (the verified-smooth showcase path); Lenis coupled scroll to the WebGL
        // render frame and fought the theme's CSS scroll-behavior, causing jank.

        // WebGL cinematic layer (vendored Three.js + modules). Order matters.
        wp_enqueue_script('three',         $uri . '/assets/js/vendor/three.min.js', [], '0.160.0', true);
        wp_enqueue_script('cb-wg-shaders', $uri . '/assets/js/cb-webgl/shaders.js', ['three'], $ver, true);
        wp_enqueue_script('cb-wg-engine',  $uri . '/assets/js/cb-webgl/engine.js',  ['three', 'cb-wg-shaders'], $ver, true);
        wp_enqueue_script('cb-wg-scenes',  $uri . '/assets/js/cb-webgl/scenes.js',  ['three', 'cb-wg-engine'], $ver, true);
        wp_enqueue_script('cb-wg-cursor',  $uri . '/assets/js/cb-webgl/cursor.js',  ['three'], $ver, true);
        wp_enqueue_script('cb-wg-main',    $uri . '/assets/js/cb-webgl/main.js',    ['three', 'cb-wg-shaders', 'cb-wg-engine', 'cb-wg-scenes', 'cb-wg-cursor'], $ver, true);
        wp_enqueue_style('cb-webgl',       $uri . '/assets/css/cb-webgl.css', ['cb-scroll-home'], $ver);

        // Gate init() to >=1025px desktop + motion + fine pointer (one definition of
        // "eligible"); try/catch restores the DOM fallback instead of a stuck blank canvas.
        $base = esc_js($uri . '/assets/images/webgl/');
        wp_add_inline_script(
            'cb-wg-main',
            "(function(){try{var ok=window.matchMedia('(min-width: 1025px)').matches"
            . "&&window.matchMedia('(prefers-reduced-motion: no-preference)').matches"
            . "&&!window.matchMedia('(pointer: coarse)').matches;"
            . "if(ok&&window.CBWebGL){window.CBWebGL.init({basePath:'{$base}',canvas:'#cb-webgl'});}"
            . "}catch(e){document.documentElement.classList.remove('cb-webgl-on');"
            . "if(window.console){console.warn('[cb] WebGL init failed; using fallback.',e);}}})();",
            'after'
        );
    }
    add_action('wp_enqueue_scripts', 'cb_home2_enqueue');
}

/* Body classes for the overlay header + any preview-scoped styling. */
if (!function_exists('cb_home2_body_class')) {
    function cb_home2_body_class($classes) {
        if (is_page_template(CB_HOME2_TEMPLATE)) {
            $classes[] = 'cb-page--home';
            $classes[] = 'cb-page--home2-preview';
        }
        return $classes;
    }
    add_filter('body_class', 'cb_home2_body_class');
}

/* Keep the preview out of WordPress core's /wp-sitemap.xml (Yoast handled via the
   per-post noindex meta set at page creation). */
if (!function_exists('cb_home2_sitemap_exclude')) {
    function cb_home2_sitemap_exclude($args, $post_type) {
        if ($post_type !== 'page') { return $args; }
        $mq = isset($args['meta_query']) ? $args['meta_query'] : [];
        $mq[] = [
            'relation' => 'OR',
            ['key' => '_wp_page_template', 'value' => CB_HOME2_TEMPLATE, 'compare' => '!='],
            ['key' => '_wp_page_template', 'compare' => 'NOT EXISTS'],
        ];
        $args['meta_query'] = $mq;
        return $args;
    }
    add_filter('wp_sitemaps_posts_query_args', 'cb_home2_sitemap_exclude', 10, 2);
}

/* Don't pollute analytics or emit brokerage JSON-LD on the noindex preview. */
if (!function_exists('cb_home2_suppress_extras')) {
    function cb_home2_suppress_extras() {
        if (!is_page_template(CB_HOME2_TEMPLATE)) { return; }
        remove_action('wp_head', 'cb_analytics_head', 1);
        remove_action('wp_head', 'cb_brokerage_schema', 5);
    }
    add_action('template_redirect', 'cb_home2_suppress_extras');
}

<?php
/**
 * Plugin Name: CB — Home 5 Fusion + Virtual Office Preview (loader)
 * Description: Self-contained loader for the un-linked /home-5/ "virtual office"
 *   concept — the Home 4 fusion engine (Three.js dust-nebula + glass cards +
 *   custom cursor) extended with a scroll-driven walkthrough of a stylized 3D
 *   Coldwell office. Acts ONLY on pages using the "Home 5 — Fusion + Virtual
 *   Office Preview" template; the theme's files and the Home 2/3/4 previews are
 *   NOT modified. To remove: delete this file and the "Home 5" page.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (!defined('CB_HOME5_TEMPLATE')) {
    define('CB_HOME5_TEMPLATE', 'templates/template-home5-office.php');
}

if (!function_exists('cb_home5_enqueue')) {
    function cb_home5_enqueue() {
        if (!is_page_template(CB_HOME5_TEMPLATE)) { return; }
        $uri = get_template_directory_uri();
        // Suffix bumped per cb-office asset change so browsers/CDN fetch fresh.
        $ver = (defined('CB_THEME_VERSION') ? CB_THEME_VERSION : '1.1.0') . '-o2';

        wp_enqueue_style('cb-office', $uri . '/assets/css/cb-office.css', ['cb-legacy-style'], $ver);

        // Three.js (r160, already vendored) + standalone cursor + the office engine.
        wp_enqueue_script('three',     $uri . '/assets/js/vendor/three.min.js', [], '0.160.0', true);
        wp_enqueue_script('cb-cursor', $uri . '/assets/js/cb-webgl/cursor.js',  [], $ver, true);
        wp_enqueue_script('cb-office', $uri . '/assets/js/cb-office/office.js',  ['three', 'cb-cursor'], $ver, true);

        $mono  = esc_js($uri . '/assets/images/logos/monogram.svg');
        $monoS = esc_js($uri . '/assets/images/logos/monogram-vertical-stacked.svg');
        wp_add_inline_script(
            'cb-office',
            "(function(){try{var ok=window.matchMedia('(min-width: 1025px)').matches"
            . "&&window.matchMedia('(prefers-reduced-motion: no-preference)').matches"
            . "&&!window.matchMedia('(pointer: coarse)').matches;"
            . "if(ok&&window.CBOffice){window.CBOffice.init({canvas:'#cb-office-canvas',"
            . "monogram:'{$mono}',monogramStacked:'{$monoS}'});}"
            . "}catch(e){document.documentElement.classList.remove('cb-office-on');"
            . "if(window.console){console.warn('[cb-office] init failed; using fallback.',e);}}})();",
            'after'
        );
    }
    add_action('wp_enqueue_scripts', 'cb_home5_enqueue');
}

if (!function_exists('cb_home5_body_class')) {
    function cb_home5_body_class($classes) {
        if (is_page_template(CB_HOME5_TEMPLATE)) {
            $classes[] = 'cb-page--home';
            $classes[] = 'cb-page--home5-preview';
        }
        return $classes;
    }
    add_filter('body_class', 'cb_home5_body_class');
}

if (!function_exists('cb_home5_sitemap_exclude')) {
    function cb_home5_sitemap_exclude($args, $post_type) {
        if ($post_type !== 'page') { return $args; }
        $mq = isset($args['meta_query']) ? $args['meta_query'] : [];
        $mq[] = [
            'relation' => 'OR',
            ['key' => '_wp_page_template', 'value' => CB_HOME5_TEMPLATE, 'compare' => '!='],
            ['key' => '_wp_page_template', 'compare' => 'NOT EXISTS'],
        ];
        $args['meta_query'] = $mq;
        return $args;
    }
    add_filter('wp_sitemaps_posts_query_args', 'cb_home5_sitemap_exclude', 10, 2);
}

if (!function_exists('cb_home5_suppress_extras')) {
    function cb_home5_suppress_extras() {
        if (!is_page_template(CB_HOME5_TEMPLATE)) { return; }
        remove_action('wp_head', 'cb_analytics_head', 1);
        remove_action('wp_head', 'cb_brokerage_schema', 5);
    }
    add_action('template_redirect', 'cb_home5_suppress_extras');
}

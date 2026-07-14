<?php
/**
 * Plugin Name: CB — Home 7 Corridor Walkthrough Preview (loader)
 * Description: Self-contained loader for the un-linked /home-7/ "corridor
 *   walkthrough" concept — merges Home 2's content boxes with Home 5's
 *   3D hallway camera. Floating glass boxes along the corridor open into
 *   vertical-scroll sections. Acts ONLY on "Home 7 — Corridor Walkthrough
 *   Preview" template. theme files and all other previews left UNCHANGED.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (!defined('CB_HOME7_TEMPLATE')) {
    define('CB_HOME7_TEMPLATE', 'templates/template-home7-corridor.php');
}

if (!function_exists('cb_home7_enqueue')) {
    function cb_home7_enqueue() {
        if (!is_page_template(CB_HOME7_TEMPLATE)) { return; }
        $uri = get_template_directory_uri();
        $ver = (defined('CB_THEME_VERSION') ? CB_THEME_VERSION : '1.1.0') . '-c1';

        wp_enqueue_style('cb-corridor', $uri . '/assets/css/cb-corridor.css', ['cb-legacy-style'], $ver);

        // Three.js (r160) + standalone cursor + corridor engine.
        wp_enqueue_script('three',        $uri . '/assets/js/vendor/three.min.js',   [], '0.160.0', true);
        wp_enqueue_script('cb-cursor',    $uri . '/assets/js/cb-webgl/cursor.js',   [], $ver, true);
        wp_enqueue_script('cb-corridor', $uri . '/assets/js/cb-corridor/corridor.js', ['three', 'cb-cursor'], $ver, true);

        $mono  = esc_js($uri . '/assets/images/logos/monogram.svg');
        $monoS = esc_js($uri . '/assets/images/logos/monogram-vertical-stacked.svg');
        wp_add_inline_script(
            'cb-corridor',
            "(function(){try{var ok=window.matchMedia('(min-width: 1025px)').matches"
            . "&&window.matchMedia('(prefers-reduced-motion: no-preference)').matches"
            . "&&!window.matchMedia('(pointer: coarse)').matches;"
            . "if(ok&&window.CBCorridor){window.CBCorridor.init({canvas:'#cb-corridor-canvas',"
            . "monogram:'{$mono}',monogramStacked:'{$monoS}'});}"
            . "}catch(e){document.documentElement.classList.remove('cb-corridor-on');"
            . "if(window.console){console.warn('[cb-corridor] init failed; using fallback.',e);}}})();",
            'after'
        );
    }
    add_action('wp_enqueue_scripts', 'cb_home7_enqueue');
}

if (!function_exists('cb_home7_body_class')) {
    function cb_home7_body_class($classes) {
        if (is_page_template(CB_HOME7_TEMPLATE)) {
            $classes[] = 'cb-page--home';
            $classes[] = 'cb-page--home7-preview';
        }
        return $classes;
    }
    add_filter('body_class', 'cb_home7_body_class');
}

if (!function_exists('cb_home7_sitemap_exclude')) {
    function cb_home7_sitemap_exclude($args, $post_type) {
        if ($post_type !== 'page') { return $args; }
        $mq = isset($args['meta_query']) ? $args['meta_query'] : [];
        $mq[] = [
            'relation' => 'OR',
            ['key' => '_wp_page_template', 'value' => CB_HOME7_TEMPLATE, 'compare' => '!='],
            ['key' => '_wp_page_template', 'compare' => 'NOT EXISTS'],
        ];
        $args['meta_query'] = $mq;
        return $args;
    }
    add_filter('wp_sitemaps_posts_query_args', 'cb_home7_sitemap_exclude', 10, 2);
}

if (!function_exists('cb_home7_suppress_extras')) {
    function cb_home7_suppress_extras() {
        if (!is_page_template(CB_HOME7_TEMPLATE)) { return; }
        remove_action('wp_head', 'cb_analytics_head', 1);
        remove_action('wp_head', 'cb_brokerage_schema', 5);
    }
    add_action('template_redirect', 'cb_home7_suppress_extras');
}

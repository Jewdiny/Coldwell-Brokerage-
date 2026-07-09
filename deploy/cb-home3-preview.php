<?php
/**
 * Plugin Name: CB — Home 3 Dust / Floating Cards Preview (loader)
 * Description: Self-contained loader for the un-linked /home-3/ "dust / floating
 *   cards" homepage concept. Lives in wp-content/mu-plugins/ and acts ONLY on
 *   pages using the "Home 3 — Dust / Floating Cards Preview" template — the
 *   active theme's existing files (functions.php, header.php, front-page.php) and
 *   the Home 2 preview are NOT modified, so no current page changes. To fully
 *   remove: delete this file and the "Home 3" page. When the full theme is later
 *   deployed, DELETE this loader (functions.php would then own the logic).
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (!defined('CB_HOME3_TEMPLATE')) {
    define('CB_HOME3_TEMPLATE', 'templates/template-home3-dust.php');
}

/* Enqueue the dust stack ON THE PREVIEW TEMPLATE ONLY. All handles/files are
   additive and unused elsewhere on the live site; dust.js guards re-init. */
if (!function_exists('cb_home3_enqueue')) {
    function cb_home3_enqueue() {
        if (!is_page_template(CB_HOME3_TEMPLATE)) { return; }
        $uri = get_template_directory_uri();
        // Suffix bumped on each cb-dust asset change so browsers/CDN fetch fresh
        // (the base theme version stays 1.1.0 across the rest of the site).
        $ver = (defined('CB_THEME_VERSION') ? CB_THEME_VERSION : '1.1.0') . '-p2';

        // Glass-card styling (new file; depends on the theme's main stylesheet
        // so the --cb-* / --font-* tokens are defined first).
        wp_enqueue_style('cb-dust', $uri . '/assets/css/cb-dust.css', ['cb-legacy-style'], $ver);

        // Canvas-2D dust engine (no Three.js needed).
        wp_enqueue_script('cb-dust', $uri . '/assets/js/cb-dust/dust.js', [], $ver, true);

        // Gate init() to >=1025px desktop + motion + fine pointer (same eligibility
        // rule as Home 2); try/catch restores the static fallback on any failure.
        wp_add_inline_script(
            'cb-dust',
            "(function(){try{var ok=window.matchMedia('(min-width: 1025px)').matches"
            . "&&window.matchMedia('(prefers-reduced-motion: no-preference)').matches"
            . "&&!window.matchMedia('(pointer: coarse)').matches;"
            . "if(ok&&window.CBDust){window.CBDust.init({canvas:'#cb-dust-canvas'});}"
            . "}catch(e){document.documentElement.classList.remove('cb-dust-on');"
            . "if(window.console){console.warn('[cb-dust] init failed; using fallback.',e);}}})();",
            'after'
        );
    }
    add_action('wp_enqueue_scripts', 'cb_home3_enqueue');
}

/* Body classes for the overlay header + preview-scoped styling. */
if (!function_exists('cb_home3_body_class')) {
    function cb_home3_body_class($classes) {
        if (is_page_template(CB_HOME3_TEMPLATE)) {
            $classes[] = 'cb-page--home';
            $classes[] = 'cb-page--home3-preview';
        }
        return $classes;
    }
    add_filter('body_class', 'cb_home3_body_class');
}

/* Keep the preview out of WordPress core's /wp-sitemap.xml. */
if (!function_exists('cb_home3_sitemap_exclude')) {
    function cb_home3_sitemap_exclude($args, $post_type) {
        if ($post_type !== 'page') { return $args; }
        $mq = isset($args['meta_query']) ? $args['meta_query'] : [];
        $mq[] = [
            'relation' => 'OR',
            ['key' => '_wp_page_template', 'value' => CB_HOME3_TEMPLATE, 'compare' => '!='],
            ['key' => '_wp_page_template', 'compare' => 'NOT EXISTS'],
        ];
        $args['meta_query'] = $mq;
        return $args;
    }
    add_filter('wp_sitemaps_posts_query_args', 'cb_home3_sitemap_exclude', 10, 2);
}

/* Don't pollute analytics or emit brokerage JSON-LD on the noindex preview. */
if (!function_exists('cb_home3_suppress_extras')) {
    function cb_home3_suppress_extras() {
        if (!is_page_template(CB_HOME3_TEMPLATE)) { return; }
        remove_action('wp_head', 'cb_analytics_head', 1);
        remove_action('wp_head', 'cb_brokerage_schema', 5);
    }
    add_action('template_redirect', 'cb_home3_suppress_extras');
}

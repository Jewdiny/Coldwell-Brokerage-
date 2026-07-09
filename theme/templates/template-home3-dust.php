<?php
/**
 * Template Name: Home 3 — Dust / Floating Cards Preview
 *
 * A SECOND safe, un-linked staging copy of the homepage exploring a different
 * aesthetic from the cinematic WebGL build (Home 2). Here the whole page lives on
 * a deep-blue animated canvas; every block of information is a floating glass
 * "card." As a section scrolls away its cards break apart into a cloud of dust
 * (canvas particles in the card's own silhouette + a CSS erosion), and that dust
 * re-coalesces into the next section's floating cards.
 *
 * How to use (same pattern as Home 2):
 *   1. Upload the theme files + the mu-plugin loader (deploy/cb-home3-preview.php).
 *   2. WP Admin -> Pages -> Add New -> title "Home 3".
 *   3. Page Attributes -> Template -> "Home 3 — Dust / Floating Cards Preview".
 *   4. Visit /home-3/. It is noindex and in no menu.
 *
 * Reuses the EXACT same server-rendered content (live MLS listings, communities,
 * testimonials, blog, stats) via template-parts/home-dust-scenes.php. The live
 * front page and Home 2 are both left completely untouched.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

// Preview only — never let search engines index the staging page.
if (function_exists('cb_set_seo_meta')) {
    cb_set_seo_meta([
        'title'       => 'Dust Homepage Preview (Home 3) — Coldwell Banker Legacy',
        'description' => 'Internal preview of the floating-cards / dust homepage concept for Coldwell Banker Legacy, San Angelo.',
        'canonical'   => get_permalink(),
        'robots'      => 'noindex, nofollow, noarchive, max-image-preview:none',
    ]);
}

// Flag read by template-parts/home-dust-scenes.php and the loader.
$GLOBALS['cb_home_dust'] = true;

get_header();

get_template_part('template-parts/home-dust-scenes');

get_footer();

<?php
/**
 * Template Name: Home 4 — Fusion Preview
 *
 * A THIRD un-linked staging concept that fuses the best of the two earlier
 * previews: Home 2's cinematic Three.js WebGL backdrop + custom cursor, and
 * Home 3's floating glass info-cards that crumble into / form from dust — here
 * unified so the cards break apart into (and re-gather from) the SAME 3D
 * particle nebula.
 *
 * How to use (same pattern as Home 2 / Home 3):
 *   1. Upload the theme files + mu-plugin loader (deploy/cb-home4-preview.php).
 *   2. WP Admin -> Pages -> Add New -> title "Home 4".
 *   3. Page Attributes -> Template -> "Home 4 — Fusion Preview".
 *   4. Visit /home-4/. It is noindex and in no menu.
 *
 * Reuses the same live content (MLS, communities, Testimonial Tree, blog, stats)
 * via template-parts/home-fusion-scenes.php. front-page.php, Home 2 and Home 3
 * are all left completely untouched.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (function_exists('cb_set_seo_meta')) {
    cb_set_seo_meta([
        'title'       => 'Fusion Homepage Preview (Home 4) — Coldwell Banker Legacy',
        'description' => 'Internal preview fusing the cinematic WebGL and dust-card homepage concepts for Coldwell Banker Legacy, San Angelo.',
        'canonical'   => get_permalink(),
        'robots'      => 'noindex, nofollow, noarchive, max-image-preview:none',
    ]);
}

$GLOBALS['cb_home_fusion'] = true;

get_header();

get_template_part('template-parts/home-fusion-scenes');

get_footer();

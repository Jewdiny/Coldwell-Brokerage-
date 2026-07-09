<?php
/**
 * Template Name: Home 2 — Cinematic WebGL Preview
 *
 * A SAFE, UN-LINKED staging copy of the cinematic homepage so the new
 * WebGL/scroll experience can be reviewed live on the real domain without
 * touching the published front page or appearing in any menu.
 *
 * How to use (see WP_INTEGRATION.md "Preview page" section):
 *   1. Upload the theme files.
 *   2. WP Admin → Pages → Add New → title "Home 2".
 *   3. Page Attributes → Template → "Home 2 — Cinematic WebGL Preview" → Publish.
 *   4. Visit /home-2/ (URL = the page slug). It is noindex and in no menu.
 *
 * Renders the identical server-side homepage content (live MLS listings,
 * communities, testimonials, blog, stats) via template-parts/home-scenes.php,
 * plus the WebGL <canvas>. Mode arbitration (WebGL vs. CSS-cinematic vs. stacked
 * fallback) is handled by cb_home2_capability_script() in functions.php so the
 * two scroll controllers can never run at once.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

// Preview only — never let search engines index the staging page.
cb_set_seo_meta([
    'title'       => 'Cinematic Homepage Preview (Home 2) — Coldwell Banker Legacy',
    'description' => 'Internal preview of the cinematic WebGL homepage for Coldwell Banker Legacy, San Angelo.',
    'canonical'   => get_permalink(),
    'robots'      => 'noindex, nofollow, noarchive, max-image-preview:none',
]);

// Flag read by template-parts/home-scenes.php to emit the WebGL canvas, and by
// cb_enqueue_home2_webgl() / cb_home2_capability_script() in functions.php.
$GLOBALS['cb_home_webgl'] = true;

get_header();

get_template_part('template-parts/home-scenes');

get_footer();

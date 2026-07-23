<?php
/**
 * Template Name: Home 9 — The House (retired → renders Home 10)
 *
 * RETIRED. Home 10 replaced Home 9; this file is an alias, not a template.
 *
 * WHY IT STILL EXISTS
 * -------------------
 * WordPress stores the chosen template on the page as _wp_page_template =
 * 'templates/template-home9-house.php'. Delete this file and every page already
 * assigned it silently falls back to page.php — the walkthrough replaced by the
 * default page layout, at the same URL, with no error anywhere. Keeping the path
 * alive and pointing it at Home 10 is what makes "replace Home 9 with Home 10" a
 * swap rather than a breakage: nothing has to be re-assigned in wp-admin and no
 * link goes stale.
 *
 * WHAT WAS ACTUALLY REMOVED
 * -------------------------
 *   assets/js/cb-home9/home9.js     the real-time WebGL house (~3,200 lines)
 *   assets/js/vendor/GLTFLoader.js  vendored solely to load its armchair mesh
 *   harness/patch-gltf.js           that loader's regenerator
 *   deploy/cb-home9-preview.php     its loader
 *
 * WHAT DELIBERATELY SURVIVES, because Home 10 is built ON Home 9:
 *   assets/css/cb-home9.css                styles everything INSIDE a panel
 *   template-parts/home9-house-scenes.php  the content Home 10 lifts
 *
 * So "Home 9" is gone as an EXPERIENCE while remaining the shared foundation.
 * Deleting either of those two files takes Home 10 down with it.
 *
 * Assets are enqueued by deploy/cb-home10-preview.php, which treats this
 * template and the Home 10 one as the same target.
 *
 * To retire this alias for good: re-assign the affected pages to "Home 10 — The
 * House, filmed" in wp-admin, confirm nothing else references the old path, then
 * delete this file.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (function_exists('cb_set_seo_meta')) {
    cb_set_seo_meta([
        'title'       => 'The House, filmed (Home 10) — Coldwell Banker Legacy',
        'description' => 'Internal preview: Home 9\'s walkthrough as photoreal stills joined by filmed camera moves.',
        'canonical'   => get_permalink(),
        'robots'      => 'noindex, nofollow, noarchive, max-image-preview:none',
    ]);
}

$GLOBALS['cb_home10'] = true;

get_header();

get_template_part('template-parts/home10-filmed-scenes');

get_footer();

<?php
/**
 * Template Name: Home 8 — Floating Pages
 *
 * The experience Home 7's template docblock described but never implemented:
 *   1. You stand at the threshold of a Coldwell hallway (dust nebula).
 *   2. Each section is a PAGE floating ahead of you in the corridor.
 *   3. As you scroll, the camera dollies down the hallway and the next page
 *      zooms in from the distance, settling at reading distance, crisp at 1:1.
 *   4. Tall content (live MLS, testimonials) scrolls INSIDE the page. The
 *      document does not move while it does, so the camera holds -- the dwell
 *      lasts exactly as long as the reading takes.
 *   5. At the end of the inner scroll the gesture chains natively back to the
 *      document, the camera resumes, and the page flies past you as the next
 *      one arrives.
 *
 * Home 7 (corridor) is left UNCHANGED as the comparison point, as are all other
 * previews and front-page.php.
 *
 * Un-linked, noindex preview.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (function_exists('cb_set_seo_meta')) {
    cb_set_seo_meta([
        'title'       => 'Floating Pages (Home 8) — Coldwell Banker Legacy',
        'description' => 'Internal preview: Home 2 content on Home 5 hallway camera, with each section floating as its own page.',
        'canonical'   => get_permalink(),
        'robots'      => 'noindex, nofollow, noarchive, max-image-preview:none',
    ]);
}

$GLOBALS['cb_home8'] = true;

get_header();

get_template_part('template-parts/home8-pages-scenes');

get_footer();

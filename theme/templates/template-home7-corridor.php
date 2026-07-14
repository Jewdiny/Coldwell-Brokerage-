<?php
/**
 * Template Name: Home 7 — Corridor Walkthrough Preview
 *
 * A scroll-driven walkthrough that merges the TEXT/INFO-BOXES from Home 2 with the
 * 3D hallway camera from Home 5. The experience:
 *   1. You stand at the threshold of a Coldwell hallway (dust nebula).
 *   2. Floating content boxes (Arrival, Welcome, Listings, Legacy, Buy,
 *      Communities, Sell, Connect) hover ahead.
 *   3. As you scroll, the camera PANS through the hallway toward the next box.
 *   4. The box transitions into a VERTICAL SCROLL of that section's full content
 *      (MLS listings, stats, testimonials, map, blog).
 *   5. At the end of vertical scroll, it transitions BACK to the hallway, camera
 *      moves to the next box, and repeats.
 *
 * Un-linked, noindex preview. All other home pages left UNCHANGED.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (function_exists('cb_set_seo_meta')) {
    cb_set_seo_meta([
        'title'       => 'Corridor Walkthrough (Home 7) — Coldwell Banker Legacy',
        'description' => 'Internal preview: a scroll-driven hallway walkthrough merging cinema and info boxes.',
        'canonical'   => get_permalink(),
        'robots'      => 'noindex, nofollow, noarchive, max-image-preview:none',
    ]);
}

$GLOBALS['cb_home_corridor'] = true;

get_header();

get_template_part('template-parts/home-corridor-scenes');

get_footer();

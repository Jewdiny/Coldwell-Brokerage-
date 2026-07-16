<?php
/**
 * Template Name: Home 9 — The House
 *
 * Home 8, walked through an actual home instead of a blueprint.
 *
 * The experience is identical: you stand at the threshold, walk the hallway, turn
 * to face a room, walk in as its page zooms up to reading size, dwell (tall live
 * content scrolls inside the page while the camera holds), back out, turn back,
 * carry on. Same 8 rooms, same camera, same pages.
 *
 * What changes is the world. Home 8's hallway is a wireframe schematic — additive
 * lines and dust condensing onto them. Home 9's is a house: oak floors, plaster,
 * wainscot, crown moulding, rugs, furniture, and lamps that actually light it.
 * Home 2's eight scene plates hang framed on the walls rather than sitting behind
 * the pages, which is what photographs of places are for.
 *
 * Home 8 (templates/template-home8-pages.php) is left UNCHANGED and still ships,
 * as are Home 7 and every other preview and front-page.php.
 *
 * Un-linked, noindex preview.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (function_exists('cb_set_seo_meta')) {
    cb_set_seo_meta([
        'title'       => 'The House (Home 9) — Coldwell Banker Legacy',
        'description' => 'Internal preview: Home 8\'s walkthrough, rendered as a warm, furnished home rather than a blueprint.',
        'canonical'   => get_permalink(),
        'robots'      => 'noindex, nofollow, noarchive, max-image-preview:none',
    ]);
}

$GLOBALS['cb_home9'] = true;

get_header();

get_template_part('template-parts/home9-house-scenes');

get_footer();

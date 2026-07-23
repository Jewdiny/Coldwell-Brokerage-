<?php
/**
 * Template Name: Home 10 — The House, filmed
 *
 * Home 9, filmed instead of rendered.
 *
 * Home 9 builds its house in real time with three.js. Home 10 has no geometry at
 * all: every room is a photoreal still and every move between rooms is a short
 * clip of the camera walking there. Scroll into a section and the walk PLAYS —
 * scroll does not scrub it — then the clip parks on its last frame, which is the
 * room you now stand in, and the reading panel fades up over it.
 *
 * The content is Home 9's, lifted rather than retyped. See
 * template-parts/home10-filmed-scenes.php for how and why.
 *
 * Home 9 (templates/template-home9-house.php) and Home 8 are left UNCHANGED and
 * still ship, as does front-page.php.
 *
 * Un-linked, noindex preview.
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

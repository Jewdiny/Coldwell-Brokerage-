<?php
/**
 * Template Name: Home 5 — Fusion + Virtual Office Preview
 *
 * Extends the Home 4 "Fusion" concept (Three.js WebGL dust-nebula + floating
 * glass info-cards) with a scroll-driven VIRTUAL COLDWELL OFFICE walkthrough:
 * the camera travels through a stylized 3D office that materializes from the
 * dust, and floating info-boxes reveal each station's information as it takes
 * you around. Un-linked, noindex preview. Home 2/3/4 and front-page.php are all
 * left UNCHANGED.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

if (function_exists('cb_set_seo_meta')) {
    cb_set_seo_meta([
        'title'       => 'Virtual Office Homepage Preview (Home 5) — Coldwell Banker Legacy',
        'description' => 'Internal preview: a scroll-driven virtual Coldwell Banker office walkthrough with floating information boxes.',
        'canonical'   => get_permalink(),
        'robots'      => 'noindex, nofollow, noarchive, max-image-preview:none',
    ]);
}

$GLOBALS['cb_home_office'] = true;

get_header();

get_template_part('template-parts/home-office-scenes');

get_footer();

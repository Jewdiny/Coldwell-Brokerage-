<?php
/**
 * /events/ — "Plant a Legacy" landing (Coldwell Banker Legacy × Grace Gardens).
 *
 * WHY THIS FILE OWNS /events/
 * ---------------------------
 * A `cb_event` post type is registered with has_archive => true and the `events`
 * rewrite slug, so it shadows the WP Page with slug `events` and this archive is
 * what actually serves /events/. Rather than fight that, the events URL renders
 * the current campaign landing (the module schedule lives in cb_event_modules()
 * in inc/form-handler.php, shared with the AJAX handler and the emails).
 *
 * TWO VIEWS
 * ---------
 *   /events/            full page with the site header/footer.
 *   /events/?embed=1    bare, framing-permissive view for a partner <iframe>.
 *                       The main site stays X-Frame-Options: SAMEORIGIN; only
 *                       this embed view opts into cross-origin framing.
 *
 * @package CB_Legacy_Luxury
 */

$cb_is_embed = isset($_GET['embed']) && $_GET['embed'] !== '0';

if ($cb_is_embed) {
    // Opt THIS view into cross-origin framing. LiteSpeed sets a global
    // X-Frame-Options: SAMEORIGIN; a matching .htaccess rule unsets it for the
    // ?embed=1 query, and these PHP headers reinforce it for any layer that
    // honours PHP-set headers. Kept off the search index — it's an embed, not a
    // second copy of the page.
    if (!headers_sent()) {
        header_remove('X-Frame-Options');
        header('Content-Security-Policy: frame-ancestors *');
        header('X-Robots-Tag: noindex, nofollow', true);
    }
    add_action('wp_head', function () {
        echo '<meta name="robots" content="noindex,nofollow">' . "\n";
    });

    get_header('embed');
    get_template_part('template-parts/events-plant-a-legacy');
    get_footer('embed');
    return;
}

if (function_exists('cb_set_seo_meta')) {
    cb_set_seo_meta([
        'title'       => 'Planting a Legacy — Garden Workshops in San Angelo | Sponsored by Coldwell Banker Legacy',
        'description' => 'Planting a Legacy: four hands-on gardening workshops (October–November) supporting Grace Gardens in San Angelo, sponsored by Coldwell Banker Legacy. Lasagna gardening, composting, upcycling & recycling, and green cleaners. Register and support the mission.',
        'canonical'   => home_url('/events/plantingalegacy/'),
    ]);
}

get_header();
get_template_part('template-parts/events-plant-a-legacy');
get_footer();

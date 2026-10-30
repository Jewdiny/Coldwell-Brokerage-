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

$cb_embed_mode = isset($_GET['embed']) ? $_GET['embed'] : '';
$cb_is_embed   = $cb_embed_mode !== '' && $cb_embed_mode !== '0';

if ($cb_is_embed) {
    // Opt THIS view into cross-origin framing. LiteSpeed sets a global
    // X-Frame-Options: SAMEORIGIN; these PHP headers add frame-ancestors *, which
    // modern browsers honour over XFO. Kept off the search index — it's an embed.
    if (!headers_sent()) {
        header_remove('X-Frame-Options');
        header('Content-Security-Policy: frame-ancestors *');
        header('X-Robots-Tag: noindex, nofollow', true);
    }

    if ($cb_embed_mode === 'flyer') {
        // Standalone promotional flyer for a partner's website builder — a full
        // HTML document (no site chrome, no form), so render it and stop.
        get_template_part('template-parts/events-flyer');
        return;
    }

    add_action('wp_head', function () {
        echo '<meta name="robots" content="noindex,nofollow">' . "\n";
    });

    get_header('embed');
    get_template_part('template-parts/events-plant-a-legacy');
    get_footer('embed');
    return;
}

// 1200x630 crop of the hero, sized for Facebook/iMessage link previews.
// cb_set_seo_meta() hands it to Yoast (see cb_add_og_image()).
$cb_event_share = get_template_directory_uri() . '/assets/images/events/share.jpg';

if (function_exists('cb_set_seo_meta')) {
    cb_set_seo_meta([
        'title'       => 'Planting a Legacy: Garden Workshops in San Angelo, TX',
        'description' => 'Hands-on San Angelo garden workshops supporting Grace Gardens: lasagna gardening, composting, upcycling and green cleaners, from Coldwell Banker Legacy.',
        'canonical'   => home_url('/events/plantingalegacy/'),
        'og_image'    => $cb_event_share,
    ]);
}

// schema.org Event markup, so search engines can list each workshop as an event.
add_action('wp_head', 'cb_event_schema_head');

get_header();
get_template_part('template-parts/events-plant-a-legacy');
get_footer();

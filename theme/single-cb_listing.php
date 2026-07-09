<?php
/**
 * Single Listing Detail Template
 *
 * Rendered for /listing/<slug>-<id>/ URLs (matched via the rewrite rule in
 * functions.php). Pulls the full listing record from the Spark API, emits
 * SEO-rich head tags + JSON-LD, and renders a luxury detail page.
 *
 * @package CB_Legacy_Luxury
 */

$cb_listing_id = preg_replace('/[^a-zA-Z0-9]/', '', get_query_var('cb_listing_id'));

if (!$cb_listing_id || !class_exists('CB_Spark_Client')) {
    status_header(404);
    nocache_headers();
    get_header();
    echo '<div style="padding:8rem 0;text-align:center;"><h1>Listing Not Found</h1><p>The listing you requested could not be found.</p><p><a href="' . esc_url(home_url('/find-a-home/')) . '" class="cb-btn cb-btn--primary">Browse Listings</a></p></div>';
    get_footer();
    return;
}

$cb_client  = new CB_Spark_Client();
$cb_listing = $cb_client->get_listing_detail($cb_listing_id);

if (is_wp_error($cb_listing) || empty($cb_listing)) {
    status_header(404);
    nocache_headers();
    get_header();
    echo '<div style="padding:8rem 0;text-align:center;"><h1>Listing Not Found</h1><p>This property may have been sold or is no longer in the MLS.</p><p><a href="' . esc_url(home_url('/find-a-home/')) . '" class="cb-btn cb-btn--primary">Browse Listings</a></p></div>';
    get_footer();
    return;
}

// 301 to the canonical slugged URL if the user arrived via a stale or bare-id URL.
$cb_canonical    = CB_Spark_Client::detail_url($cb_listing);
$cb_current_path = '/' . ltrim($_SERVER['REQUEST_URI'] ?? '', '/');
$cb_current_path = strtok($cb_current_path, '?');
if (rtrim(parse_url($cb_canonical, PHP_URL_PATH), '/') !== rtrim($cb_current_path, '/')) {
    wp_safe_redirect($cb_canonical, 301);
    exit;
}

// Pull display values.
$cb_address       = $cb_listing['UnparsedAddress'] ?? '';
$cb_city          = $cb_listing['City'] ?? '';
$cb_state         = $cb_listing['StateOrProvince'] ?? '';
$cb_zip           = $cb_listing['PostalCode'] ?? '';
$cb_price         = $cb_listing['ListPrice'] ?? 0;
$cb_price_display = CB_Spark_Client::format_price($cb_price);
$cb_beds          = $cb_listing['BedsTotal'] ?? '';
$cb_baths         = $cb_listing['BathsTotal'] ?? '';
$cb_sqft          = $cb_listing['BuildingAreaTotal'] ?? '';
$cb_lot           = $cb_listing['LotSizeAcres'] ?? '';
$cb_year          = $cb_listing['YearBuilt'] ?? '';
$cb_subtype       = $cb_listing['PropertySubType'] ?? '';
$cb_status        = $cb_listing['StandardStatus'] ?? ($cb_listing['MlsStatus'] ?? 'Active');
$cb_remarks       = $cb_listing['PublicRemarks'] ?? '';
$cb_agent_name    = $cb_listing['ListAgentName'] ?? '';
$cb_office_name   = $cb_listing['ListOfficeName'] ?? '';
$cb_modified      = $cb_listing['ListingUpdateTimestamp'] ?? '';
$cb_subdivision   = $cb_listing['SubdivisionName'] ?? '';
$cb_county        = $cb_listing['CountyOrParish'] ?? '';
$cb_school_elem   = $cb_listing['ElementarySchool'] ?? '';
$cb_school_mid    = $cb_listing['MiddleOrJuniorSchool'] ?? '';
$cb_school_high   = $cb_listing['HighSchool'] ?? '';
$cb_garage        = $cb_listing['GarageSpaces'] ?? '';
$cb_hero_photo    = CB_Spark_Client::photo_url($cb_listing, 'Uri1280');
$cb_gallery       = CB_Spark_Client::photo_urls($cb_listing, 'Uri1280');
$cb_locale        = trim(implode(', ', array_filter([$cb_city, trim("$cb_state $cb_zip")])), ', ');
$cb_is_active     = strcasecmp($cb_status, 'Active') === 0;
$cb_has_schools   = $cb_school_elem || $cb_school_mid || $cb_school_high;

// Drop API redaction asterisks before they hit headings.
$cb_clean = function ($v) {
    return ($v === '' || $v === null || $v === '********') ? '' : $v;
};
$cb_subdivision = $cb_clean($cb_subdivision);
$cb_school_elem = $cb_clean($cb_school_elem);
$cb_school_mid  = $cb_clean($cb_school_mid);
$cb_school_high = $cb_clean($cb_school_high);

// Build the keyword-rich H2 strings used across the page.
$cb_h2_about    = 'About this'
    . ($cb_beds && $cb_beds !== '********' ? ' ' . $cb_beds . '-Bedroom' : '')
    . ' ' . ($cb_subtype ?: 'Home')
    . ($cb_subdivision ? ' in ' . $cb_subdivision : '')
    . ($cb_city ? ($cb_subdivision ? ', ' : ' in ') . $cb_city : '')
    . ($cb_state ? ', ' . $cb_state : '');
$cb_h2_specs    = trim(
    ($cb_beds && $cb_beds !== '********' ? "{$cb_beds}-Bedroom" : '')
    . ($cb_baths && $cb_baths !== '********' ? ($cb_beds ? ', ' : '') . "{$cb_baths}-Bathroom" : '')
    . ' Property at ' . $cb_address
);
$cb_h2_gallery  = 'Photos of ' . $cb_address;
$cb_h2_schools  = 'Schools Near ' . $cb_address;
$cb_h2_schedule = 'Schedule a Showing in ' . trim($cb_city . ($cb_zip ? ", $cb_state $cb_zip" : ''), ', ');
$cb_h2_related  = 'More ' . ($cb_city ?: 'San Angelo') . ' Homes for Sale';

$cb_meta_desc = $cb_remarks
    ? mb_substr(trim(preg_replace('/\s+/', ' ', $cb_remarks)), 0, 155)
    : trim("$cb_address. $cb_beds bed, $cb_baths bath home in $cb_city, $cb_state.");
if (mb_strlen($cb_meta_desc) === 155) { $cb_meta_desc .= '…'; }

$cb_page_title = trim($cb_address . ' | Coldwell Banker Legacy San Angelo');

// Yoast SEO compatibility — if Yoast is active it owns title/description/OG.
// We feed it our values via its filters; otherwise we emit them directly below.
$cb_has_yoast = defined('WPSEO_VERSION');

if ($cb_has_yoast) {
    add_filter('wpseo_title',           function () use ($cb_page_title) { return $cb_page_title; }, 99);
    add_filter('wpseo_metadesc',        function () use ($cb_meta_desc) { return $cb_meta_desc; }, 99);
    add_filter('wpseo_canonical',       function () use ($cb_canonical) { return $cb_canonical; }, 99);
    add_filter('wpseo_opengraph_title', function () use ($cb_page_title) { return $cb_page_title; }, 99);
    add_filter('wpseo_opengraph_desc',  function () use ($cb_meta_desc) { return $cb_meta_desc; }, 99);
    add_filter('wpseo_opengraph_url',   function () use ($cb_canonical) { return $cb_canonical; }, 99);
    add_filter('wpseo_opengraph_image', function () use ($cb_hero_photo) { return $cb_hero_photo; }, 99);
    add_filter('wpseo_twitter_title',   function () use ($cb_page_title) { return $cb_page_title; }, 99);
    add_filter('wpseo_twitter_description', function () use ($cb_meta_desc) { return $cb_meta_desc; }, 99);
    add_filter('wpseo_twitter_image',   function () use ($cb_hero_photo) { return $cb_hero_photo; }, 99);
    add_filter('wpseo_robots',          function ($robots) use ($cb_is_active) {
        return $cb_is_active ? 'index, follow, max-image-preview:large' : 'noindex, follow';
    }, 99);
}

// Inject SEO meta + JSON-LD via wp_head closure.
add_action('wp_head', function () use (
    $cb_canonical, $cb_page_title, $cb_meta_desc, $cb_hero_photo, $cb_gallery,
    $cb_address, $cb_city, $cb_state, $cb_zip, $cb_price, $cb_beds, $cb_baths,
    $cb_sqft, $cb_modified, $cb_is_active, $cb_agent_name, $cb_office_name, $cb_has_yoast
) {
    echo "\n";
    // Only emit head tags directly if no SEO plugin is rendering them.
    if (!$cb_has_yoast) {
        echo '<link rel="canonical" href="' . esc_url($cb_canonical) . '">' . "\n";
        echo '<meta name="description" content="' . esc_attr($cb_meta_desc) . '">' . "\n";
        echo '<meta name="robots" content="' . ($cb_is_active ? 'index,follow,max-image-preview:large' : 'noindex,follow') . '">' . "\n";

        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($cb_page_title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($cb_meta_desc) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($cb_canonical) . '">' . "\n";
        if ($cb_hero_photo) {
            echo '<meta property="og:image" content="' . esc_url($cb_hero_photo) . '">' . "\n";
            echo '<meta property="og:image:alt" content="' . esc_attr($cb_address) . '">' . "\n";
        }
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($cb_page_title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($cb_meta_desc) . '">' . "\n";
        if ($cb_hero_photo) {
            echo '<meta name="twitter:image" content="' . esc_url($cb_hero_photo) . '">' . "\n";
        }
    }

    // JSON-LD structured data — RealEstateListing (always emitted; Yoast doesn't supply this for custom routes)
    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'RealEstateListing',
        'name'        => $cb_address,
        'url'         => $cb_canonical,
        'description' => $cb_meta_desc,
        'image'       => array_slice($cb_gallery, 0, 12),
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $cb_address,
            'addressLocality' => $cb_city,
            'addressRegion'   => $cb_state,
            'postalCode'      => $cb_zip,
            'addressCountry'  => 'US',
        ],
    ];
    if ($cb_beds !== '' && $cb_beds !== '********') { $schema['numberOfRooms'] = (int) $cb_beds; }
    if ($cb_baths !== '' && $cb_baths !== '********') { $schema['numberOfBathroomsTotal'] = (float) $cb_baths; }
    if ($cb_sqft) {
        $schema['floorSize'] = [
            '@type'    => 'QuantitativeValue',
            'value'    => (int) $cb_sqft,
            'unitCode' => 'FTK',
            'unitText' => 'square feet',
        ];
    }
    if ($cb_price > 0) {
        $schema['offers'] = [
            '@type'         => 'Offer',
            'price'         => (int) $cb_price,
            'priceCurrency' => 'USD',
            'availability'  => $cb_is_active ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
        ];
    }
    if ($cb_modified) { $schema['dateModified'] = $cb_modified; }
    if ($cb_agent_name || $cb_office_name) {
        $schema['broker'] = [
            '@type' => 'RealEstateAgent',
            'name'  => trim($cb_agent_name . ($cb_office_name ? ' — ' . $cb_office_name : '')),
        ];
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
});

// Override the page title for non-Yoast / fallback path.
add_filter('pre_get_document_title', function () use ($cb_page_title) {
    return $cb_page_title;
}, 99);

get_header();
?>

<div class="cb-listing-page" style="padding-top:var(--header-height);">

<?php if (!$cb_is_active) : ?>
<div class="cb-listing-offmarket">
    <div class="cb-container">
        <strong>This property is no longer listed.</strong> Status: <?php echo esc_html($cb_status); ?>. See similar active homes below.
    </div>
</div>
<?php endif; ?>

<!-- Hero -->
<section class="cb-listing-hero">
    <?php if ($cb_hero_photo) : ?>
        <div class="cb-listing-hero__media">
            <img src="<?php echo esc_url($cb_hero_photo); ?>" alt="<?php echo esc_attr($cb_address . ' — primary photo'); ?>" fetchpriority="high">
            <?php if ($cb_status) : ?>
                <span class="cb-listing-hero__badge"><?php echo esc_html($cb_status); ?></span>
            <?php endif; ?>
            <?php if ($cb_price_display) : ?>
                <span class="cb-listing-hero__price"><?php echo esc_html($cb_price_display); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="cb-container cb-listing-hero__content cb-reveal">
        <h1 class="cb-listing-hero__address"><?php echo esc_html($cb_address); ?></h1>
        <p class="cb-listing-hero__locale">
            <?php echo esc_html($cb_locale); ?><?php if ($cb_subtype) : ?> — <span><?php echo esc_html($cb_subtype); ?></span><?php endif; ?><?php if ($cb_subdivision) : ?> &middot; <span><?php echo esc_html($cb_subdivision); ?></span><?php endif; ?>
        </p>
    </div>
</section>

<!-- Specs strip -->
<section class="cb-listing-specs cb-section cb-section--offwhite" style="padding:2.5rem 0;">
    <div class="cb-container">
        <h2 class="cb-listing-specs__heading"><?php echo esc_html($cb_h2_specs); ?></h2>
        <dl class="cb-listing-specs__grid">
            <?php if ($cb_beds !== '' && $cb_beds !== '********') : ?>
                <div class="cb-listing-spec"><dt>Bedrooms</dt><dd><?php echo esc_html($cb_beds); ?></dd></div>
            <?php endif; ?>
            <?php if ($cb_baths !== '' && $cb_baths !== '********') : ?>
                <div class="cb-listing-spec"><dt>Bathrooms</dt><dd><?php echo esc_html($cb_baths); ?></dd></div>
            <?php endif; ?>
            <?php if ($cb_sqft) : ?>
                <div class="cb-listing-spec"><dt>Square Feet</dt><dd><?php echo esc_html(number_format((float) $cb_sqft)); ?></dd></div>
            <?php endif; ?>
            <?php if ($cb_lot) : ?>
                <div class="cb-listing-spec"><dt>Lot Size</dt><dd><?php echo esc_html(number_format((float) $cb_lot, 2)); ?> ac</dd></div>
            <?php endif; ?>
            <?php if ($cb_year) : ?>
                <div class="cb-listing-spec"><dt>Year Built</dt><dd><?php echo esc_html($cb_year); ?></dd></div>
            <?php endif; ?>
            <?php if ($cb_price_display) : ?>
                <div class="cb-listing-spec cb-listing-spec--price"><dt>List Price</dt><dd><?php echo esc_html($cb_price_display); ?></dd></div>
            <?php endif; ?>
        </dl>
    </div>
</section>

<?php if ($cb_remarks && $cb_remarks !== '********') : ?>
<!-- Description -->
<section class="cb-section" style="padding:4rem 0 2rem;">
    <div class="cb-container" style="max-width:880px;">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Property Details</span>
            <h2 class="cb-section__title"><?php echo esc_html($cb_h2_about); ?></h2>
            <div class="cb-section__divider"></div>
        </div>
        <div class="cb-listing-description cb-reveal">
            <?php echo wpautop(esc_html($cb_remarks)); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($cb_has_schools) : ?>
<!-- Schools -->
<section class="cb-section cb-section--offwhite" style="padding:3rem 0;">
    <div class="cb-container" style="max-width:880px;">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">School District</span>
            <h2 class="cb-section__title"><?php echo esc_html($cb_h2_schools); ?></h2>
            <div class="cb-section__divider"></div>
            <?php if ($cb_county) : ?>
                <p class="cb-section__desc">Located in <?php echo esc_html($cb_county); ?> County, San Angelo ISD school zones.</p>
            <?php endif; ?>
        </div>
        <ul class="cb-listing-schools cb-reveal">
            <?php if ($cb_school_elem) : ?>
                <li><strong>Elementary</strong><span><?php echo esc_html($cb_school_elem); ?></span></li>
            <?php endif; ?>
            <?php if ($cb_school_mid) : ?>
                <li><strong>Middle / Junior</strong><span><?php echo esc_html($cb_school_mid); ?></span></li>
            <?php endif; ?>
            <?php if ($cb_school_high) : ?>
                <li><strong>High School</strong><span><?php echo esc_html($cb_school_high); ?></span></li>
            <?php endif; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($cb_gallery)) : ?>
<!-- Gallery -->
<section class="cb-section<?php echo $cb_has_schools ? '' : ' cb-section--offwhite'; ?>" id="gallery" style="padding:3rem 0 4rem;">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Photos</span>
            <h2 class="cb-section__title"><?php echo esc_html($cb_h2_gallery); ?></h2>
            <div class="cb-section__divider"></div>
        </div>
        <div class="cb-listing-gallery" data-cb-lightbox>
            <?php $cb_total = count($cb_gallery); foreach ($cb_gallery as $i => $url) : ?>
                <button type="button" class="cb-listing-gallery__item" data-index="<?php echo (int) $i; ?>" data-full="<?php echo esc_url($url); ?>">
                    <img src="<?php echo esc_url($url); ?>"
                         alt="<?php echo esc_attr($cb_address . ' — photo ' . ($i + 1) . ' of ' . $cb_total); ?>"
                         <?php echo $i === 0 ? '' : 'loading="lazy"'; ?>>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Listing agent + Schedule a Showing -->
<section class="cb-section<?php echo $cb_has_schools ? ' cb-section--offwhite' : ''; ?>" id="schedule" style="padding:4rem 0;">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Tour This Home</span>
            <h2 class="cb-section__title"><?php echo esc_html($cb_h2_schedule); ?></h2>
            <div class="cb-section__divider"></div>
        </div>
        <div class="cb-listing-agent-grid">
            <div class="cb-listing-agent-card cb-reveal--left">
                <span class="cb-section__subtitle">Listed By</span>
                <?php if ($cb_agent_name) : ?>
                    <h3 class="cb-listing-agent-card__name"><?php echo esc_html($cb_agent_name); ?></h3>
                <?php endif; ?>
                <?php if ($cb_office_name) : ?>
                    <p class="cb-listing-agent-card__office"><?php echo esc_html($cb_office_name); ?></p>
                <?php endif; ?>
                <p class="cb-listing-agent-card__pitch">Schedule a private showing with a Coldwell Banker Legacy specialist. We'll coordinate with the listing agent and walk you through the property in person.</p>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', get_theme_mod('cb_phone', '(325) 944-9559'))); ?>" class="cb-btn cb-btn--outline">Call (325) 944-9559</a>
            </div>

            <div class="cb-listing-form cb-reveal--right">
                <h3 style="font-family:var(--font-heading);color:var(--cb-navy);margin-bottom:0.25rem;">Schedule a Showing</h3>
                <p style="color:var(--cb-text-muted);margin-bottom:1.5rem;">Tell us when works for you and we'll confirm within the hour.</p>
                <form id="cb-contact-form" class="cb-form">
                    <input type="hidden" id="contact-subject" name="subject" value="Showing Request: <?php echo esc_attr($cb_address); ?>">
                    <div class="cb-form__row">
                        <div class="cb-form__field">
                            <label for="contact-first">First Name *</label>
                            <input type="text" id="contact-first" name="first_name" required>
                        </div>
                        <div class="cb-form__field">
                            <label for="contact-last">Last Name</label>
                            <input type="text" id="contact-last" name="last_name">
                        </div>
                    </div>
                    <div class="cb-form__row">
                        <div class="cb-form__field">
                            <label for="contact-email">Email *</label>
                            <input type="email" id="contact-email" name="email" required>
                        </div>
                        <div class="cb-form__field">
                            <label for="contact-phone">Phone</label>
                            <input type="tel" id="contact-phone" name="phone">
                        </div>
                    </div>
                    <div class="cb-form__field">
                        <label for="contact-message">When would you like to tour? *</label>
                        <textarea id="contact-message" name="message" rows="4" required placeholder="Property: <?php echo esc_attr($cb_address); ?>&#10;Preferred date/time: "><?php echo esc_textarea("Property: $cb_address\nPreferred date/time: "); ?></textarea>
                    </div>
                    <button type="submit" class="cb-btn cb-btn--primary cb-btn--lg" style="width:100%;">Request Showing</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Related listings -->
<section class="cb-section<?php echo $cb_has_schools ? '' : ' cb-section--offwhite'; ?>" style="padding:4rem 0;">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Continue Exploring</span>
            <h2 class="cb-section__title"><?php echo esc_html($cb_h2_related); ?></h2>
            <div class="cb-section__divider"></div>
        </div>
        <div class="cb-reveal">
            <?php echo do_shortcode('[cb_listings filter="featured" count="3" columns="3"]'); ?>
        </div>
    </div>
</section>

</div>

<!-- Lightbox container (populated by lightbox.js) -->
<div class="cb-lightbox" id="cb-lightbox" hidden>
    <button type="button" class="cb-lightbox__close" aria-label="Close">&times;</button>
    <button type="button" class="cb-lightbox__prev" aria-label="Previous">&lsaquo;</button>
    <button type="button" class="cb-lightbox__next" aria-label="Next">&rsaquo;</button>
    <img class="cb-lightbox__img" src="" alt="">
    <div class="cb-lightbox__counter"></div>
</div>

<?php get_footer(); ?>

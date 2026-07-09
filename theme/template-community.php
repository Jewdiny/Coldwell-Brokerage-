<?php
/**
 * Community / Neighborhood Page Template
 *
 * Rendered for /communities/<slug>/ URLs (matched via the rewrite rule in
 * functions.php). Pulls the community config from cb_get_communities(),
 * shows a branded hero + the live MLS listings filtered to that area.
 *
 * @package CB_Legacy_Luxury
 */

$cb_slug        = sanitize_key(get_query_var('cb_community_slug'));
$cb_communities = cb_get_communities();

// Index page: /communities/ — show all areas as a grid of cards.
if ($cb_slug === 'index') {
    $cb_index_title = 'San Angelo Neighborhoods & Communities | Coldwell Banker Legacy';
    add_filter('wpseo_title',           function () use ($cb_index_title) { return $cb_index_title; }, 99);
    add_filter('pre_get_document_title', function () use ($cb_index_title) { return $cb_index_title; }, 99);

    get_header();
    ?>
    <div style="padding-top:var(--header-height);">
    <section class="cb-page-hero" style="min-height:340px;">
        <div class="cb-page-hero__overlay" style="background:linear-gradient(135deg,#0a1628,#012169);opacity:1;"></div>
        <div class="cb-page-hero__content">
            <span class="cb-section__subtitle cb-reveal">Explore the Area</span>
            <h1 class="cb-reveal">San Angelo Communities</h1>
            <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
            <p class="cb-reveal" style="max-width:640px;margin:1.5rem auto 0;color:rgba(255,255,255,0.9);font-size:1.125rem;">
                From historic downtown San Angelo to lakefront retreats and country acreage, find your neighborhood in the Concho Valley.
            </p>
        </div>
    </section>

    <section class="cb-section">
        <div class="cb-container">
            <div class="cb-community-grid">
                <?php foreach ($cb_communities as $slug => $c) : ?>
                    <?php $img = cb_community_image_url($c); ?>
                    <a href="<?php echo esc_url(home_url('/communities/' . $slug . '/')); ?>"
                       class="cb-community-card<?php echo $img ? ' cb-community-card--image' : ''; ?> cb-reveal--scale">
                        <?php if ($img) : ?>
                            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($c['name'] . ', San Angelo TX'); ?>" class="cb-community-card__image" loading="lazy">
                        <?php endif; ?>
                        <div class="cb-community-card__overlay">
                            <h3 class="cb-community-card__name"><?php echo esc_html($c['name']); ?></h3>
                            <span class="cb-community-card__count">View Listings</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    </div>
    <?php
    get_footer();
    return;
}

// Single-community page.
if (!isset($cb_communities[$cb_slug])) {
    status_header(404);
    nocache_headers();
    get_header();
    echo '<div style="padding:8rem 0;text-align:center;"><h1>Community Not Found</h1><p>We couldn\'t find that neighborhood.</p><p><a href="' . esc_url(home_url('/communities/')) . '" class="cb-btn cb-btn--primary">All Communities</a></p></div>';
    get_footer();
    return;
}

$cb         = $cb_communities[$cb_slug];
$cb_name    = $cb['name'];
$cb_canonical = home_url('/communities/' . $cb_slug . '/');
$cb_page_title = $cb_name . ' Homes for Sale, San Angelo TX | Coldwell Banker Legacy';
$cb_meta_desc  = mb_substr(trim(preg_replace('/\s+/', ' ', $cb['description'])), 0, 155);

// Yoast filters (if Yoast is active) so it serves our values.
$cb_has_yoast = defined('WPSEO_VERSION');
if ($cb_has_yoast) {
    add_filter('wpseo_title',           function () use ($cb_page_title) { return $cb_page_title; }, 99);
    add_filter('wpseo_metadesc',        function () use ($cb_meta_desc) { return $cb_meta_desc; }, 99);
    add_filter('wpseo_canonical',       function () use ($cb_canonical) { return $cb_canonical; }, 99);
    add_filter('wpseo_opengraph_title', function () use ($cb_page_title) { return $cb_page_title; }, 99);
    add_filter('wpseo_opengraph_desc',  function () use ($cb_meta_desc) { return $cb_meta_desc; }, 99);
    add_filter('wpseo_opengraph_url',   function () use ($cb_canonical) { return $cb_canonical; }, 99);
}

add_filter('pre_get_document_title', function () use ($cb_page_title) { return $cb_page_title; }, 99);

// JSON-LD Place schema for the community + breadcrumbs.
add_action('wp_head', function () use ($cb, $cb_name, $cb_canonical, $cb_meta_desc, $cb_has_yoast) {
    if (!$cb_has_yoast) {
        echo "\n";
        echo '<link rel="canonical" href="' . esc_url($cb_canonical) . '">' . "\n";
        echo '<meta name="description" content="' . esc_attr($cb_meta_desc) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($cb_name . ' Homes for Sale, San Angelo TX | Coldwell Banker Legacy') . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($cb_meta_desc) . '">' . "\n";
    }
    $schema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'Place',
                'name'        => $cb_name . ', San Angelo, TX',
                'description' => $cb['description'],
                'url'         => $cb_canonical,
                'address'     => [
                    '@type'           => 'PostalAddress',
                    'addressLocality' => 'San Angelo',
                    'addressRegion'   => 'TX',
                    'addressCountry'  => 'US',
                ],
            ],
            [
                '@type'           => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',        'item' => home_url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Communities', 'item' => home_url('/communities/')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $cb_name],
                ],
            ],
        ],
    ];
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
});

get_header();
?>

<div style="padding-top:var(--header-height);">

<?php $cb_hero_img = cb_community_image_url($cb); ?>
<!-- Hero -->
<section class="cb-community-hero<?php echo $cb_hero_img ? ' cb-community-hero--image' : ''; ?>"
         <?php echo $cb_hero_img ? 'style="background-image:url(' . esc_url($cb_hero_img) . ');"' : ''; ?>>
    <div class="cb-community-hero__overlay"></div>
    <div class="cb-container cb-community-hero__content cb-reveal">
        <nav class="cb-community-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
            <span>›</span>
            <a href="<?php echo esc_url(home_url('/communities/')); ?>">Communities</a>
            <span>›</span>
            <span class="cb-community-breadcrumb__current"><?php echo esc_html($cb_name); ?></span>
        </nav>
        <span class="cb-section__subtitle" style="color:var(--cb-gold-light);">Coldwell Banker Legacy</span>
        <h1 class="cb-community-hero__title"><?php echo esc_html($cb_name); ?> Homes for Sale</h1>
        <h2 class="cb-community-hero__tagline"><?php echo esc_html($cb['tagline']); ?> &middot; San Angelo, TX</h2>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-community-hero__desc"><?php echo esc_html($cb['description']); ?></p>
    </div>
</section>

<!-- Live MLS listings filtered to this area -->
<section class="cb-section" id="cb-community-listings">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Active Listings</span>
            <h2 class="cb-section__title">Homes for Sale in <?php echo esc_html($cb_name); ?></h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">Live from the San Angelo MLS, updated every 15 minutes.</p>
        </div>
        <div class="cb-reveal">
            <?php
            $expr = "StandardStatus Eq 'Active' And " . $cb['expr'];
            // Call the shortcode handler directly so the SparkQL keeps its single quotes.
            echo CB_Spark_Shortcodes::render_listings([
                'expr'    => $expr,
                'count'   => 24,
                'columns' => 3,
            ]);
            ?>
        </div>
    </div>
</section>

<!-- Other communities -->
<section class="cb-section cb-section--offwhite" style="padding:4rem 0;">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Explore More</span>
            <h2 class="cb-section__title">Other San Angelo Communities</h2>
            <div class="cb-section__divider"></div>
        </div>
        <div class="cb-community-grid">
            <?php foreach ($cb_communities as $slug => $c) : if ($slug === $cb_slug) continue; ?>
                <a href="<?php echo esc_url(home_url('/communities/' . $slug . '/')); ?>" class="cb-community-card cb-reveal--scale">
                    <div class="cb-community-card__overlay">
                        <h3 class="cb-community-card__name"><?php echo esc_html($c['name']); ?></h3>
                        <span class="cb-community-card__count">View Listings</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.svg'); ?>');"></div>
    <div class="cb-cta__overlay"></div>
    <div class="cb-container">
        <div class="cb-cta__content">
            <h2 class="cb-cta__title cb-reveal">Have Questions About <?php echo esc_html($cb_name); ?>?</h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Our agents know every neighborhood in the Concho Valley. We'll match you with the right home.
            </p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--outline cb-btn--lg cb-reveal">Talk to a Local Expert</a>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

<?php
/**
 * School-Zone Landing Page Template
 *
 * Rendered for /schools/<slug>/ URLs. Shows live MLS listings filtered to
 * homes inside that school's attendance zone — a high-intent buyer query.
 *
 * @package CB_Legacy_Luxury
 */

$cb_slug    = sanitize_key(get_query_var('cb_school_slug'));
$cb_schools = cb_get_schools();

// Index — list all school zones.
if ($cb_slug === 'index') {
    $cb_index_title = 'San Angelo School Zones — Find Homes by School District | Coldwell Banker Legacy';
    cb_set_seo_meta([
        'title'       => $cb_index_title,
        'description' => 'Search San Angelo homes for sale by school zone — Central HS, Lake View HS, Grape Creek, Wall, Christoval. Find homes inside the school attendance zone you want.',
        'canonical'   => home_url('/schools/'),
    ]);

    get_header();
    ?>
    <div style="padding-top:var(--header-height);">
    <section class="cb-page-hero" style="min-height:340px;">
        <div class="cb-page-hero__overlay" style="background:linear-gradient(135deg,#0a1628,#012169);opacity:1;"></div>
        <div class="cb-page-hero__content">
            <span class="cb-section__subtitle cb-reveal">Find Your Zone</span>
            <h1 class="cb-reveal">San Angelo Homes by School Zone</h1>
            <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
            <p class="cb-reveal" style="max-width:640px;margin:1.5rem auto 0;color:rgba(255,255,255,0.9);font-size:1.125rem;">
                Find homes inside the attendance zone for San Angelo's top high schools — including Central, Lake View, Wall, and more.
            </p>
        </div>
    </section>

    <section class="cb-section">
        <div class="cb-container">
            <div class="cb-school-grid">
                <?php foreach ($cb_schools as $slug => $s) : ?>
                    <a href="<?php echo esc_url(home_url('/schools/' . $slug . '/')); ?>" class="cb-school-card cb-reveal--scale">
                        <div class="cb-school-card__inner">
                            <span class="cb-school-card__district"><?php echo esc_html($s['district']); ?></span>
                            <h3 class="cb-school-card__name"><?php echo esc_html($s['name']); ?></h3>
                            <p class="cb-school-card__desc"><?php echo esc_html(mb_substr($s['description'], 0, 130)) . '…'; ?></p>
                            <span class="cb-school-card__cta">View Homes →</span>
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

// Single school page.
if (!isset($cb_schools[$cb_slug])) {
    status_header(404);
    nocache_headers();
    get_header();
    echo '<div style="padding:8rem 0;text-align:center;"><h1>School Zone Not Found</h1><p>We don\'t have that school zone configured yet.</p><p><a href="' . esc_url(home_url('/schools/')) . '" class="cb-btn cb-btn--primary">All School Zones</a></p></div>';
    get_footer();
    return;
}

$s             = $cb_schools[$cb_slug];
$cb_canonical  = home_url('/schools/' . $cb_slug . '/');
$cb_page_title = "Homes for Sale in {$s['name']} Zone, San Angelo TX | Coldwell Banker Legacy";
$cb_meta_desc  = mb_substr(trim(preg_replace('/\s+/', ' ', $s['description'])), 0, 155);

cb_set_seo_meta([
    'title'       => $cb_page_title,
    'description' => $cb_meta_desc,
    'canonical'   => $cb_canonical,
]);

// JSON-LD: BreadcrumbList + Place schema for the school attendance area.
add_action('wp_head', function () use ($s, $cb_canonical) {
    $schema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'Place',
                'name'        => $s['name'] . ' Attendance Zone, San Angelo, TX',
                'description' => $s['description'],
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
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',         'item' => home_url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'School Zones', 'item' => home_url('/schools/')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $s['name']],
                ],
            ],
        ],
    ];
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
});

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Hero -->
<section class="cb-community-hero">
    <div class="cb-container cb-community-hero__content cb-reveal">
        <nav class="cb-community-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
            <span>›</span>
            <a href="<?php echo esc_url(home_url('/schools/')); ?>">School Zones</a>
            <span>›</span>
            <span class="cb-community-breadcrumb__current"><?php echo esc_html($s['short']); ?></span>
        </nav>
        <span class="cb-section__subtitle" style="color:var(--cb-gold-light);"><?php echo esc_html($s['district']); ?></span>
        <h1 class="cb-community-hero__title">Homes for Sale in <?php echo esc_html($s['name']); ?> Zone</h1>
        <h2 class="cb-community-hero__tagline">San Angelo, TX &middot; Live MLS data, updated every 15 minutes</h2>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-community-hero__desc"><?php echo esc_html($s['description']); ?></p>
    </div>
</section>

<!-- Listings filtered to this school zone -->
<section class="cb-section" id="cb-school-listings">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Active Listings</span>
            <h2 class="cb-section__title">Active Homes in the <?php echo esc_html($s['short']); ?> Attendance Zone</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">All currently available homes inside the <?php echo esc_html($s['name']); ?> school zone.</p>
        </div>
        <div class="cb-reveal">
            <?php
            $expr = "StandardStatus Eq 'Active' And " . $s['expr'];
            echo CB_Spark_Shortcodes::render_listings([
                'expr'    => $expr,
                'count'   => 24,
                'columns' => 3,
            ]);
            ?>
        </div>
    </div>
</section>

<!-- Other school zones -->
<section class="cb-section cb-section--offwhite" style="padding:4rem 0;">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Explore More</span>
            <h2 class="cb-section__title">Other San Angelo School Zones</h2>
            <div class="cb-section__divider"></div>
        </div>
        <div class="cb-school-grid">
            <?php foreach ($cb_schools as $slug => $other) : if ($slug === $cb_slug) continue; ?>
                <a href="<?php echo esc_url(home_url('/schools/' . $slug . '/')); ?>" class="cb-school-card cb-reveal--scale">
                    <div class="cb-school-card__inner">
                        <span class="cb-school-card__district"><?php echo esc_html($other['district']); ?></span>
                        <h3 class="cb-school-card__name"><?php echo esc_html($other['name']); ?></h3>
                        <span class="cb-school-card__cta">View Homes →</span>
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
            <h2 class="cb-cta__title cb-reveal">Buying for the School Zone?</h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Our agents can help you find the perfect home inside <?php echo esc_html($s['name']); ?>'s attendance boundaries.
            </p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--outline cb-btn--lg cb-reveal">Talk to a Local Expert</a>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

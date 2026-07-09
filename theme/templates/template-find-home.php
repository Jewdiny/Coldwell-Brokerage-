<?php
/**
 * Template Name: Find a Home
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'San Angelo MLS Search — Live Homes for Sale | Coldwell Banker Legacy',
    'description' => 'Search the live San Angelo MLS for homes, ranches, and luxury properties. Filter by neighborhood, price, and beds. Real-time listings with map view.',
    'canonical'   => get_permalink(),
]);

// ---------------------------------------------------------------------------
// Read filter params from query string and build a Spark _filter expression.
// ---------------------------------------------------------------------------
$communities = function_exists('cb_get_communities') ? cb_get_communities() : [];

$f_neighborhood = isset($_GET['neighborhood']) ? sanitize_key($_GET['neighborhood']) : '';
$f_min          = isset($_GET['min'])   ? intval($_GET['min'])   : 0;
$f_max          = isset($_GET['max'])   ? intval($_GET['max'])   : 0;
$f_beds         = isset($_GET['beds'])  ? intval($_GET['beds'])  : 0;
$f_baths        = isset($_GET['baths']) ? intval($_GET['baths']) : 0;
$f_type         = isset($_GET['type'])  ? sanitize_text_field($_GET['type']) : '';
$f_sort         = isset($_GET['sort'])  ? sanitize_text_field($_GET['sort']) : 'newest';

$parts = ["StandardStatus Eq 'Active'"];

if ($f_neighborhood && isset($communities[$f_neighborhood])) {
    $parts[] = '(' . $communities[$f_neighborhood]['expr'] . ')';
} else {
    // Default scope: San Angelo + Concho Valley cities so the map stays centered.
    $parts[] = "(City Eq 'San Angelo' Or City Eq 'Grape Creek' Or City Eq 'Christoval' Or City Eq 'Wall' Or City Eq 'Mertzon' Or City Eq 'Carlsbad' Or City Eq 'Eola' Or City Eq 'Mereta' Or City Eq 'Rowena' Or City Eq 'Veribest' Or City Eq 'Water Valley')";
}

if ($f_min > 0) { $parts[] = 'ListPrice Ge ' . $f_min; }
if ($f_max > 0) { $parts[] = 'ListPrice Le ' . $f_max; }
if ($f_beds > 0) { $parts[] = 'BedsTotal Ge ' . $f_beds; }
if ($f_baths > 0) { $parts[] = 'BathsTotal Ge ' . $f_baths; }

if ($f_type) {
    $allowed_types = ['Residential', 'Land', 'Farm', 'Commercial Sale', 'Residential Income'];
    if (in_array($f_type, $allowed_types, true)) {
        $parts[] = "PropertyType Eq '" . str_replace("'", "''", $f_type) . "'";
    }
}

$expr = implode(' And ', $parts);

$orderby_map = [
    'newest'     => 'ModificationTimestamp desc',
    'price-asc'  => 'ListPrice asc',
    'price-desc' => 'ListPrice desc',
    'beds-desc'  => 'BedsTotal desc',
];
$orderby = $orderby_map[$f_sort] ?? $orderby_map['newest'];

// Heading copy reflects current filter so the page is keyword-targeted per state.
$page_h1 = 'San Angelo Homes for Sale';
if ($f_neighborhood && isset($communities[$f_neighborhood])) {
    $page_h1 = $communities[$f_neighborhood]['name'] . ' Homes for Sale';
}

get_header();
?>

<div class="cb-find" style="padding-top:var(--header-height);">

<!-- Neighborhood Quick-Pills -->
<section class="cb-neighborhood-pills-wrap">
    <div class="cb-container">
        <div class="cb-neighborhood-pills" role="tablist" aria-label="Filter by neighborhood">
            <a href="<?php echo esc_url(get_permalink()); ?>" class="cb-neighborhood-pill <?php echo $f_neighborhood === '' ? 'cb-neighborhood-pill--active' : ''; ?>">All Areas</a>
            <?php foreach ($communities as $slug => $c) :
                $is_active = $f_neighborhood === $slug;
                $pill_url  = add_query_arg('neighborhood', $slug, get_permalink());
                ?>
                <a href="<?php echo esc_url($pill_url); ?>" class="cb-neighborhood-pill <?php echo $is_active ? 'cb-neighborhood-pill--active' : ''; ?>">
                    <?php echo esc_html($c['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Filter Bar -->
<section class="cb-filter-bar-wrap">
    <div class="cb-container">
        <form class="cb-filter-bar" method="get" action="<?php echo esc_url(get_permalink()); ?>" id="cb-filter-form">
            <?php if ($f_neighborhood) : ?>
                <input type="hidden" name="neighborhood" value="<?php echo esc_attr($f_neighborhood); ?>">
            <?php endif; ?>

            <div class="cb-filter-bar__group">
                <label class="cb-filter-bar__label" for="cb-f-min">Min Price</label>
                <select name="min" id="cb-f-min" class="cb-filter-bar__select">
                    <option value="0">Any</option>
                    <?php foreach ([100000, 150000, 200000, 250000, 300000, 400000, 500000, 750000, 1000000] as $v) : ?>
                        <option value="<?php echo $v; ?>" <?php selected($f_min, $v); ?>>$<?php echo number_format($v); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cb-filter-bar__group">
                <label class="cb-filter-bar__label" for="cb-f-max">Max Price</label>
                <select name="max" id="cb-f-max" class="cb-filter-bar__select">
                    <option value="0">Any</option>
                    <?php foreach ([200000, 300000, 400000, 500000, 750000, 1000000, 1500000, 2000000, 5000000] as $v) : ?>
                        <option value="<?php echo $v; ?>" <?php selected($f_max, $v); ?>>$<?php echo number_format($v); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cb-filter-bar__group">
                <label class="cb-filter-bar__label" for="cb-f-beds">Beds</label>
                <select name="beds" id="cb-f-beds" class="cb-filter-bar__select">
                    <option value="0">Any</option>
                    <option value="1" <?php selected($f_beds, 1); ?>>1+</option>
                    <option value="2" <?php selected($f_beds, 2); ?>>2+</option>
                    <option value="3" <?php selected($f_beds, 3); ?>>3+</option>
                    <option value="4" <?php selected($f_beds, 4); ?>>4+</option>
                    <option value="5" <?php selected($f_beds, 5); ?>>5+</option>
                </select>
            </div>

            <div class="cb-filter-bar__group">
                <label class="cb-filter-bar__label" for="cb-f-baths">Baths</label>
                <select name="baths" id="cb-f-baths" class="cb-filter-bar__select">
                    <option value="0">Any</option>
                    <option value="1" <?php selected($f_baths, 1); ?>>1+</option>
                    <option value="2" <?php selected($f_baths, 2); ?>>2+</option>
                    <option value="3" <?php selected($f_baths, 3); ?>>3+</option>
                    <option value="4" <?php selected($f_baths, 4); ?>>4+</option>
                </select>
            </div>

            <div class="cb-filter-bar__group">
                <label class="cb-filter-bar__label" for="cb-f-type">Type</label>
                <select name="type" id="cb-f-type" class="cb-filter-bar__select">
                    <option value="">Any</option>
                    <option value="Residential" <?php selected($f_type, 'Residential'); ?>>Residential</option>
                    <option value="Land" <?php selected($f_type, 'Land'); ?>>Land</option>
                    <option value="Farm" <?php selected($f_type, 'Farm'); ?>>Farm/Ranch</option>
                    <option value="Commercial Sale" <?php selected($f_type, 'Commercial Sale'); ?>>Commercial</option>
                </select>
            </div>

            <div class="cb-filter-bar__group">
                <label class="cb-filter-bar__label" for="cb-f-sort">Sort</label>
                <select name="sort" id="cb-f-sort" class="cb-filter-bar__select">
                    <option value="newest"     <?php selected($f_sort, 'newest'); ?>>Newest</option>
                    <option value="price-desc" <?php selected($f_sort, 'price-desc'); ?>>Price &darr;</option>
                    <option value="price-asc"  <?php selected($f_sort, 'price-asc'); ?>>Price &uarr;</option>
                    <option value="beds-desc"  <?php selected($f_sort, 'beds-desc'); ?>>Most Beds</option>
                </select>
            </div>

            <button type="submit" class="cb-btn cb-btn--primary cb-filter-bar__submit">Search</button>
            <button type="button" class="cb-filter-bar__save" id="cb-save-search" aria-pressed="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                <span class="cb-filter-bar__save-label">Save search</span>
            </button>
            <?php if ($f_neighborhood || $f_min || $f_max || $f_beds || $f_baths || $f_type) : ?>
                <a href="<?php echo esc_url(get_permalink()); ?>" class="cb-filter-bar__clear">Clear all</a>
            <?php endif; ?>
        </form>

        <button type="button" class="cb-map-toggle" id="cb-map-toggle" aria-pressed="false">
            <span class="cb-map-toggle__label cb-map-toggle__label--show">Show Map</span>
            <span class="cb-map-toggle__label cb-map-toggle__label--hide">Hide Map</span>
        </button>
    </div>
</section>

<!-- Split View: Map + Cards (Zillow-style) -->
<section class="cb-search-split" id="cb-search-split">
    <div class="cb-search-split__container">
        <div class="cb-search-split__map-col">
            <div class="cb-search-split__map" id="cb-map" data-default-lat="31.4377" data-default-lng="-100.4503" data-default-zoom="11">
                <div class="cb-search-split__map-loading">Loading map&hellip;</div>
            </div>
        </div>

        <div class="cb-search-split__listings" id="cb-search-listings">
            <div class="cb-results-head">
                <div class="cb-results-head__text">
                    <h1 class="cb-results-head__title"><?php echo esc_html($page_h1); ?></h1>
                    <p class="cb-results-head__count"><span id="cb-results-count">&mdash;</span> <span id="cb-results-noun">homes</span> for sale</p>
                </div>
            </div>
            <?php
            echo CB_Spark_Shortcodes::render_listings([
                'expr'    => $expr,
                'count'   => 50,
                'columns' => 2,
                'orderby' => $orderby,
                'class'   => 'cb-property-grid--split',
            ]);
            ?>
        </div>
    </div>
</section>

<!-- Why Search With Us -->
<section class="cb-section">
    <div class="cb-container" style="max-width:900px;">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Why Coldwell Banker</span>
            <h2 class="cb-section__title">Your Search Advantage</h2>
            <div class="cb-section__divider"></div>
        </div>

        <div class="cb-benefits-grid" style="grid-template-columns:repeat(3,1fr);">
            <div class="cb-benefit-card cb-reveal" style="text-align:center;">
                <div class="cb-benefit-card__number" style="font-size:3rem;">MLS</div>
                <h3 style="font-size:1.125rem;">Live MLS Data</h3>
                <p>Real-time listings from the San Angelo Association of REALTORS&reg; &mdash; same data your agent sees.</p>
            </div>
            <div class="cb-benefit-card cb-reveal" style="text-align:center;">
                <div class="cb-benefit-card__number" style="font-size:3rem;">32+</div>
                <h3 style="font-size:1.125rem;">Local Experts</h3>
                <p>Our agents know every neighborhood, school district, and hidden gem in the Concho Valley.</p>
            </div>
            <div class="cb-benefit-card cb-reveal" style="text-align:center;">
                <div class="cb-benefit-card__number" style="font-size:3rem;">#1</div>
                <h3 style="font-size:1.125rem;">Global Brand</h3>
                <p>Coldwell Banker's network of 100,000+ agents ensures your search has no boundaries.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.svg'); ?>');"></div>
    <div class="cb-cta__overlay"></div>
    <div class="cb-container">
        <div class="cb-cta__content">
            <h2 class="cb-cta__title cb-reveal">Can't Find What You're Looking For?</h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Our agents have access to off-market properties and upcoming listings. Tell us what you need and we'll find it.
            </p>
            <div class="cb-reveal" style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Contact an Agent</a>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', get_theme_mod('cb_phone', '(325) 944-9559'))); ?>" class="cb-btn cb-btn--outline cb-btn--lg">Call (325) 944-9559</a>
            </div>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

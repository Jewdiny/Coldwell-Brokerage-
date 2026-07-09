<?php
/**
 * Template Name: Market Report
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'San Angelo Real Estate Market Report ' . date('Y') . ' | Coldwell Banker Legacy',
    'description' => 'Live San Angelo housing market data — median sale price, days on market, inventory, and neighborhood trends. Updated continuously from the SAARMLS.',
    'canonical'   => get_permalink(),
]);

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Hero -->
<section class="cb-page-hero cb-page-hero--compact">
    <div class="cb-page-hero__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>');"></div>
    <div class="cb-page-hero__overlay"></div>
    <div class="cb-page-hero__content">
        <span class="cb-section__subtitle cb-reveal">Market Intelligence</span>
        <h1 class="cb-reveal">San Angelo Market Report</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-reveal" style="max-width:600px;margin:1.5rem auto 0;opacity:0.9;font-size:1.125rem;">
            Stay informed with the latest real estate market data for San Angelo and the Concho Valley.
        </p>
    </div>
</section>

<!-- Live Market Snapshot -->
<section class="cb-section">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Live Data</span>
            <h2 class="cb-section__title">San Angelo Real Estate Market Snapshot</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">Real-time data pulled directly from the San Angelo Association of REALTORS&reg; MLS. No estimates — these are the actual current numbers.</p>
        </div>

        <div class="cb-reveal">
            <?php echo do_shortcode('[cb_market_stats city="San Angelo"]'); ?>
        </div>
    </div>
</section>

<!-- About the Market (keyword-rich evergreen content) -->
<section class="cb-section cb-section--offwhite">
    <div class="cb-container" style="max-width:880px;">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Market Overview</span>
            <h2 class="cb-section__title">Understanding the San Angelo Housing Market</h2>
            <div class="cb-section__divider"></div>
        </div>
        <div class="cb-listing-description cb-reveal">
            <p>San Angelo is one of West Texas's most resilient and steadily appreciating real estate markets. Anchored by Goodfellow Air Force Base, Angelo State University, oil &amp; gas, healthcare, and agriculture, the local economy sustains consistent housing demand without the boom-bust volatility of larger Texas metros.</p>
            <p>The current San Angelo MLS inventory spans more than a thousand active homes, ranches, and luxury estates across diverse neighborhoods — from established mid-century brick homes in <a href="<?php echo esc_url(home_url('/communities/college-hills/')); ?>">College Hills</a> to gated luxury estates in <a href="<?php echo esc_url(home_url('/communities/bentwood/')); ?>">Bentwood Country Club</a>, lakefront properties on <a href="<?php echo esc_url(home_url('/communities/lake-nasworthy/')); ?>">Lake Nasworthy</a>, and large-acreage country properties in <a href="<?php echo esc_url(home_url('/communities/christoval/')); ?>">Christoval</a>, <a href="<?php echo esc_url(home_url('/communities/wall/')); ?>">Wall</a>, and <a href="<?php echo esc_url(home_url('/communities/grape-creek/')); ?>">Grape Creek</a>.</p>
            <p>Median list prices in San Angelo span the full spectrum — from entry-level homes under $200,000 ideal for first-time buyers, through mid-market family homes in the $300,000–$500,000 range, up to luxury properties exceeding $1 million in Bentwood and on the lake. The market has historically tracked steady single-digit annual appreciation, making San Angelo attractive both for owner-occupants and buy-and-hold investors.</p>
        </div>
    </div>
</section>

<!-- Community Breakdown — Active inventory per area, auto-pulled -->
<section class="cb-section">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Neighborhood Inventory</span>
            <h2 class="cb-section__title">Active Listings by Community</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">Live counts of active homes for sale in each San Angelo-area community.</p>
        </div>

        <div class="cb-market-table-wrap cb-reveal">
            <table class="cb-market-table">
                <thead>
                    <tr>
                        <th>Community</th>
                        <th>Active Listings</th>
                        <th>Browse</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (function_exists('cb_get_communities')) {
                        $client      = new CB_Spark_Client();
                        $communities = cb_get_communities();
                        foreach ($communities as $slug => $c) {
                            $expr  = "StandardStatus Eq 'Active' And " . $c['expr'];
                            $data  = $client->get('listings', [
                                '_filter' => $expr, '_select' => 'Id', '_limit' => 1, '_pagination' => 1,
                            ]);
                            $count = !is_wp_error($data) ? (int) ($data['Pagination']['TotalRows'] ?? 0) : 0;
                            echo '<tr>';
                            echo '<td><strong>' . esc_html($c['name']) . '</strong> <span style="color:var(--cb-text-muted);font-size:0.875rem;">— ' . esc_html($c['tagline']) . '</span></td>';
                            echo '<td>' . esc_html(number_format($count)) . '</td>';
                            echo '<td><a href="' . esc_url(home_url('/communities/' . $slug . '/')) . '" style="color:var(--cb-gold);font-weight:600;">View →</a></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.jpg'); ?>');"></div>
    <div class="cb-cta__overlay"></div>
    <div class="cb-container">
        <div class="cb-cta__content">
            <h2 class="cb-cta__title cb-reveal">Get a Custom Market Analysis</h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Want a detailed analysis for your specific neighborhood or property? Our agents provide complimentary market consultations.
            </p>
            <div class="cb-reveal" style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Get My Home Value</a>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--outline cb-btn--lg">Contact an Agent</a>
            </div>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

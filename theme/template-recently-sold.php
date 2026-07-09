<?php
/**
 * Recently Sold Page Template
 *
 * Rendered for /recently-sold/. Shows MLS-sourced Closed properties from the
 * past 90 days. Useful as buyer-research proof points and SEO target for
 * "recently sold homes san angelo".
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'Recently Sold Homes in San Angelo, TX | Last 90 Days | Coldwell Banker Legacy',
    'description' => 'Browse homes recently sold in San Angelo over the last 90 days. Real sale prices and close dates from the San Angelo MLS — useful for buyers and sellers researching values.',
    'canonical'   => home_url('/recently-sold/'),
]);

get_header();
?>

<div style="padding-top:var(--header-height);">

<section class="cb-page-hero" style="min-height:340px;">
    <div class="cb-page-hero__overlay" style="background:linear-gradient(135deg,#0a1628,#012169);opacity:1;"></div>
    <div class="cb-page-hero__content">
        <span class="cb-section__subtitle cb-reveal">Market Proof</span>
        <h1 class="cb-reveal">Recently Sold Homes in San Angelo</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-reveal" style="max-width:640px;margin:1.5rem auto 0;color:rgba(255,255,255,0.9);font-size:1.125rem;">
            Real sale prices and close dates from the last 90 days, pulled live from the San Angelo Association of REALTORS&reg; MLS.
        </p>
    </div>
</section>

<section class="cb-section">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Last 90 Days</span>
            <h2 class="cb-section__title">Homes Sold in San Angelo, TX</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">These are actual recent sale prices — your best benchmark for what homes in San Angelo are really selling for right now.</p>
        </div>
        <div class="cb-reveal">
            <?php echo do_shortcode('[cb_sold_listings days="90" count="24" columns="3"]'); ?>
        </div>
    </div>
</section>

<section class="cb-section cb-section--offwhite">
    <div class="cb-container" style="max-width:880px;">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Why This Matters</span>
            <h2 class="cb-section__title">Why Look at Recent Sold Prices?</h2>
            <div class="cb-section__divider"></div>
        </div>
        <div class="cb-listing-description cb-reveal">
            <p>Whether you're <strong>buying or selling in San Angelo</strong>, recent comparable sales are the single most reliable indicator of true market value. List prices reflect what sellers <em>want</em> — sale prices reflect what buyers actually <em>paid</em>.</p>
            <p>For sellers, recent sold data tells you where to price your home for the fastest sale at the strongest number. For buyers, it tells you what your offer needs to look like to win — and what you should never pay.</p>
            <p>Want a custom comparison for your specific home or a property you're considering? <a href="<?php echo esc_url(home_url('/home-value/')); ?>">Request a free comparative market analysis</a> from a Coldwell Banker Legacy agent.</p>
        </div>
    </div>
</section>

<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.svg'); ?>');"></div>
    <div class="cb-cta__overlay"></div>
    <div class="cb-container">
        <div class="cb-cta__content">
            <h2 class="cb-cta__title cb-reveal">Considering Buying or Selling?</h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Get a custom report based on recent sales in your neighborhood — no obligation.
            </p>
            <div class="cb-reveal" style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Get My Home Value</a>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--outline cb-btn--lg">Talk to an Agent</a>
            </div>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

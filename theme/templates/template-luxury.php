<?php
/**
 * Template Name: Luxury Market
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'Luxury Homes for Sale in San Angelo, TX | $500K+ Properties | Coldwell Banker Legacy',
    'description' => 'Browse San Angelo luxury real estate $500,000 and up. Estate homes, waterfront properties, and gated communities — represented by Coldwell Banker Global Luxury specialists.',
    'canonical'   => get_permalink(),
]);

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Cinematic Hero -->
<section class="cb-luxury-hero">
    <div class="cb-luxury-hero__bg">
        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>" alt="Luxury Real Estate">
    </div>
    <div class="cb-luxury-hero__overlay"></div>
    <!-- Gold Frame SVG -->
    <svg class="cb-luxury-hero__frame" viewBox="0 0 1200 600" preserveAspectRatio="none">
        <rect x="40" y="40" width="1120" height="520" fill="none" stroke="#C5A44E" stroke-width="2" stroke-dasharray="3280" stroke-dashoffset="3280" class="cb-gold-frame-line"/>
    </svg>
    <div class="cb-luxury-hero__content">
        <span class="cb-section__subtitle cb-reveal" style="color:var(--cb-gold-light);">Coldwell Banker Global Luxury</span>
        <h1 class="cb-reveal" style="color:var(--cb-white);letter-spacing:0.03em;">The Prestige Collection</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-reveal" style="max-width:640px;margin:1.5rem auto 0;color:rgba(255,255,255,0.9);font-size:1.25rem;line-height:1.7;">
            Discover San Angelo's most distinguished properties, presented by our Global Luxury Specialists.
        </p>
    </div>
</section>

<!-- Luxury Listings -->
<section class="cb-section" id="cb-luxury-listings">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Exclusive Properties</span>
            <h2 class="cb-section__title">Luxury Listings</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">Hand-selected properties representing the finest San Angelo has to offer.</p>
        </div>

        <div class="cb-reveal">
            <?php echo do_shortcode('[cb_listings filter="luxury" count="6" columns="2"]'); ?>
        </div>
    </div>
</section>

<!-- Luxury Trend Report -->
<section class="cb-section cb-section--offwhite" id="cb-luxury-trends">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Market Intelligence</span>
            <h2 class="cb-section__title">Luxury Market Trends</h2>
            <div class="cb-section__divider"></div>
        </div>

        <div class="cb-luxury-stats">
            <div class="cb-luxury-stat cb-reveal">
                <div class="cb-luxury-stat__circle" data-percent="85">
                    <svg viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--cb-gray-300)" stroke-width="4"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--cb-gold)" stroke-width="4" stroke-dasharray="339.29" stroke-dashoffset="339.29" stroke-linecap="round" class="cb-progress-ring"/>
                    </svg>
                    <span class="cb-luxury-stat__value">85%</span>
                </div>
                <h4>Sold at or Above Asking</h4>
                <p>Luxury properties in San Angelo</p>
            </div>
            <div class="cb-luxury-stat cb-reveal">
                <div class="cb-luxury-stat__circle" data-percent="92">
                    <svg viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--cb-gray-300)" stroke-width="4"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--cb-gold)" stroke-width="4" stroke-dasharray="339.29" stroke-dashoffset="339.29" stroke-linecap="round" class="cb-progress-ring"/>
                    </svg>
                    <span class="cb-luxury-stat__value">92%</span>
                </div>
                <h4>Client Satisfaction</h4>
                <p>Among luxury home buyers</p>
            </div>
            <div class="cb-luxury-stat cb-reveal">
                <div class="cb-luxury-stat__circle" data-percent="45">
                    <svg viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--cb-gray-300)" stroke-width="4"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--cb-gold)" stroke-width="4" stroke-dasharray="339.29" stroke-dashoffset="339.29" stroke-linecap="round" class="cb-progress-ring"/>
                    </svg>
                    <span class="cb-luxury-stat__value">45</span>
                </div>
                <h4>Average Days on Market</h4>
                <p>For luxury properties</p>
            </div>
            <div class="cb-luxury-stat cb-reveal">
                <div class="cb-luxury-stat__circle" data-percent="78">
                    <svg viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--cb-gray-300)" stroke-width="4"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--cb-gold)" stroke-width="4" stroke-dasharray="339.29" stroke-dashoffset="339.29" stroke-linecap="round" class="cb-progress-ring"/>
                    </svg>
                    <span class="cb-luxury-stat__value">+12%</span>
                </div>
                <h4>Year-Over-Year Growth</h4>
                <p>In luxury home values</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.jpg'); ?>');"></div>
    <div class="cb-cta__overlay" style="background:rgba(10,22,40,0.9);"></div>
    <div class="cb-container">
        <div class="cb-cta__content">
            <span class="cb-section__subtitle cb-reveal" style="color:var(--cb-gold-light);">Begin Your Luxury Journey</span>
            <h2 class="cb-cta__title cb-reveal">Experience the Extraordinary</h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Schedule a private showing with one of our Global Luxury Specialists.
            </p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--outline cb-btn--lg cb-reveal">Schedule a Showing</a>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

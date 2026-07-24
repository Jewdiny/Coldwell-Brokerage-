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

<?php /* BLACK AND WHITE, PER THE BRIEF.
     Coldwell Banker Global Luxury is a monochrome identity -- black, white and
     grey -- not the accent colour the rest of this site runs on. Rather than
     hunt every gold rule, the scope class below REDEFINES the --cb-gold custom
     property for this page only (see pages.css). Every `var(--cb-gold)` inside
     inherits the override automatically, including rules written later, so the
     page cannot drift back to accent colour one component at a time. Dark
     sections re-override it to white, because black on a dark hero is nothing. */ ?>
<div class="cb-luxury-page" style="padding-top:var(--header-height);">

<!-- Cinematic Hero -->
<section class="cb-luxury-hero">
    <div class="cb-luxury-hero__bg">
        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>" alt="Luxury Real Estate">
    </div>
    <div class="cb-luxury-hero__overlay"></div>
    <!-- Frame: monochrome, was #C5A44E -->
    <svg class="cb-luxury-hero__frame" viewBox="0 0 1200 600" preserveAspectRatio="none">
        <rect x="40" y="40" width="1120" height="520" fill="none" stroke="#FFFFFF" stroke-opacity="0.75" stroke-width="1.5" stroke-dasharray="3280" stroke-dashoffset="3280" class="cb-gold-frame-line"/>
    </svg>
    <div class="cb-luxury-hero__content">
        <?php /* The official CB mark, monochrome, above the Global Luxury
             wordmark. The mark is the real brand SVG recoloured; the wordmark is
             set in type rather than faked as a lockup image, because the
             official Global Luxury lockup file has not been supplied and
             approximating a trademarked lockup in Illustrator-by-eye is not
             something to ship. Swap in the real asset when it arrives. */ ?>
        <div class="cb-gl-lockup cb-reveal">
            <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/logos/monogram-horizontal-stacked.svg'); ?>" alt="Coldwell Banker" class="cb-gl-lockup__mark">
            <span class="cb-gl-lockup__word">Global Luxury</span>
        </div>
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

<!-- What Global Luxury Means -->
<?php /* THESE NUMBERS WERE INVENTED.
     This section previously showed four progress rings reading "85% Sold at or
     Above Asking", "92% Client Satisfaction", "45 Average Days on Market" and
     "+12% Year-Over-Year Growth", captioned as San Angelo luxury market data.
     None of it was measured. The client-satisfaction figure in particular is a
     claim about the brokerage's own performance that nobody had surveyed, and
     the rings' data-percent attributes did not even match the numbers printed
     inside them (78 behind "+12%") -- they were decorative placeholders that
     had been left to read as research.

     Real luxury market figures belong on the market report, which pulls them
     from the MLS. This section now describes what the Global Luxury programme
     actually provides, which is what a seller at this price point is deciding
     between and needs no invented statistics to be persuasive. */ ?>
<section class="cb-section cb-section--offwhite" id="cb-luxury-program">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">The Programme</span>
            <h2 class="cb-section__title">What Global Luxury Means for Your Home</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">Coldwell Banker Global Luxury is how a San Angelo property reaches buyers who are not looking in San Angelo.</p>
        </div>

        <div class="cb-gl-points">
            <div class="cb-gl-point cb-reveal">
                <h4>Certified Specialists</h4>
                <p>Your home is listed by an agent who has completed the Global Luxury Property Specialist training, not simply by whoever is available.</p>
            </div>
            <div class="cb-gl-point cb-reveal">
                <h4>International Reach</h4>
                <p>Listings are syndicated through the Coldwell Banker global network and its luxury partner sites, putting a Concho Valley estate in front of buyers relocating from out of state and overseas.</p>
            </div>
            <div class="cb-gl-point cb-reveal">
                <h4>Presentation to Match</h4>
                <p>Professional photography, video and print collateral built for properties where the pictures are doing the selling before anyone books a showing.</p>
            </div>
            <div class="cb-gl-point cb-reveal">
                <h4>Discretion</h4>
                <p>Private and off-market options for sellers who would rather their home not appear on a public search at all. Ask us how that works.</p>
            </div>
        </div>

        <p style="text-align:center;margin-top:2.5rem;">
            <a href="<?php echo esc_url(home_url('/market-report/')); ?>" class="cb-btn cb-btn--navy">See Current Market Data</a>
        </p>
    </div>
</section>



<!-- CTA -->
<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.jpg'); ?>');"></div>
    <?php /* Was rgba(10,22,40,0.9) -- navy. Neutral, to match the monochrome page. */ ?>
    <div class="cb-cta__overlay" style="background:rgba(0,0,0,0.88);"></div>
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

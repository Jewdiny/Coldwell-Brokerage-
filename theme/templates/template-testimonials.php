<?php
/**
 * Template Name: Testimonials
 *
 * Full-page client review wall powered by the Coldwell Banker-approved
 * Testimonial Tree list widget (ID 71289).
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'Client Reviews & Testimonials | Coldwell Banker Legacy San Angelo',
    'description' => 'Read verified client reviews of Coldwell Banker Legacy San Angelo real estate agents. Real stories from buyers and sellers across the Concho Valley.',
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
        <span class="cb-section__subtitle cb-reveal">Client Stories</span>
        <h1 class="cb-reveal">What Our Clients Say</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-reveal" style="max-width:600px;margin:1.5rem auto 0;color:rgba(255,255,255,0.9);font-size:1.125rem;">
            Real, verified reviews from buyers and sellers we've served across San Angelo and the Concho Valley.
        </p>
    </div>
</section>

<!-- Reviews List -->
<section class="cb-section">
    <div class="cb-container" style="max-width:960px;">
        <div class="cb-reveal cb-tt-frame">
            <?php echo do_shortcode('[cb_testimonials type="list"]'); ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.svg'); ?>');"></div>
    <div class="cb-cta__overlay"></div>
    <div class="cb-container">
        <div class="cb-cta__content">
            <h2 class="cb-cta__title cb-reveal">Ready to Work With Us?</h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Join the families across San Angelo who've trusted Coldwell Banker Legacy with their move.
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

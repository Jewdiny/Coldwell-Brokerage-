<?php
/**
 * Single Community Template
 *
 * @package CB_Legacy_Luxury
 */

get_header();

while (have_posts()) : the_post();
?>

<div style="padding-top:var(--header-height);">

<!-- Community Hero -->
<section class="cb-page-hero">
    <div class="cb-page-hero__bg" style="background-image:url('<?php echo has_post_thumbnail() ? esc_url(get_the_post_thumbnail_url(get_the_ID(), 'cb-hero')) : esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>');"></div>
    <div class="cb-page-hero__overlay"></div>
    <div class="cb-page-hero__content">
        <span class="cb-section__subtitle cb-reveal">Explore the Area</span>
        <h1 class="cb-reveal"><?php the_title(); ?></h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
    </div>
</section>

<!-- Community Content -->
<section class="cb-section">
    <div class="cb-container" style="max-width:800px;">
        <div class="cb-reveal">
            <?php if (get_the_content()) : ?>
                <div class="cb-page-content" style="font-size:1.0625rem;line-height:1.8;color:var(--cb-text-muted);">
                    <?php the_content(); ?>
                </div>
            <?php else : ?>
                <p style="font-size:1.125rem;color:var(--cb-text-muted);line-height:1.8;text-align:center;">
                    <?php the_title(); ?> is one of the vibrant communities served by Coldwell Banker Legacy. Whether you're looking for a family home, a luxury property, or a quiet retreat, our agents can help you find the perfect fit in <?php the_title(); ?>.
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.jpg'); ?>');"></div>
    <div class="cb-cta__overlay"></div>
    <div class="cb-container">
        <div class="cb-cta__content">
            <h2 class="cb-cta__title cb-reveal">Interested in <?php the_title(); ?>?</h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Let our agents show you what <?php the_title(); ?> has to offer.
            </p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg cb-reveal">Contact an Agent</a>
        </div>
    </div>
</section>

</div>

<?php endwhile; ?>

<?php get_footer(); ?>

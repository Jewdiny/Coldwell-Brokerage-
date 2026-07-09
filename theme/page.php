<?php
/**
 * Default Page Template
 *
 * @package CB_Legacy_Luxury
 */

get_header();
?>

<div style="padding-top:var(--header-height);">
    <?php while (have_posts()) : the_post(); ?>

    <!-- Page Hero -->
    <section class="cb-section cb-section--navy" style="padding:4rem 0;">
        <div class="cb-container" style="text-align:center;">
            <h1 class="cb-reveal" style="color:var(--cb-white);"><?php the_title(); ?></h1>
            <div class="cb-section__divider" style="margin-top:1.5rem;"></div>
        </div>
    </section>

    <!-- Page Content -->
    <section class="cb-section">
        <div class="cb-container" style="max-width:800px;">
            <div class="cb-page-content cb-reveal">
                <?php the_content(); ?>
            </div>
        </div>
    </section>

    <?php endwhile; ?>
</div>

<?php get_footer(); ?>

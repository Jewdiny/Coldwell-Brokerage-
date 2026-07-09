<?php
/**
 * Default Template
 *
 * @package CB_Legacy_Luxury
 */

get_header();
?>

<div style="padding-top:var(--header-height);">
    <section class="cb-section">
        <div class="cb-container">
            <?php if (have_posts()) : ?>
                <div class="cb-blog-grid">
                    <?php while (have_posts()) : the_post(); ?>
                        <article class="cb-blog-card cb-reveal">
                            <div class="cb-blog-card__image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('cb-blog-thumb'); ?>
                                <?php else : ?>
                                    <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-blog.jpg'); ?>" alt="<?php the_title_attribute(); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="cb-blog-card__body">
                                <?php $categories = get_the_category(); if ($categories) : ?>
                                    <span class="cb-blog-card__category"><?php echo esc_html($categories[0]->name); ?></span>
                                <?php endif; ?>
                                <h3 class="cb-blog-card__title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <p class="cb-blog-card__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
                                <span class="cb-blog-card__meta"><?php echo get_the_date(); ?></span>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                <div style="text-align:center;margin-top:3rem;">
                    <?php the_posts_pagination(['mid_size' => 2, 'prev_text' => '&laquo; Previous', 'next_text' => 'Next &raquo;']); ?>
                </div>
            <?php else : ?>
                <p>No posts found.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>

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
            <?php
            /* This template had no heading of any kind -- the blog listing went
               straight from the site header into a grid of cards. No <h1> means
               a screen reader lands with nothing describing the page, and a
               search engine has only the <title> to go on.

               The title is read from the page assigned to posts in Settings ->
               Reading rather than hardcoded, so renaming that page renames this
               heading too. */
            $cb_blog_page  = (int) get_option('page_for_posts');
            $cb_blog_title = $cb_blog_page ? get_the_title($cb_blog_page) : __('Blog', 'cb-legacy');
            ?>
            <div class="cb-section__header cb-reveal">
                <span class="cb-section__subtitle">From the Concho Valley</span>
                <h1 class="cb-section__title"><?php echo esc_html($cb_blog_title); ?></h1>
                <div class="cb-section__divider"></div>
            </div>

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
                <?php /* "No posts found." reads like an error. Say what is true
                     and give the reader somewhere to go, as the events archive
                     does. */ ?>
                <div class="cb-empty-state" style="text-align:center;max-width:38rem;margin:0 auto;padding:1rem 0;">
                    <p style="font-size:1.0625rem;line-height:1.75;">
                        There&rsquo;s nothing here just yet.
                    </p>
                    <p style="line-height:1.75;">
                        We&rsquo;re working on it &mdash; in the meantime, have a look at
                        <a href="<?php echo esc_url(home_url('/market-report/')); ?>">the current market report</a>.
                    </p>
                    <p style="margin-top:1.75rem;">
                        <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary">Browse San Angelo Homes</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>

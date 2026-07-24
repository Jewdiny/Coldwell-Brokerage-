<?php
/**
 * Community Events archive.
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * There is a Page with the slug `events` AND a `cb_event` post type registered
 * with has_archive => true and the same `events` rewrite slug. The post type
 * wins, so /events/ has always served the ARCHIVE and the Page's content was
 * unreachable -- and with no events published, the archive fell through to
 * index.php and rendered essentially nothing. The page looked broken rather
 * than empty.
 *
 * Rather than rename the Page or drop the post type (which would throw away the
 * structure for real events later), this gives the archive a proper template
 * with an honest empty state. The client's "community events - none now" is
 * then displayed as a deliberate message instead of a blank screen.
 *
 * @package CB_Legacy_Luxury
 */

if (function_exists('cb_set_seo_meta')) {
    cb_set_seo_meta([
        'title'       => 'Community Events — Coldwell Banker Legacy San Angelo',
        'description' => 'Community events and happenings across San Angelo and the Concho Valley, hosted and supported by Coldwell Banker Legacy.',
        'canonical'   => get_post_type_archive_link('cb_event'),
    ]);
}

get_header();
?>

<section class="cb-section" style="padding-top:calc(var(--header-height) + 3rem);">
    <div class="cb-container">
        <div class="cb-section__head">
            <span class="cb-section__subtitle">In the Concho Valley</span>
            <h1 class="cb-section__title">Community Events</h1>
            <p class="cb-section__desc">Coldwell Banker Legacy is proud to support the community we live and work in.</p>
        </div>

        <?php if (have_posts()) : ?>
            <div class="cb-grid cb-grid--3">
                <?php while (have_posts()) : the_post(); ?>
                    <article class="cb-card cb-reveal">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="cb-card__image"><?php the_post_thumbnail('medium_large'); ?></div>
                        <?php endif; ?>
                        <div class="cb-card__body">
                            <h2 class="cb-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <p class="cb-card__meta"><?php echo esc_html(get_the_date()); ?></p>
                            <p><?php echo esc_html(get_the_excerpt()); ?></p>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php the_posts_pagination(['mid_size' => 2]); ?>

        <?php else : ?>
            <?php /* Honest empty state. No invented events -- if nothing is
                 scheduled, say so and give the visitor somewhere to go. */ ?>
            <div class="cb-empty-state" style="text-align:center;max-width:38rem;margin:0 auto;padding:2rem 0 1rem;">
                <p style="font-size:1.125rem;line-height:1.75;">
                    We don&rsquo;t have any events scheduled at the moment.
                </p>
                <p style="line-height:1.75;">
                    Check back soon &mdash; or <a href="<?php echo esc_url(home_url('/contact/')); ?>">get in touch</a>
                    and we&rsquo;ll let you know what&rsquo;s coming up.
                </p>
                <p style="margin-top:1.75rem;">
                    <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary">Browse San Angelo Homes</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>

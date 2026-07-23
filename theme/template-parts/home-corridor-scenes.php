<?php
/**
 * Home "corridor" scenes partial — Home 7 preview.
 *
 * Merges Home 2's text/info-card content with Home 5's 3D hallway. Each section
 * appears as a floating glass card in the corridor. On scroll, the camera pans
 * toward it, the box opens to reveal full vertical-scroll content, then closes
 * back into the hallway as the camera moves to the next section.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

$hero_title    = get_theme_mod('cb_hero_title', 'Find Your Dream Home in San Angelo');
$hero_subtitle = get_theme_mod('cb_hero_subtitle', 'Discover luxury living with Coldwell Banker Legacy. Your trusted partner in San Angelo real estate.');

// ---- float animation helpers (mirrored from office pattern) ----
$cb_corridor_i = 0;
$cb_float = function ($extra = '') use (&$cb_corridor_i) {
    $durs = [8.5, 10.0, 9.2, 11.5, 7.8, 12.4, 9.8, 10.8];
    $dels = [0.0, -2.3, -4.1, -1.2, -5.6, -3.0, -6.2, -0.7];
    $ys   = [10, 14, 8, 16, 12, 9, 15, 11];
    $rots = [0.6, -0.8, 0.5, -0.5, 0.9, -0.7, 0.4, -0.6];
    $i = $cb_corridor_i++;
    return sprintf(
        'style="--fl-dur:%ss;--fl-delay:%ss;--fl-y:%spx;--fl-rot:%sdeg;%s"',
        $durs[$i % count($durs)], $dels[$i % count($dels)], $ys[$i % count($ys)], $rots[$i % count($rots)], $extra
    );
};

$cb_communities = function_exists('cb_get_communities') ? cb_get_communities() : [];
$cb_featured    = ['grape-creek', 'bentwood', 'college-hills', 'christoval', 'wall', 'lake-nasworthy'];

$cb_corridor_nav = [
    0 => 'Arrival', 1 => 'Welcome', 2 => 'Listings', 3 => 'Legacy',
    4 => 'Buy', 5 => 'Communities', 6 => 'Sell', 7 => 'Connect',
];
?>

<div class="cb-corridor-stage" id="cb-corridor-stage" data-cb-corridor>

    <!-- Three.js dust-nebula + corridor backdrop -->
    <canvas id="cb-corridor-canvas" aria-hidden="true"></canvas>
    <div class="cb-corridor-vignette" aria-hidden="true"></div>

    <!-- Side progress rail -->
    <nav class="cb-corridor-nav" aria-label="Page sections">
        <?php foreach ($cb_corridor_nav as $i => $label) : ?>
            <button class="cb-corridor-nav__dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                    data-corridor-to="<?php echo esc_attr($i); ?>" type="button">
                <span class="cb-corridor-nav__label"><?php echo esc_html($label); ?></span>
            </button>
        <?php endforeach; ?>
    </nav>

    <div class="cb-corridor-scenes">

        <!-- ================================================================
             SECTION 0 — ARRIVAL (Hero). Floating cards fade in at the corridor
             threshold. Camera pans from the entrance toward the first box.
             ================================================================ -->
        <section class="cb-corridor-section cb-corridor-section--hero" data-corridor-section="0" id="corridor-arrival" aria-label="Welcome">
            <div class="cb-corridor-section__inner">
                <div class="cb-corridor-card cb-corridor-card--chip" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <span class="cb-corridor-eyebrow">Live Well With Coldwell&#8480;</span>
                        <span class="cb-corridor-coords">31.46&deg;&nbsp;N&nbsp; &middot; &nbsp;100.44&deg;&nbsp;W &middot; San Angelo, Texas</span>
                    </div>
                </div>
                <div class="cb-corridor-card cb-corridor-card--title" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner"><h1 class="cb-corridor-h1"><?php echo esc_html($hero_title); ?></h1></div>
                </div>
                <div class="cb-corridor-card cb-corridor-card--lead" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner"><p class="cb-corridor-sub"><?php echo esc_html($hero_subtitle); ?></p></div>
                </div>
                <div class="cb-corridor-card cb-corridor-card--ghost" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <span class="cb-corridor-cue" aria-hidden="true">
                            Scroll &mdash; walk the corridor
                            <span class="cb-corridor-cue__line"><span></span></span>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 1 — WELCOME / QUICK ACTIONS. Camera pans to reception,
             box opens to vertical scroll content with 4 action cards.
             ================================================================ -->
        <section class="cb-corridor-section" data-corridor-section="1" id="corridor-welcome" aria-label="How can we help">
            <div class="cb-corridor-section__inner">
                <div class="cb-corridor-card cb-corridor-card--head" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <span class="cb-corridor-eyebrow">Welcome to the Concho Valley</span>
                        <h2 class="cb-corridor-h2">At home in San&nbsp;Angelo.</h2>
                        <p class="cb-corridor-p">Whether you&rsquo;re buying, selling, or just dreaming &mdash; start here. Four ways we help you move forward.</p>
                    </div>
                </div>
                <div class="cb-corridor-grid cb-corridor-grid--4">
                    <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-corridor-card cb-corridor-card--action" data-corridor data-cursor="Explore" <?php echo $cb_float(); ?>>
                        <div class="cb-corridor-card__inner">
                            <h3 class="cb-corridor-h3">Find a Home</h3>
                            <p class="cb-corridor-p">Browse available properties across San Angelo and the Concho Valley.</p>
                            <span class="cb-corridor-go">Explore <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                    <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-corridor-card cb-corridor-card--action" data-corridor data-cursor="Value" <?php echo $cb_float(); ?>>
                        <div class="cb-corridor-card__inner">
                            <h3 class="cb-corridor-h3">Sell Your Home</h3>
                            <p class="cb-corridor-p">Get a free home valuation and connect with an expert agent.</p>
                            <span class="cb-corridor-go">Get value <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                    <a href="<?php echo esc_url(home_url('/our-team/')); ?>" class="cb-corridor-card cb-corridor-card--action" data-corridor data-cursor="Meet" <?php echo $cb_float(); ?>>
                        <div class="cb-corridor-card__inner">
                            <h3 class="cb-corridor-h3">Meet Our Team</h3>
                            <p class="cb-corridor-p">Connect with experienced agents who know San Angelo inside and out.</p>
                            <span class="cb-corridor-go">Meet us <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                    <a href="<?php echo esc_url(home_url('/office/')); ?>" class="cb-corridor-card cb-corridor-card--action" data-corridor data-cursor="Visit" <?php echo $cb_float(); ?>>
                        <div class="cb-corridor-card__inner">
                            <h3 class="cb-corridor-h3">Visit Our Office</h3>
                            <p class="cb-corridor-p">Stop by our office on Knickerbocker Road. We&rsquo;d love to meet you.</p>
                            <span class="cb-corridor-go">Directions <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 2 — FEATURED LISTINGS (live MLS). Camera pans to listings
             wall. Box opens to vertical scroll with MLS grid.
             ================================================================ -->
        <section class="cb-corridor-section" data-corridor-section="2" id="corridor-listings" aria-label="Featured listings">
            <div class="cb-corridor-section__inner">
                <div class="cb-corridor-card cb-corridor-card--head" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <span class="cb-corridor-eyebrow">Featured Properties</span>
                        <h2 class="cb-corridor-h2">The latest on the market.</h2>
                        <p class="cb-corridor-p">A hand-picked look at premier San Angelo homes, updated live from the MLS.</p>
                    </div>
                </div>
                <div class="cb-corridor-card cb-corridor-card--frame" data-corridor data-corridor-frame <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <?php echo do_shortcode('[cb_listings filter="featured" count="6" columns="3"]'); ?>
                    </div>
                </div>
                <div class="cb-corridor-card cb-corridor-card--cta-row" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary">View All Properties</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 3 — LEGACY / STATS. Camera passes under the legacy arch.
             Floating stat counters scroll vertically.
             ================================================================ -->
        <section class="cb-corridor-section" data-corridor-section="3" id="corridor-legacy" aria-label="Our track record">
            <div class="cb-corridor-section__inner">
                <div class="cb-corridor-card cb-corridor-card--head" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <span class="cb-corridor-eyebrow">Serving the Concho Valley for over 35 years</span>
                        <h2 class="cb-corridor-h2">A legacy of results in the Concho&nbsp;Valley.</h2>
                    </div>
                </div>
                <div class="cb-corridor-grid cb-corridor-grid--4">
                    <div class="cb-corridor-card cb-corridor-card--stat" data-corridor <?php echo $cb_float(); ?>>
                        <div class="cb-corridor-card__inner">
                            <div class="cb-corridor-stat__num" data-count="<?php echo esc_attr(get_theme_mod('cb_homes_sold', '1200')); ?>">0</div>
                            <div class="cb-corridor-stat__label">Homes Sold</div>
                        </div>
                    </div>
                    <div class="cb-corridor-card cb-corridor-card--stat" data-corridor <?php echo $cb_float(); ?>>
                        <div class="cb-corridor-card__inner">
                            <div class="cb-corridor-stat__num" data-count="30">0</div>
                            <div class="cb-corridor-stat__label">Expert Agents</div>
                        </div>
                    </div>
                    <div class="cb-corridor-card cb-corridor-card--stat" data-corridor <?php echo $cb_float(); ?>>
                        <div class="cb-corridor-card__inner">
                            <div class="cb-corridor-stat__num" data-count="<?php echo esc_attr(get_theme_mod('cb_years_serving', '35')); ?>">0</div>
                            <div class="cb-corridor-stat__label">Years Serving San Angelo</div>
                        </div>
                    </div>
                    <div class="cb-corridor-card cb-corridor-card--stat" data-corridor <?php echo $cb_float(); ?>>
                        <div class="cb-corridor-card__inner">
                            <div class="cb-corridor-stat__num" data-count="20">0</div>
                            <div class="cb-corridor-stat__label">Communities Served</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 4 — BUYERS CTA (Open the door). Camera passes a door
             threshold frame. Vertical scroll shows the buying CTA.
             ================================================================ -->
        <section class="cb-corridor-section cb-corridor-section--center" data-corridor-section="4" id="corridor-buy" aria-label="Browse homes">
            <div class="cb-corridor-section__inner">
                <div class="cb-corridor-card cb-corridor-card--cta" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <span class="cb-corridor-eyebrow">For Buyers</span>
                        <h2 class="cb-corridor-h2">Open the door to San&nbsp;Angelo living.</h2>
                        <p class="cb-corridor-p">Search every active listing in the Concho Valley &mdash; filtered, mapped, and updated in real time.</p>
                        <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">
                            Browse San Angelo Homes
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 5 — COMMUNITIES. Camera pans to the communities table.
             Vertical scroll shows community cards with images.
             ================================================================ -->
        <section class="cb-corridor-section" data-corridor-section="5" id="corridor-communities" aria-label="Featured communities">
            <div class="cb-corridor-section__inner">
                <div class="cb-corridor-card cb-corridor-card--head" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <span class="cb-corridor-eyebrow">Explore the Area</span>
                        <h2 class="cb-corridor-h2">From the river to the ranch&nbsp;land.</h2>
                        <p class="cb-corridor-p">From downtown San Angelo to the scenic shores of Lake Nasworthy, find the neighborhood that fits your life.</p>
                    </div>
                </div>
                <div class="cb-corridor-grid cb-corridor-grid--3">
                    <?php foreach ($cb_featured as $slug) :
                        if (!isset($cb_communities[$slug])) { continue; }
                        $c = $cb_communities[$slug];
                        $img_url = function_exists('cb_community_image_url') ? cb_community_image_url($c) : '';
                    ?>
                        <a href="<?php echo esc_url(home_url('/communities/' . $slug . '/')); ?>"
                           class="cb-corridor-card cb-corridor-card--community<?php echo $img_url ? ' has-image' : ''; ?>"
                           data-corridor data-cursor="View" <?php echo $cb_float(); ?>>
                            <div class="cb-corridor-card__inner">
                                <?php if ($img_url) : ?>
                                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($c['name'] . ', San Angelo TX'); ?>" class="cb-corridor-community__img" loading="lazy" decoding="async">
                                <?php endif; ?>
                                <div class="cb-corridor-community__overlay">
                                    <h3 class="cb-corridor-h3"><?php echo esc_html($c['name']); ?></h3>
                                    <span class="cb-corridor-go">View Listings</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="cb-corridor-card cb-corridor-card--cta-row" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <a href="<?php echo esc_url(home_url('/communities/')); ?>" class="cb-btn cb-btn--outline">Explore All Communities</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 6 — SELLERS CTA + Property Watch. Camera pans to the
             valuation desk. Vertical scroll shows the home-value form + watch.
             ================================================================ -->
        <section class="cb-corridor-section" data-corridor-section="6" id="corridor-sell" aria-label="Sell your home">
            <div class="cb-corridor-section__inner">
                <div class="cb-corridor-grid cb-corridor-grid--2">
                    <div class="cb-corridor-card cb-corridor-card--cta" data-corridor <?php echo $cb_float(); ?>>
                        <div class="cb-corridor-card__inner">
                            <span class="cb-corridor-eyebrow">For Sellers</span>
                            <h2 class="cb-corridor-h2">What&rsquo;s my home worth today?</h2>
                            <p class="cb-corridor-p">Get a free, no-obligation valuation grounded in live San Angelo market data &mdash; usually within 24 hours.</p>
                            <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Get My Home Value</a>
                        </div>
                    </div>
                    <div class="cb-corridor-card cb-corridor-card--watch" data-corridor <?php echo $cb_float(); ?>>
                        <div class="cb-corridor-card__inner">
                            <h3 class="cb-corridor-h3">Never miss a listing</h3>
                            <p class="cb-corridor-p">Property Watch emails you the moment a home matching your criteria hits the market.</p>
                            <form class="cb-corridor-watch__form" data-cb-watch>
                                <input type="email" class="cb-corridor-watch__input" name="email" placeholder="Enter your email address" aria-label="Email address" required>
                                <button type="submit" class="cb-btn cb-btn--primary">Sign Up</button>
                            </form>
                            <p class="cb-corridor-watch__note">No spam. Unsubscribe anytime.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 7 — TESTIMONIALS + BLOG + BRAND CLOSE. Camera reaches the
             story wall. Vertical scroll shows testimonials, blog posts, and
             the Coldwell brand mark.
             ================================================================ -->
        <section class="cb-corridor-section" data-corridor-section="7" id="corridor-connect" aria-label="Reviews and stories">
            <div class="cb-corridor-section__inner">
                <div class="cb-corridor-card cb-corridor-card--head" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <span class="cb-corridor-eyebrow">Client Stories</span>
                        <h2 class="cb-corridor-h2">What our clients say.</h2>
                        <p class="cb-corridor-p">Real reviews from Coldwell Banker Legacy San Angelo clients &mdash; verified via Testimonial Tree.</p>
                    </div>
                </div>
                <div class="cb-corridor-card cb-corridor-card--frame" data-corridor data-corridor-frame <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <?php echo do_shortcode('[cb_testimonials type="rotator"]'); ?>
                    </div>
                </div>
                <div class="cb-corridor-card cb-corridor-card--cta-row" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <a href="<?php echo esc_url(home_url('/testimonials/')); ?>" class="cb-btn cb-btn--outline">Read All Reviews</a>
                    </div>
                </div>

                <div class="cb-corridor-card cb-corridor-card--head" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <span class="cb-corridor-eyebrow">From Our Blog</span>
                        <h2 class="cb-corridor-h2 cb-corridor-h2--sm">Local insight &amp; market news.</h2>
                    </div>
                </div>
                <div class="cb-corridor-grid cb-corridor-grid--3">
                    <?php
                    $blog_posts = new WP_Query([
                        'post_type'      => 'post',
                        'posts_per_page' => 3,
                        'post_status'    => 'publish',
                    ]);
                    if ($blog_posts->have_posts()) :
                        while ($blog_posts->have_posts()) : $blog_posts->the_post(); ?>
                        <article class="cb-corridor-card cb-corridor-card--blog" data-corridor <?php echo $cb_float(); ?>>
                            <div class="cb-corridor-card__inner">
                                <div class="cb-corridor-blog__image">
                                    <?php if (has_post_thumbnail()) : the_post_thumbnail('cb-blog-thumb');
                                    else : ?><img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-blog.jpg'); ?>" alt="<?php the_title_attribute(); ?>" decoding="async"><?php endif; ?>
                                </div>
                                <div class="cb-corridor-blog__body">
                                    <?php $categories = get_the_category(); if ($categories) : ?>
                                        <span class="cb-corridor-blog__cat"><?php echo esc_html($categories[0]->name); ?></span>
                                    <?php endif; ?>
                                    <h3 class="cb-corridor-h3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    <p class="cb-corridor-p"><?php echo esc_html(get_the_excerpt()); ?></p>
                                    <span class="cb-corridor-blog__meta"><?php echo get_the_date(); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; wp_reset_postdata();
                    else :
                        $placeholders = [
                            ['title' => '12 San Angelo Secrets Only Locals Know', 'cat' => 'Community', 'date' => 'April 10, 2026'],
                            ['title' => 'Spring 2026 San Angelo Market Report', 'cat' => 'Market Update', 'date' => 'April 5, 2026'],
                            ['title' => 'First-Time Home Buyer Guide for West Texas', 'cat' => 'Buying Tips', 'date' => 'March 28, 2026'],
                        ];
                        foreach ($placeholders as $p) : ?>
                        <article class="cb-corridor-card cb-corridor-card--blog" data-corridor <?php echo $cb_float(); ?>>
                            <div class="cb-corridor-card__inner">
                                <div class="cb-corridor-blog__image">
                                    <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-blog.jpg'); ?>" alt="<?php echo esc_attr($p['title']); ?>" decoding="async">
                                </div>
                                <div class="cb-corridor-blog__body">
                                    <span class="cb-corridor-blog__cat"><?php echo esc_html($p['cat']); ?></span>
                                    <h3 class="cb-corridor-h3"><a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php echo esc_html($p['title']); ?></a></h3>
                                    <p class="cb-corridor-p">Discover the latest insights about San Angelo real estate and community living.</p>
                                    <span class="cb-corridor-blog__meta"><?php echo esc_html($p['date']); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach;
                    endif; ?>
                </div>

                <div class="cb-corridor-card cb-corridor-card--mark" data-corridor <?php echo $cb_float(); ?>>
                    <div class="cb-corridor-card__inner">
                        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/logos/monogram-vertical-stacked.svg'); ?>" alt="Coldwell Banker Legacy" class="cb-corridor-mark__logo">
                        <p class="cb-corridor-mark__tag">Live Well With Coldwell&#8480;</p>
                        <p class="cb-corridor-mark__line">At home in San Angelo, Texas.</p>
                    </div>
                </div>
            </div>
        </section>

    </div><!-- /.cb-corridor-scenes -->
</div><!-- /.cb-corridor-stage -->

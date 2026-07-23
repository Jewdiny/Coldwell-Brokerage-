<?php
/**
 * Home "office" scenes partial — Home 4 preview.
 *
 * Combines Home 2 + Home 3: a Three.js WebGL 3D dust-nebula backdrop
 * (assets/js/cb-office/office.js) + custom cursor, behind floating glass
 * info-cards that form from / crumble into that same 3D dust field. Renders the
 * SAME live content as the other previews (MLS, communities, Testimonial Tree,
 * blog, stats). front-page.php, Home 2, and Home 3 are all left UNCHANGED.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

$hero_title    = get_theme_mod('cb_hero_title', 'Find Your Dream Home in San Angelo');
$hero_subtitle = get_theme_mod('cb_hero_subtitle', 'Discover luxury living with Coldwell Banker Legacy. Your trusted partner in San Angelo real estate.');

$cb_office_i = 0;
$cb_float = function ($extra = '') use (&$cb_office_i) {
    $durs = [8.5, 10.0, 9.2, 11.5, 7.8, 12.4, 9.8, 10.8];
    $dels = [0.0, -2.3, -4.1, -1.2, -5.6, -3.0, -6.2, -0.7];
    $ys   = [10, 14, 8, 16, 12, 9, 15, 11];
    $rots = [0.6, -0.8, 0.5, -0.5, 0.9, -0.7, 0.4, -0.6];
    $i = $cb_office_i++;
    return sprintf(
        'style="--fl-dur:%ss;--fl-delay:%ss;--fl-y:%spx;--fl-rot:%sdeg;%s"',
        $durs[$i % count($durs)], $dels[$i % count($dels)], $ys[$i % count($ys)], $rots[$i % count($rots)], $extra
    );
};

$cb_communities = function_exists('cb_get_communities') ? cb_get_communities() : [];
$cb_featured    = ['grape-creek', 'bentwood', 'college-hills', 'christoval', 'wall', 'lake-nasworthy'];

$cb_office_nav = [
    0 => 'Arrival', 1 => 'Start', 2 => 'Listings', 3 => 'Legacy',
    4 => 'Buy', 5 => 'Communities', 6 => 'Sell', 7 => 'Connect',
];
?>

<div class="cb-office-stage" id="cb-office-stage" data-cb-office>

    <!-- WebGL 3D dust-nebula backdrop (revealed only under html.cb-office-on). -->
    <canvas id="cb-office-canvas" aria-hidden="true"></canvas>
    <div class="cb-office-vignette" aria-hidden="true"></div>

    <nav class="cb-office-nav" aria-label="Page sections">
        <?php foreach ($cb_office_nav as $i => $label) : ?>
            <button class="cb-office-nav__dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                    data-office-to="<?php echo esc_attr($i); ?>" type="button">
                <span class="cb-office-nav__label"><?php echo esc_html($label); ?></span>
            </button>
        <?php endforeach; ?>
    </nav>

    <div class="cb-office-scenes">

        <!-- SECTION 0 — ARRIVAL -->
        <section class="cb-office-section cb-office-section--hero" data-office-section="0" id="office-arrival" aria-label="Welcome">
            <div class="cb-office-section__inner">
                <div class="cb-office-card cb-office-card--chip" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <span class="cb-office-eyebrow">Live Well With Coldwell&#8480;</span>
                        <span class="cb-office-coords">31.46&deg;&nbsp;N&nbsp; &middot; &nbsp;100.44&deg;&nbsp;W &middot; San Angelo, Texas</span>
                    </div>
                </div>
                <div class="cb-office-card cb-office-card--title" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner"><h1 class="cb-office-h1"><?php echo esc_html($hero_title); ?></h1></div>
                </div>
                <div class="cb-office-card cb-office-card--lead" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner"><p class="cb-office-sub"><?php echo esc_html($hero_subtitle); ?></p></div>
                </div>
                <div class="cb-office-card cb-office-card--ghost" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <span class="cb-office-cue" aria-hidden="true">
                            Scroll &mdash; step inside the Legacy office
                            <span class="cb-office-cue__line"><span></span></span>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 1 — START / QUICK ACTIONS -->
        <section class="cb-office-section" data-office-section="1" id="office-start" aria-label="How can we help">
            <div class="cb-office-section__inner">
                <div class="cb-office-card cb-office-card--head" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <span class="cb-office-eyebrow">Welcome to the Concho Valley</span>
                        <h2 class="cb-office-h2">At home in San&nbsp;Angelo.</h2>
                        <p class="cb-office-p">Whether you&rsquo;re buying, selling, or just dreaming &mdash; start here. Four ways we help you move forward.</p>
                    </div>
                </div>
                <div class="cb-office-grid cb-office-grid--4">
                    <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-office-card cb-office-card--action" data-office data-cursor="Explore" <?php echo $cb_float(); ?>>
                        <div class="cb-office-card__inner">
                            <div class="cb-office-icon"><?php echo cb_get_svg_icon('home'); ?></div>
                            <h3 class="cb-office-h3">Find a Home</h3>
                            <p class="cb-office-p">Browse available properties across San Angelo and the Concho Valley.</p>
                            <span class="cb-office-go">Explore <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                    <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-office-card cb-office-card--action" data-office data-cursor="Value" <?php echo $cb_float(); ?>>
                        <div class="cb-office-card__inner">
                            <div class="cb-office-icon"><?php echo cb_get_svg_icon('sell'); ?></div>
                            <h3 class="cb-office-h3">Sell Your Home</h3>
                            <p class="cb-office-p">Get a free home valuation and connect with an expert agent.</p>
                            <span class="cb-office-go">Get value <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                    <a href="<?php echo esc_url(home_url('/our-team/')); ?>" class="cb-office-card cb-office-card--action" data-office data-cursor="Meet" <?php echo $cb_float(); ?>>
                        <div class="cb-office-card__inner">
                            <div class="cb-office-icon"><?php echo cb_get_svg_icon('team'); ?></div>
                            <h3 class="cb-office-h3">Meet Our Team</h3>
                            <p class="cb-office-p">Connect with experienced agents who know San Angelo inside and out.</p>
                            <span class="cb-office-go">Meet us <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                    <a href="<?php echo esc_url(home_url('/office/')); ?>" class="cb-office-card cb-office-card--action" data-office data-cursor="Visit" <?php echo $cb_float(); ?>>
                        <div class="cb-office-card__inner">
                            <div class="cb-office-icon"><?php echo cb_get_svg_icon('office'); ?></div>
                            <h3 class="cb-office-h3">Visit Our Office</h3>
                            <p class="cb-office-p">Stop by our office on Knickerbocker Road. We&rsquo;d love to meet you.</p>
                            <span class="cb-office-go">Directions <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- SECTION 2 — FEATURED LISTINGS -->
        <section class="cb-office-section" data-office-section="2" id="office-listings" aria-label="Featured listings">
            <div class="cb-office-section__inner">
                <div class="cb-office-card cb-office-card--head" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <span class="cb-office-eyebrow">Featured Properties</span>
                        <h2 class="cb-office-h2">The latest on the market.</h2>
                        <p class="cb-office-p">A hand-picked look at premier San Angelo homes, updated live from the MLS.</p>
                    </div>
                </div>
                <div class="cb-office-card cb-office-card--frame" data-office data-office-frame <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <?php echo do_shortcode('[cb_listings filter="featured" count="6" columns="3"]'); ?>
                    </div>
                </div>
                <div class="cb-office-card cb-office-card--cta-row" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary">View All Properties</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 3 — LEGACY / STATS -->
        <section class="cb-office-section" data-office-section="3" id="office-legacy" aria-label="Our track record">
            <div class="cb-office-section__inner">
                <div class="cb-office-card cb-office-card--head" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <span class="cb-office-eyebrow">Serving the Concho Valley for over 35 years</span>
                        <h2 class="cb-office-h2">A legacy of results in the Concho&nbsp;Valley.</h2>
                    </div>
                </div>
                <div class="cb-office-grid cb-office-grid--4">
                    <div class="cb-office-card cb-office-card--stat" data-office <?php echo $cb_float(); ?>>
                        <div class="cb-office-card__inner">
                            <div class="cb-office-stat__num" data-count="<?php echo esc_attr(get_theme_mod('cb_homes_sold', '1200')); ?>">0</div>
                            <div class="cb-office-stat__label">Homes Sold</div>
                        </div>
                    </div>
                    <div class="cb-office-card cb-office-card--stat" data-office <?php echo $cb_float(); ?>>
                        <div class="cb-office-card__inner">
                            <div class="cb-office-stat__num" data-count="30">0</div>
                            <div class="cb-office-stat__label">Expert Agents</div>
                        </div>
                    </div>
                    <div class="cb-office-card cb-office-card--stat" data-office <?php echo $cb_float(); ?>>
                        <div class="cb-office-card__inner">
                            <div class="cb-office-stat__num" data-count="<?php echo esc_attr(get_theme_mod('cb_years_serving', '35')); ?>">0</div>
                            <div class="cb-office-stat__label">Years Serving San Angelo</div>
                        </div>
                    </div>
                    <div class="cb-office-card cb-office-card--stat" data-office <?php echo $cb_float(); ?>>
                        <div class="cb-office-card__inner">
                            <div class="cb-office-stat__num" data-count="20">0</div>
                            <div class="cb-office-stat__label">Communities Served</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 4 — BUYERS CTA -->
        <section class="cb-office-section cb-office-section--center" data-office-section="4" id="office-buy" aria-label="Browse homes">
            <div class="cb-office-section__inner">
                <div class="cb-office-card cb-office-card--cta" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <span class="cb-office-eyebrow">For Buyers</span>
                        <h2 class="cb-office-h2">Open the door to San&nbsp;Angelo living.</h2>
                        <p class="cb-office-p">Search every active listing in the Concho Valley &mdash; filtered, mapped, and updated in real time.</p>
                        <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">
                            <?php echo cb_get_svg_icon('search'); ?> Browse San Angelo Homes
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 5 — COMMUNITIES -->
        <section class="cb-office-section" data-office-section="5" id="office-communities" aria-label="Featured communities">
            <div class="cb-office-section__inner">
                <div class="cb-office-card cb-office-card--head" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <span class="cb-office-eyebrow">Explore the Area</span>
                        <h2 class="cb-office-h2">From the river to the ranch&nbsp;land.</h2>
                        <p class="cb-office-p">From downtown San Angelo to the scenic shores of Lake Nasworthy, find the neighborhood that fits your life.</p>
                    </div>
                </div>
                <div class="cb-office-grid cb-office-grid--3">
                    <?php foreach ($cb_featured as $slug) :
                        if (!isset($cb_communities[$slug])) { continue; }
                        $c = $cb_communities[$slug];
                        $img_url = function_exists('cb_community_image_url') ? cb_community_image_url($c) : '';
                    ?>
                        <a href="<?php echo esc_url(home_url('/communities/' . $slug . '/')); ?>"
                           class="cb-office-card cb-office-card--community<?php echo $img_url ? ' has-image' : ''; ?>"
                           data-office data-cursor="View" <?php echo $cb_float(); ?>>
                            <div class="cb-office-card__inner">
                                <?php if ($img_url) : ?>
                                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($c['name'] . ', San Angelo TX'); ?>" class="cb-office-community__img" loading="lazy" decoding="async">
                                <?php endif; ?>
                                <div class="cb-office-community__overlay">
                                    <h3 class="cb-office-h3"><?php echo esc_html($c['name']); ?></h3>
                                    <span class="cb-office-go">View Listings</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="cb-office-card cb-office-card--cta-row" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <a href="<?php echo esc_url(home_url('/communities/')); ?>" class="cb-btn cb-btn--outline">Explore All Communities</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 6 — SELLERS CTA + PROPERTY WATCH -->
        <section class="cb-office-section" data-office-section="6" id="office-sell" aria-label="Sell your home">
            <div class="cb-office-section__inner">
                <div class="cb-office-grid cb-office-grid--2">
                    <div class="cb-office-card cb-office-card--cta" data-office <?php echo $cb_float(); ?>>
                        <div class="cb-office-card__inner">
                            <span class="cb-office-eyebrow">For Sellers</span>
                            <h2 class="cb-office-h2">What&rsquo;s my home worth today?</h2>
                            <p class="cb-office-p">Get a free, no-obligation valuation grounded in live San Angelo market data &mdash; usually within 24 hours.</p>
                            <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Get My Home Value</a>
                        </div>
                    </div>
                    <div class="cb-office-card cb-office-card--watch" data-office <?php echo $cb_float(); ?>>
                        <div class="cb-office-card__inner">
                            <h3 class="cb-office-h3">Never miss a listing</h3>
                            <p class="cb-office-p">Property Watch emails you the moment a home matching your criteria hits the market.</p>
                            <form class="cb-office-watch__form" data-cb-watch>
                                <input type="email" class="cb-office-watch__input" name="email" placeholder="Enter your email address" aria-label="Email address" required>
                                <button type="submit" class="cb-btn cb-btn--primary">Sign Up</button>
                            </form>
                            <p class="cb-office-watch__note">No spam. Unsubscribe anytime.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 7 — TESTIMONIALS + BLOG + BRAND CLOSE -->
        <section class="cb-office-section" data-office-section="7" id="office-connect" aria-label="Reviews and stories">
            <div class="cb-office-section__inner">
                <div class="cb-office-card cb-office-card--head" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <span class="cb-office-eyebrow">Client Stories</span>
                        <h2 class="cb-office-h2">What our clients say.</h2>
                        <p class="cb-office-p">Real reviews from Coldwell Banker Legacy San Angelo clients &mdash; verified via Testimonial Tree.</p>
                    </div>
                </div>
                <div class="cb-office-card cb-office-card--frame" data-office data-office-frame <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <?php echo do_shortcode('[cb_testimonials type="rotator"]'); ?>
                    </div>
                </div>
                <div class="cb-office-card cb-office-card--cta-row" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <a href="<?php echo esc_url(home_url('/testimonials/')); ?>" class="cb-btn cb-btn--outline">Read All Reviews</a>
                    </div>
                </div>

                <div class="cb-office-card cb-office-card--head" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <span class="cb-office-eyebrow">From Our Blog</span>
                        <h2 class="cb-office-h2 cb-office-h2--sm">Local insight &amp; market news.</h2>
                    </div>
                </div>
                <div class="cb-office-grid cb-office-grid--3">
                    <?php
                    $blog_posts = new WP_Query([
                        'post_type'      => 'post',
                        'posts_per_page' => 3,
                        'post_status'    => 'publish',
                    ]);
                    if ($blog_posts->have_posts()) :
                        while ($blog_posts->have_posts()) : $blog_posts->the_post(); ?>
                        <article class="cb-office-card cb-office-card--blog" data-office <?php echo $cb_float(); ?>>
                            <div class="cb-office-card__inner">
                                <div class="cb-office-blog__image">
                                    <?php if (has_post_thumbnail()) : the_post_thumbnail('cb-blog-thumb');
                                    else : ?><img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-blog.jpg'); ?>" alt="<?php the_title_attribute(); ?>" decoding="async"><?php endif; ?>
                                </div>
                                <div class="cb-office-blog__body">
                                    <?php $categories = get_the_category(); if ($categories) : ?>
                                        <span class="cb-office-blog__cat"><?php echo esc_html($categories[0]->name); ?></span>
                                    <?php endif; ?>
                                    <h3 class="cb-office-h3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    <p class="cb-office-p"><?php echo esc_html(get_the_excerpt()); ?></p>
                                    <span class="cb-office-blog__meta"><?php echo get_the_date(); ?></span>
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
                        <article class="cb-office-card cb-office-card--blog" data-office <?php echo $cb_float(); ?>>
                            <div class="cb-office-card__inner">
                                <div class="cb-office-blog__image">
                                    <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-blog.jpg'); ?>" alt="<?php echo esc_attr($p['title']); ?>" decoding="async">
                                </div>
                                <div class="cb-office-blog__body">
                                    <span class="cb-office-blog__cat"><?php echo esc_html($p['cat']); ?></span>
                                    <h3 class="cb-office-h3"><a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php echo esc_html($p['title']); ?></a></h3>
                                    <p class="cb-office-p">Discover the latest insights about San Angelo real estate and community living.</p>
                                    <span class="cb-office-blog__meta"><?php echo esc_html($p['date']); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach;
                    endif; ?>
                </div>

                <div class="cb-office-card cb-office-card--mark" data-office <?php echo $cb_float(); ?>>
                    <div class="cb-office-card__inner">
                        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/logos/monogram-vertical-stacked.svg'); ?>" alt="Coldwell Banker Legacy" class="cb-office-mark__logo">
                        <p class="cb-office-mark__tag">Live Well With Coldwell&#8480;</p>
                        <p class="cb-office-mark__line">At home in San Angelo, Texas.</p>
                    </div>
                </div>
            </div>
        </section>

    </div><!-- /.cb-office-scenes -->
</div><!-- /.cb-office-stage -->

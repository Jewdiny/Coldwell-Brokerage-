<?php
/**
 * Home "dust" scenes partial — the floating-card / crumble-to-dust concept.
 *
 * A sibling to template-parts/home-scenes.php (the cinematic WebGL stage). This
 * lays out the SAME server-rendered homepage content (live MLS listings,
 * communities, testimonials, blog, stats) but as floating glass cards on a deep
 * blue animated canvas. assets/js/cb-dust/dust.js drives the canvas dust field
 * and the per-card "form from dust" / "crumble into dust" transitions; without
 * JS (or on mobile / reduced-motion) every card simply renders static and legible.
 *
 * front-page.php and Home 2 are intentionally left UNCHANGED.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

$hero_title    = get_theme_mod('cb_hero_title', 'Find Your Dream Home in San Angelo');
$hero_subtitle = get_theme_mod('cb_hero_subtitle', 'Discover luxury living with Coldwell Banker Legacy. Your trusted partner in San Angelo real estate.');

/**
 * Per-card float variety. Returns an inline style string seeding the gentle
 * idle drift (duration / delay / travel / rotation) so no two cards bob in sync.
 * Deterministic (index-based) so server output is stable / cacheable.
 */
$cb_dust_i = 0;
$cb_float  = function ($extra = '') use (&$cb_dust_i) {
    $durs = [8.5, 10.0, 9.2, 11.5, 7.8, 12.4, 9.8, 10.8];
    $dels = [0.0, -2.3, -4.1, -1.2, -5.6, -3.0, -6.2, -0.7];
    $ys   = [10, 14, 8, 16, 12, 9, 15, 11];
    $rots = [0.6, -0.8, 0.5, -0.5, 0.9, -0.7, 0.4, -0.6];
    $i    = $cb_dust_i++;
    $dur  = $durs[$i % count($durs)];
    $del  = $dels[$i % count($dels)];
    $y    = $ys[$i % count($ys)];
    $rot  = $rots[$i % count($rots)];
    // --d = stagger index for the "form from dust" entrance order within a section.
    return sprintf(
        'style="--fl-dur:%ss;--fl-delay:%ss;--fl-y:%spx;--fl-rot:%sdeg;--d:%d;%s"',
        $dur, $del, $y, $rot, $i, $extra
    );
};

// Featured communities (same set as the cinematic homepage).
$cb_communities = function_exists('cb_get_communities') ? cb_get_communities() : [];
$cb_featured    = ['grape-creek', 'bentwood', 'college-hills', 'christoval', 'wall', 'lake-nasworthy'];

// Section labels for the side progress rail.
$cb_dust_nav = [
    0 => 'Arrival',
    1 => 'Start',
    2 => 'Listings',
    3 => 'Legacy',
    4 => 'Buy',
    5 => 'Communities',
    6 => 'Sell',
    7 => 'Connect',
];
?>

<div class="cb-dust-stage" id="cb-dust-stage" data-cb-dust>

    <!-- ============================================================
         BLUE DUST CANVAS — full-viewport fixed 2D particle field.
         Positioned + revealed only under html.cb-dust-on (cb-dust.css);
         dust.js adds that class only after the canvas context starts.
         Until then it is inert and every card renders static & legible.
         ============================================================ -->
    <canvas id="cb-dust-canvas" aria-hidden="true"></canvas>

    <!-- Section progress rail (decorative; click to jump). -->
    <nav class="cb-dust-nav" aria-label="Page sections">
        <?php foreach ($cb_dust_nav as $i => $label) : ?>
            <button class="cb-dust-nav__dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                    data-dust-to="<?php echo esc_attr($i); ?>" type="button">
                <span class="cb-dust-nav__label"><?php echo esc_html($label); ?></span>
            </button>
        <?php endforeach; ?>
    </nav>

    <div class="cb-dust-scenes">

        <!-- ================================================================
             SECTION 0 — ARRIVAL (hero, as floating chips)
             ================================================================ -->
        <section class="cb-dust-section cb-dust-section--hero" data-dust-section="0" id="dust-arrival" aria-label="Welcome">
            <div class="cb-dust-section__inner">
                <div class="cb-dust-card cb-dust-card--chip" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <span class="cb-dust-eyebrow">Live Well With Coldwell&#8480;</span>
                        <span class="cb-dust-coords">31.46&deg;&nbsp;N&nbsp; &middot; &nbsp;100.44&deg;&nbsp;W &middot; San Angelo, Texas</span>
                    </div>
                </div>

                <div class="cb-dust-card cb-dust-card--title" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <h1 class="cb-dust-h1"><?php echo esc_html($hero_title); ?></h1>
                    </div>
                </div>

                <div class="cb-dust-card cb-dust-card--lead" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <p class="cb-dust-sub"><?php echo esc_html($hero_subtitle); ?></p>
                    </div>
                </div>

                <div class="cb-dust-card cb-dust-card--ghost" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <span class="cb-dust-cue" aria-hidden="true">
                            Scroll &mdash; watch it turn to dust
                            <span class="cb-dust-cue__line"><span></span></span>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 1 — START / QUICK ACTIONS
             ================================================================ -->
        <section class="cb-dust-section" data-dust-section="1" id="dust-start" aria-label="How can we help">
            <div class="cb-dust-section__inner">
                <div class="cb-dust-card cb-dust-card--head" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <span class="cb-dust-eyebrow">Welcome to the Concho Valley</span>
                        <h2 class="cb-dust-h2">At home in San&nbsp;Angelo.</h2>
                        <p class="cb-dust-p">Whether you&rsquo;re buying, selling, or just dreaming &mdash; start here. Four ways we help you move forward.</p>
                    </div>
                </div>

                <div class="cb-dust-grid cb-dust-grid--4">
                    <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-dust-card cb-dust-card--action" data-dust <?php echo $cb_float(); ?>>
                        <div class="cb-dust-card__inner">
                            <div class="cb-dust-icon"><?php echo cb_get_svg_icon('home'); ?></div>
                            <h3 class="cb-dust-h3">Find a Home</h3>
                            <p class="cb-dust-p">Browse available properties across San Angelo and the Concho Valley.</p>
                            <span class="cb-dust-go">Explore <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                    <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-dust-card cb-dust-card--action" data-dust <?php echo $cb_float(); ?>>
                        <div class="cb-dust-card__inner">
                            <div class="cb-dust-icon"><?php echo cb_get_svg_icon('sell'); ?></div>
                            <h3 class="cb-dust-h3">Sell Your Home</h3>
                            <p class="cb-dust-p">Get a free home valuation and connect with an expert agent.</p>
                            <span class="cb-dust-go">Get value <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                    <a href="<?php echo esc_url(home_url('/our-team/')); ?>" class="cb-dust-card cb-dust-card--action" data-dust <?php echo $cb_float(); ?>>
                        <div class="cb-dust-card__inner">
                            <div class="cb-dust-icon"><?php echo cb_get_svg_icon('team'); ?></div>
                            <h3 class="cb-dust-h3">Meet Our Team</h3>
                            <p class="cb-dust-p">Connect with experienced agents who know San Angelo inside and out.</p>
                            <span class="cb-dust-go">Meet us <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                    <a href="<?php echo esc_url(home_url('/office/')); ?>" class="cb-dust-card cb-dust-card--action" data-dust <?php echo $cb_float(); ?>>
                        <div class="cb-dust-card__inner">
                            <div class="cb-dust-icon"><?php echo cb_get_svg_icon('office'); ?></div>
                            <h3 class="cb-dust-h3">Visit Our Office</h3>
                            <p class="cb-dust-p">Stop by our office on Knickerbocker Road. We&rsquo;d love to meet you.</p>
                            <span class="cb-dust-go">Directions <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 2 — FEATURED LISTINGS (live MLS)
             ================================================================ -->
        <section class="cb-dust-section" data-dust-section="2" id="dust-listings" aria-label="Featured listings">
            <div class="cb-dust-section__inner">
                <div class="cb-dust-card cb-dust-card--head" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <span class="cb-dust-eyebrow">Featured Properties</span>
                        <h2 class="cb-dust-h2">The latest on the market.</h2>
                        <p class="cb-dust-p">A hand-picked look at premier San Angelo homes, updated live from the MLS.</p>
                    </div>
                </div>

                <div class="cb-dust-card cb-dust-card--frame" data-dust data-dust-frame <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <?php echo do_shortcode('[cb_listings filter="featured" count="6" columns="3"]'); ?>
                    </div>
                </div>

                <div class="cb-dust-card cb-dust-card--cta-row" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary">View All Properties</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 3 — LEGACY / STATS
             ================================================================ -->
        <section class="cb-dust-section" data-dust-section="3" id="dust-legacy" aria-label="Our track record">
            <div class="cb-dust-section__inner">
                <div class="cb-dust-card cb-dust-card--head" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <span class="cb-dust-eyebrow">Since 2000</span>
                        <h2 class="cb-dust-h2">A legacy of results in the Concho&nbsp;Valley.</h2>
                    </div>
                </div>

                <div class="cb-dust-grid cb-dust-grid--4">
                    <div class="cb-dust-card cb-dust-card--stat" data-dust <?php echo $cb_float(); ?>>
                        <div class="cb-dust-card__inner">
                            <div class="cb-dust-stat__num" data-count="<?php echo esc_attr(get_theme_mod('cb_homes_sold', '1200')); ?>">0</div>
                            <div class="cb-dust-stat__label">Homes Sold</div>
                        </div>
                    </div>
                    <div class="cb-dust-card cb-dust-card--stat" data-dust <?php echo $cb_float(); ?>>
                        <div class="cb-dust-card__inner">
                            <div class="cb-dust-stat__num" data-count="30">0</div>
                            <div class="cb-dust-stat__label">Expert Agents</div>
                        </div>
                    </div>
                    <div class="cb-dust-card cb-dust-card--stat" data-dust <?php echo $cb_float(); ?>>
                        <div class="cb-dust-card__inner">
                            <div class="cb-dust-stat__num" data-count="<?php echo esc_attr(get_theme_mod('cb_years_serving', '25')); ?>">0</div>
                            <div class="cb-dust-stat__label">Years Serving San Angelo</div>
                        </div>
                    </div>
                    <div class="cb-dust-card cb-dust-card--stat" data-dust <?php echo $cb_float(); ?>>
                        <div class="cb-dust-card__inner">
                            <div class="cb-dust-stat__num" data-count="20">0</div>
                            <div class="cb-dust-stat__label">Communities Served</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 4 — BUYERS CTA
             ================================================================ -->
        <section class="cb-dust-section cb-dust-section--center" data-dust-section="4" id="dust-buy" aria-label="Browse homes">
            <div class="cb-dust-section__inner">
                <div class="cb-dust-card cb-dust-card--cta" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <span class="cb-dust-eyebrow">For Buyers</span>
                        <h2 class="cb-dust-h2">Open the door to San&nbsp;Angelo living.</h2>
                        <p class="cb-dust-p">Search every active listing in the Concho Valley &mdash; filtered, mapped, and updated in real time.</p>
                        <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">
                            <?php echo cb_get_svg_icon('search'); ?> Browse San Angelo Homes
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 5 — COMMUNITIES
             ================================================================ -->
        <section class="cb-dust-section" data-dust-section="5" id="dust-communities" aria-label="Featured communities">
            <div class="cb-dust-section__inner">
                <div class="cb-dust-card cb-dust-card--head" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <span class="cb-dust-eyebrow">Explore the Area</span>
                        <h2 class="cb-dust-h2">From the river to the ranch&nbsp;land.</h2>
                        <p class="cb-dust-p">From downtown San Angelo to the scenic shores of Lake Nasworthy, find the neighborhood that fits your life.</p>
                    </div>
                </div>

                <div class="cb-dust-grid cb-dust-grid--3">
                    <?php foreach ($cb_featured as $slug) :
                        if (!isset($cb_communities[$slug])) { continue; }
                        $c       = $cb_communities[$slug];
                        $img_url = function_exists('cb_community_image_url') ? cb_community_image_url($c) : '';
                    ?>
                        <a href="<?php echo esc_url(home_url('/communities/' . $slug . '/')); ?>"
                           class="cb-dust-card cb-dust-card--community<?php echo $img_url ? ' has-image' : ''; ?>"
                           data-dust <?php echo $cb_float(); ?>>
                            <div class="cb-dust-card__inner">
                                <?php if ($img_url) : ?>
                                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($c['name'] . ', San Angelo TX'); ?>" class="cb-dust-community__img" loading="lazy" decoding="async">
                                <?php endif; ?>
                                <div class="cb-dust-community__overlay">
                                    <h3 class="cb-dust-h3"><?php echo esc_html($c['name']); ?></h3>
                                    <span class="cb-dust-go">View Listings</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="cb-dust-card cb-dust-card--cta-row" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <a href="<?php echo esc_url(home_url('/communities/')); ?>" class="cb-btn cb-btn--outline">Explore All Communities</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 6 — SELLERS CTA + PROPERTY WATCH
             ================================================================ -->
        <section class="cb-dust-section" data-dust-section="6" id="dust-sell" aria-label="Sell your home">
            <div class="cb-dust-section__inner">
                <div class="cb-dust-grid cb-dust-grid--2">
                    <div class="cb-dust-card cb-dust-card--cta" data-dust <?php echo $cb_float(); ?>>
                        <div class="cb-dust-card__inner">
                            <span class="cb-dust-eyebrow">For Sellers</span>
                            <h2 class="cb-dust-h2">What&rsquo;s my home worth today?</h2>
                            <p class="cb-dust-p">Get a free, no-obligation valuation grounded in live San Angelo market data &mdash; usually within 24 hours.</p>
                            <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Get My Home Value</a>
                        </div>
                    </div>

                    <div class="cb-dust-card cb-dust-card--watch" data-dust <?php echo $cb_float(); ?>>
                        <div class="cb-dust-card__inner">
                            <h3 class="cb-dust-h3">Never miss a listing</h3>
                            <p class="cb-dust-p">Property Watch emails you the moment a home matching your criteria hits the market.</p>
                            <form class="cb-dust-watch__form" data-cb-watch>
                                <input type="email" class="cb-dust-watch__input" name="email" placeholder="Enter your email address" aria-label="Email address" required>
                                <button type="submit" class="cb-btn cb-btn--primary">Sign Up</button>
                            </form>
                            <p class="cb-dust-watch__note">No spam. Unsubscribe anytime.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SECTION 7 — TESTIMONIALS + BLOG + BRAND CLOSE
             ================================================================ -->
        <section class="cb-dust-section" data-dust-section="7" id="dust-connect" aria-label="Reviews and stories">
            <div class="cb-dust-section__inner">
                <div class="cb-dust-card cb-dust-card--head" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <span class="cb-dust-eyebrow">Client Stories</span>
                        <h2 class="cb-dust-h2">What our clients say.</h2>
                        <p class="cb-dust-p">Real reviews from Coldwell Banker Legacy San Angelo clients &mdash; verified via Testimonial Tree.</p>
                    </div>
                </div>

                <div class="cb-dust-card cb-dust-card--frame" data-dust data-dust-frame <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <?php echo do_shortcode('[cb_testimonials type="rotator"]'); ?>
                    </div>
                </div>

                <div class="cb-dust-card cb-dust-card--cta-row" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <a href="<?php echo esc_url(home_url('/testimonials/')); ?>" class="cb-btn cb-btn--outline">Read All Reviews</a>
                    </div>
                </div>

                <div class="cb-dust-card cb-dust-card--head" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <span class="cb-dust-eyebrow">From Our Blog</span>
                        <h2 class="cb-dust-h2 cb-dust-h2--sm">Local insight &amp; market news.</h2>
                    </div>
                </div>

                <div class="cb-dust-grid cb-dust-grid--3">
                    <?php
                    $blog_posts = new WP_Query([
                        'post_type'      => 'post',
                        'posts_per_page' => 3,
                        'post_status'    => 'publish',
                    ]);

                    if ($blog_posts->have_posts()) :
                        while ($blog_posts->have_posts()) : $blog_posts->the_post();
                    ?>
                        <article class="cb-dust-card cb-dust-card--blog" data-dust <?php echo $cb_float(); ?>>
                            <div class="cb-dust-card__inner">
                                <div class="cb-dust-blog__image">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('cb-blog-thumb'); ?>
                                    <?php else : ?>
                                        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-blog.jpg'); ?>" alt="<?php the_title_attribute(); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="cb-dust-blog__body">
                                    <?php $categories = get_the_category();
                                    if ($categories) : ?>
                                        <span class="cb-dust-blog__cat"><?php echo esc_html($categories[0]->name); ?></span>
                                    <?php endif; ?>
                                    <h3 class="cb-dust-h3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    <p class="cb-dust-p"><?php echo esc_html(get_the_excerpt()); ?></p>
                                    <span class="cb-dust-blog__meta"><?php echo get_the_date(); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        $placeholders = [
                            ['title' => '12 San Angelo Secrets Only Locals Know', 'cat' => 'Community', 'date' => 'April 10, 2026'],
                            ['title' => 'Spring 2026 San Angelo Market Report', 'cat' => 'Market Update', 'date' => 'April 5, 2026'],
                            ['title' => 'First-Time Home Buyer Guide for West Texas', 'cat' => 'Buying Tips', 'date' => 'March 28, 2026'],
                        ];
                        foreach ($placeholders as $p) :
                    ?>
                        <article class="cb-dust-card cb-dust-card--blog" data-dust <?php echo $cb_float(); ?>>
                            <div class="cb-dust-card__inner">
                                <div class="cb-dust-blog__image">
                                    <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-blog.jpg'); ?>" alt="<?php echo esc_attr($p['title']); ?>">
                                </div>
                                <div class="cb-dust-blog__body">
                                    <span class="cb-dust-blog__cat"><?php echo esc_html($p['cat']); ?></span>
                                    <h3 class="cb-dust-h3"><a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php echo esc_html($p['title']); ?></a></h3>
                                    <p class="cb-dust-p">Discover the latest insights about San Angelo real estate and community living.</p>
                                    <span class="cb-dust-blog__meta"><?php echo esc_html($p['date']); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach;
                    endif; ?>
                </div>

                <div class="cb-dust-card cb-dust-card--mark" data-dust <?php echo $cb_float(); ?>>
                    <div class="cb-dust-card__inner">
                        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/logos/monogram-vertical-stacked.svg'); ?>" alt="Coldwell Banker Legacy" class="cb-dust-mark__logo">
                        <p class="cb-dust-mark__tag">Live Well With Coldwell&#8480;</p>
                        <p class="cb-dust-mark__line">At home in San Angelo, Texas.</p>
                    </div>
                </div>
            </div>
        </section>

    </div><!-- /.cb-dust-scenes -->
</div><!-- /.cb-dust-stage -->

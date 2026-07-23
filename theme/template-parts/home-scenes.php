<?php
/**
 * Home scenes partial — the cinematic 8-scene scroll stage.
 *
 * This is a faithful mirror of the scene markup in front-page.php, extracted so
 * the WebGL preview page (templates/template-home2-webgl.php) can reuse the EXACT
 * same server-rendered content (live MLS listings, communities, testimonials,
 * blog, stats) without duplicating it by hand inside the template.
 *
 * front-page.php is intentionally left UNCHANGED for now (the live homepage must
 * stay safe). When the WebGL homepage is approved, front-page.php can be switched
 * to `get_template_part('template-parts/home-scenes')` in a one-line change.
 *
 * Optional WebGL canvas: set $GLOBALS['cb_home_webgl'] = true; before including
 * this partial to emit <canvas id="cb-webgl"> as the first child of the stage.
 * cb-webgl.css only reveals/positions it under html.cb-webgl-on, so it is inert
 * unless main.js successfully starts the engine.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

$cb_home_webgl = !empty($GLOBALS['cb_home_webgl']);

$hero_title    = get_theme_mod('cb_hero_title', 'Find Your Dream Home in San Angelo');
$hero_subtitle = get_theme_mod('cb_hero_subtitle', 'Discover luxury living with Coldwell Banker Legacy. Your trusted partner in San Angelo real estate.');
$hero_video    = get_theme_mod('cb_hero_video', '');
$hero_image_id = get_theme_mod('cb_hero_image', '');
$hero_poster   = $hero_image_id ? wp_get_attachment_image_url($hero_image_id, 'full') : '';

/**
 * Scene background photo hook. Drop a file named scroll/<name>.(webp|jpg|png)
 * into the theme and it is layered over that scene's gradient automatically.
 */
$cb_scroll_dir = get_stylesheet_directory() . '/assets/images/scroll/';
$cb_scroll_uri = CB_THEME_URI . '/assets/images/scroll/';
$cb_scene_photo = function ($name) use ($cb_scroll_dir, $cb_scroll_uri) {
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
        if (file_exists($cb_scroll_dir . $name . '.' . $ext)) {
            return $cb_scroll_uri . $name . '.' . $ext;
        }
    }
    return '';
};

/**
 * Scene background VIDEO hook. Drop scroll/<name>.(mp4|webm) into the theme and
 * that scene plays live footage behind its content. A matching photo is the poster.
 */
$cb_scene_video = function ($name) use ($cb_scroll_dir, $cb_scroll_uri) {
    foreach (['mp4', 'webm'] as $ext) {
        if (file_exists($cb_scroll_dir . $name . '.' . $ext)) {
            return $cb_scroll_uri . $name . '.' . $ext;
        }
    }
    return '';
};

// Background plates, one per scene index (gradient atmosphere defined in CSS).
$cb_bg_plates = [
    0 => 'arrival',
    1 => 'welcome',
    2 => 'listings',
    3 => 'legacy',
    4 => 'door',
    5 => 'communities',
    6 => 'value',
    7 => 'close',
];

// Scene labels for the side progress rail.
$cb_scene_nav = [
    0 => 'Arrival',
    1 => 'Welcome',
    2 => 'Listings',
    3 => 'Legacy',
    4 => 'Buy',
    5 => 'Communities',
    6 => 'Sell',
    7 => 'Connect',
];
?>

<div class="cb-scroll-stage" id="cb-scroll-stage" data-cb-scroll>

    <?php if ($cb_home_webgl) : ?>
    <!-- ============================================================
         WEBGL BACKDROP — Three.js cinematic canvas (Version B).
         Positioned + revealed only under html.cb-webgl-on (cb-webgl.css);
         main.js adds that class only when the engine truly starts. Until
         then this <canvas> is inert and the DOM fallback below is shown.
         ============================================================ -->
    <canvas id="cb-webgl" aria-hidden="true"></canvas>
    <?php endif; ?>

    <!-- ============================================================
         FIXED BACKGROUND CANVAS — gradient atmospheres + photo hooks.
         Only displayed in cinematic mode; home.js crossfades layers.
         Under html.cb-webgl-on this is hidden and the WebGL canvas shows.
         ============================================================ -->
    <div class="cb-stage-bg" aria-hidden="true">
        <?php foreach ($cb_bg_plates as $i => $slug) :
            $base      = sprintf('%02d-%s', $i + 1, $slug);
            $video     = $cb_scene_video($base);
            $photo     = $cb_scene_photo($base);
            $has_media = $video || $photo;
        ?>
            <div class="cb-stage-bg__layer cb-stage-bg__layer--<?php echo esc_attr($slug); ?><?php echo $i === 0 ? ' is-active' : ''; ?>"
                 data-bg="<?php echo esc_attr($i); ?>"
                 <?php echo (!$video && $photo) ? 'style="--scene-photo:url(\'' . esc_url($photo) . '\');"' : ''; ?>>
                <?php if ($video) : ?>
                    <video class="cb-stage-bg__video" muted loop playsinline preload="metadata"<?php echo $photo ? ' poster="' . esc_url($photo) . '"' : ''; ?>>
                        <source src="<?php echo esc_url($video); ?>" type="<?php echo (substr($video, -5) === '.webm') ? 'video/webm' : 'video/mp4'; ?>">
                    </video>
                <?php elseif ($photo) : ?>
                    <span class="cb-stage-bg__photo"></span>
                <?php endif; ?>
                <?php if ($has_media) : ?><span class="cb-stage-bg__tint"></span><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <span class="cb-stage-bg__grain"></span>
        <span class="cb-stage-bg__vignette"></span>
    </div>

    <!-- ============================================================
         SCENE PROGRESS RAIL (vertical dot-nav, cinematic only)
         ============================================================ -->
    <nav class="cb-scene-nav" aria-label="Page sections">
        <?php foreach ($cb_scene_nav as $i => $label) : ?>
            <button class="cb-scene-nav__dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                    data-scene-to="<?php echo esc_attr($i); ?>"
                    type="button">
                <span class="cb-scene-nav__label"><?php echo esc_html($label); ?></span>
            </button>
        <?php endforeach; ?>
    </nav>

    <div class="cb-scroll-scenes">

        <!-- ====================================================================
             SCENE 0 — ARRIVAL (Hero). Aerial over the Concho Valley.
             ==================================================================== -->
        <section class="cb-scene cb-scene--hero<?php echo $hero_video ? ' cb-scene--has-video' : ''; ?>" data-scene="0" id="scene-arrival" aria-label="Welcome">
            <?php if ($hero_video) : ?>
                <div class="cb-hero-video" aria-hidden="true">
                    <video autoplay muted loop playsinline preload="metadata"<?php echo $hero_poster ? ' poster="' . esc_url($hero_poster) . '"' : ''; ?>>
                        <source src="<?php echo esc_url($hero_video); ?>" type="video/mp4">
                    </video>
                </div>
                <div class="cb-hero-video__scrim" aria-hidden="true"></div>
            <?php endif; ?>
            <div class="cb-scene__inner">
                <div class="cb-hero-mark cb-scene__reveal" data-reveal="1">
                    <span class="cb-eyebrow cb-eyebrow--light">Live Well With Coldwell&#8480;</span>
                    <span class="cb-hero-mark__coords">31.46&deg;&nbsp;N&nbsp; &middot; &nbsp;100.44&deg;&nbsp;W &middot; San Angelo, Texas</span>
                </div>
                <h1 class="cb-hero__title cb-scene__reveal" data-reveal="2"><?php echo esc_html($hero_title); ?></h1>
                <p class="cb-hero__subtitle cb-scene__reveal" data-reveal="3"><?php echo esc_html($hero_subtitle); ?></p>
            </div>

            <div class="cb-scroll-cue cb-scene__reveal" data-reveal="4" id="cb-scroll-cue" aria-hidden="true">
                <span class="cb-scroll-cue__text">Scroll to explore</span>
                <span class="cb-scroll-cue__line"><span></span></span>
            </div>
        </section>

        <!-- ====================================================================
             SCENE 1 — WELCOME / QUICK ACTIONS
             ==================================================================== -->
        <section class="cb-scene cb-scene--welcome" data-scene="1" id="scene-welcome" aria-label="How can we help">
            <div class="cb-scene__inner cb-container">
                <div class="cb-scene__head">
                    <span class="cb-eyebrow cb-scene__reveal">Welcome to the Concho Valley</span>
                    <h2 class="cb-scene__title cb-scene__reveal">At home in San&nbsp;Angelo.</h2>
                    <p class="cb-scene__lead cb-scene__reveal">Whether you&rsquo;re buying, selling, or just dreaming &mdash; start here. Four ways we help you move forward.</p>
                </div>

                <div class="cb-actions-grid">
                    <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-action-card cb-scene__reveal">
                        <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('home'); ?></div>
                        <h3 class="cb-action-card__title">Find a Home</h3>
                        <p class="cb-action-card__desc">Browse available properties across San Angelo and the Concho Valley.</p>
                        <span class="cb-action-card__go">Explore <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-action-card cb-scene__reveal">
                        <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('sell'); ?></div>
                        <h3 class="cb-action-card__title">Sell Your Home</h3>
                        <p class="cb-action-card__desc">Get a free home valuation and connect with an expert agent.</p>
                        <span class="cb-action-card__go">Get value <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/our-team/')); ?>" class="cb-action-card cb-scene__reveal">
                        <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('team'); ?></div>
                        <h3 class="cb-action-card__title">Meet Our Team</h3>
                        <p class="cb-action-card__desc">Connect with experienced agents who know San Angelo inside and out.</p>
                        <span class="cb-action-card__go">Meet us <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/office/')); ?>" class="cb-action-card cb-scene__reveal">
                        <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('office'); ?></div>
                        <h3 class="cb-action-card__title">Visit Our Office</h3>
                        <p class="cb-action-card__desc">Stop by our office on Knickerbocker Road. We&rsquo;d love to meet you.</p>
                        <span class="cb-action-card__go">Directions <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                    </a>
                </div>
            </div>
        </section>

        <!-- ====================================================================
             SCENE 2 — FEATURED LISTINGS (live MLS)
             ==================================================================== -->
        <section class="cb-scene cb-scene--listings" data-scene="2" id="scene-listings" aria-label="Featured listings">
            <div class="cb-scene__inner cb-container">
                <div class="cb-scene__head">
                    <span class="cb-eyebrow cb-scene__reveal">Featured Properties</span>
                    <h2 class="cb-scene__title cb-scene__reveal">The latest on the market.</h2>
                    <p class="cb-scene__lead cb-scene__reveal">A hand-picked look at premier San Angelo homes, updated live from the MLS.</p>
                </div>

                <div class="cb-scene__reveal cb-listings-frame">
                    <?php echo do_shortcode('[cb_listings filter="featured" count="6" columns="3"]'); ?>
                </div>

                <div class="cb-scene__cta cb-scene__reveal">
                    <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--navy">View All Properties</a>
                </div>
            </div>
        </section>

        <!-- ====================================================================
             SCENE 3 — LEGACY / STATS (scroll-linked counters)
             ==================================================================== -->
        <section class="cb-scene cb-scene--legacy cb-scene--dark" data-scene="3" id="scene-legacy" aria-label="Our track record">
            <div class="cb-scene__inner cb-container">
                <div class="cb-scene__head">
                    <span class="cb-eyebrow cb-eyebrow--light cb-scene__reveal">Serving the Concho Valley for over 35 years</span>
                    <h2 class="cb-scene__title cb-scene__title--light cb-scene__reveal">A legacy of results in the Concho&nbsp;Valley.</h2>
                </div>

                <div class="cb-stats">
                    <div class="cb-stat cb-scene__reveal">
                        <div class="cb-stat__number" data-count="<?php echo esc_attr(get_theme_mod('cb_homes_sold', '1200')); ?>">0</div>
                        <div class="cb-stat__label">Homes Sold</div>
                    </div>
                    <div class="cb-stat cb-scene__reveal">
                        <div class="cb-stat__number" data-count="30">0</div>
                        <div class="cb-stat__label">Expert Agents</div>
                    </div>
                    <div class="cb-stat cb-scene__reveal">
                        <div class="cb-stat__number" data-count="<?php echo esc_attr(get_theme_mod('cb_years_serving', '35')); ?>">0</div>
                        <div class="cb-stat__label">Years Serving San Angelo</div>
                    </div>
                    <div class="cb-stat cb-scene__reveal">
                        <div class="cb-stat__number" data-count="20">0</div>
                        <div class="cb-stat__label">Communities Served</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ====================================================================
             SCENE 4 — "FIND YOUR HOME" CTA  (door / threshold reveal)
             ==================================================================== -->
        <section class="cb-scene cb-scene--door" data-scene="4" id="scene-buy" aria-label="Browse homes">
            <div class="cb-scene__door" aria-hidden="true">
                <span class="cb-scene__door-panel cb-scene__door-panel--l"></span>
                <span class="cb-scene__door-panel cb-scene__door-panel--r"></span>
            </div>
            <div class="cb-scene__inner cb-container">
                <div class="cb-cta-block">
                    <span class="cb-eyebrow cb-eyebrow--light cb-scene__reveal">For Buyers</span>
                    <h2 class="cb-cta-block__title cb-scene__reveal">Open the door to<br>San Angelo living.</h2>
                    <p class="cb-cta-block__lead cb-scene__reveal">Search every active listing in the Concho Valley &mdash; filtered, mapped, and updated in real time.</p>
                    <div class="cb-scene__reveal">
                        <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">
                            <?php echo cb_get_svg_icon('search'); ?>
                            Browse San Angelo Homes
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ====================================================================
             SCENE 5 — COMMUNITIES (Concho River SVG line-draw)
             ==================================================================== -->
        <section class="cb-scene cb-scene--communities" data-scene="5" id="scene-communities" aria-label="Featured communities">
            <svg class="cb-river" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
                <path class="cb-river__path" pathLength="1" d="M-40,200 C220,260 320,140 520,230 C720,320 760,520 980,560 C1180,596 1300,470 1500,540" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                <path class="cb-river__path cb-river__path--alt" pathLength="1" d="M-40,640 C200,600 360,720 560,660 C760,600 900,700 1100,650 C1280,604 1380,690 1500,650" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>

            <div class="cb-scene__inner cb-container">
                <div class="cb-scene__head">
                    <span class="cb-eyebrow cb-scene__reveal">Explore the Area</span>
                    <h2 class="cb-scene__title cb-scene__reveal">From the river to the ranch&nbsp;land.</h2>
                    <p class="cb-scene__lead cb-scene__reveal">From downtown San Angelo to the scenic shores of Lake Nasworthy, find the neighborhood that fits your life.</p>
                </div>

                <div class="cb-community-grid">
                    <?php
                    $cb_home_communities = function_exists('cb_get_communities') ? cb_get_communities() : [];
                    $cb_featured = ['grape-creek', 'bentwood', 'college-hills', 'christoval', 'wall', 'lake-nasworthy'];
                    foreach ($cb_featured as $slug) :
                        if (!isset($cb_home_communities[$slug])) continue;
                        $c       = $cb_home_communities[$slug];
                        $img_url = cb_community_image_url($c);
                    ?>
                        <a href="<?php echo esc_url(home_url('/communities/' . $slug . '/')); ?>"
                           class="cb-community-card<?php echo $img_url ? ' cb-community-card--image' : ''; ?> cb-scene__reveal"
                           <?php echo $img_url ? 'style="background-image:url(' . esc_url($img_url) . ');"' : ''; ?>>
                            <?php if ($img_url) : ?>
                                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($c['name'] . ', San Angelo TX'); ?>" class="cb-community-card__image" loading="lazy">
                            <?php endif; ?>
                            <div class="cb-community-card__overlay">
                                <h3 class="cb-community-card__name"><?php echo esc_html($c['name']); ?></h3>
                                <span class="cb-community-card__count">View Listings</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="cb-scene__cta cb-scene__reveal">
                    <a href="<?php echo esc_url(home_url('/communities/')); ?>" class="cb-btn cb-btn--navy">Explore All Communities</a>
                </div>
            </div>
        </section>

        <!-- ====================================================================
             SCENE 6 — "WHAT'S MY HOME WORTH?" CTA + Property Watch
             ==================================================================== -->
        <section class="cb-scene cb-scene--value" data-scene="6" id="scene-sell" aria-label="Sell your home">
            <div class="cb-scene__inner cb-container">
                <div class="cb-cta-block cb-cta-block--split">
                    <div class="cb-cta-block__main">
                        <span class="cb-eyebrow cb-eyebrow--light cb-scene__reveal">For Sellers</span>
                        <h2 class="cb-cta-block__title cb-scene__reveal" data-mask>What&rsquo;s my home<br>worth today?</h2>
                        <p class="cb-cta-block__lead cb-scene__reveal">Get a free, no-obligation valuation grounded in live San Angelo market data &mdash; usually within 24 hours.</p>
                        <div class="cb-scene__reveal">
                            <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Get My Home Value</a>
                        </div>
                    </div>

                    <aside class="cb-watch cb-scene__reveal" id="cb-property-watch" aria-label="Property Watch sign up">
                        <h3 class="cb-watch__title">Never miss a listing</h3>
                        <p class="cb-watch__desc">Property Watch emails you the moment a home matching your criteria hits the market.</p>
                        <form class="cb-watch__form" data-cb-watch>
                            <input type="email" class="cb-watch__input" name="email" placeholder="Enter your email address" aria-label="Email address" required>
                            <button type="submit" class="cb-btn cb-btn--primary">Sign Up</button>
                        </form>
                        <p class="cb-watch__note">No spam. Unsubscribe anytime.</p>
                    </aside>
                </div>
            </div>
        </section>

        <!-- ====================================================================
             SCENE 7 — TESTIMONIALS + BLOG + BRAND CLOSE (un-pinned)
             ==================================================================== -->
        <section class="cb-scene cb-scene--close" data-scene="7" id="scene-connect" aria-label="Reviews and stories">
            <div class="cb-scene__inner cb-container">
                <div class="cb-scene__head">
                    <span class="cb-eyebrow cb-eyebrow--light cb-scene__reveal">Client Stories</span>
                    <h2 class="cb-scene__title cb-scene__title--light cb-scene__reveal">What our clients say.</h2>
                    <p class="cb-scene__lead cb-scene__lead--light cb-scene__reveal">Real reviews from Coldwell Banker Legacy San Angelo clients &mdash; verified via Testimonial Tree.</p>
                </div>

                <div class="cb-scene__reveal cb-tt-frame">
                    <?php echo do_shortcode('[cb_testimonials type="rotator"]'); ?>
                </div>

                <div class="cb-scene__cta cb-scene__reveal">
                    <a href="<?php echo esc_url(home_url('/testimonials/')); ?>" class="cb-btn cb-btn--outline">Read All Reviews</a>
                </div>

                <!-- Latest from the blog -->
                <div class="cb-close-blog">
                    <div class="cb-scene__head cb-scene__head--sm">
                        <span class="cb-eyebrow cb-eyebrow--light cb-scene__reveal">From Our Blog</span>
                        <h2 class="cb-scene__title cb-scene__title--light cb-scene__title--sm cb-scene__reveal">Local insight &amp; market news.</h2>
                    </div>
                    <div class="cb-blog-grid">
                        <?php
                        $blog_posts = new WP_Query([
                            'post_type'      => 'post',
                            'posts_per_page' => 3,
                            'post_status'    => 'publish',
                        ]);

                        if ($blog_posts->have_posts()) :
                            while ($blog_posts->have_posts()) : $blog_posts->the_post();
                        ?>
                            <article class="cb-blog-card cb-scene__reveal">
                                <div class="cb-blog-card__image">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('cb-blog-thumb'); ?>
                                    <?php else : ?>
                                        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-blog.jpg'); ?>" alt="<?php the_title_attribute(); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="cb-blog-card__body">
                                    <?php
                                    $categories = get_the_category();
                                    if ($categories) : ?>
                                        <span class="cb-blog-card__category"><?php echo esc_html($categories[0]->name); ?></span>
                                    <?php endif; ?>
                                    <h3 class="cb-blog-card__title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    <p class="cb-blog-card__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
                                    <span class="cb-blog-card__meta"><?php echo get_the_date(); ?></span>
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
                            <article class="cb-blog-card cb-scene__reveal">
                                <div class="cb-blog-card__image">
                                    <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-blog.jpg'); ?>" alt="<?php echo esc_attr($p['title']); ?>">
                                </div>
                                <div class="cb-blog-card__body">
                                    <span class="cb-blog-card__category"><?php echo esc_html($p['cat']); ?></span>
                                    <h3 class="cb-blog-card__title"><a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php echo esc_html($p['title']); ?></a></h3>
                                    <p class="cb-blog-card__excerpt">Discover the latest insights about San Angelo real estate and community living.</p>
                                    <span class="cb-blog-card__meta"><?php echo esc_html($p['date']); ?></span>
                                </div>
                            </article>
                        <?php endforeach;
                        endif; ?>
                    </div>
                </div>

                <!-- Brand sign-off -->
                <div class="cb-close-mark cb-scene__reveal">
                    <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/logos/monogram-vertical-stacked.svg'); ?>" alt="Coldwell Banker Legacy" class="cb-close-mark__logo">
                    <p class="cb-close-mark__tag">Live Well With Coldwell&#8480;</p>
                    <p class="cb-close-mark__line">At home in San Angelo, Texas.</p>
                </div>
            </div>
        </section>

    </div><!-- /.cb-scroll-scenes -->

    <!-- ============================================================
         IMAGE CREDITS — San Angelo landmark photography is used under
         Creative Commons; attribution (title + author + source + license)
         is required by CC BY-SA 4.0. Keep this block on the page.
         (Applies to the Version-A photo fallback; harmless under WebGL.)
         ============================================================ -->
    <aside class="cb-photo-credits" aria-label="Photography credits">
        <p>
            San&nbsp;Angelo landmark photography &mdash;
            <a href="https://commons.wikimedia.org/wiki/File:San_Angelo_September_2019_64_(skyline).jpg" rel="nofollow noopener" target="_blank">Downtown&nbsp;Skyline</a>,
            <a href="https://commons.wikimedia.org/wiki/File:San_Angelo_September_2019_31_(Hilton_Hotel).jpg" rel="nofollow noopener" target="_blank">Cactus&nbsp;Hotel</a>,
            <a href="https://commons.wikimedia.org/wiki/File:San_Angelo_September_2019_36_(E_Concho_Avenue).jpg" rel="nofollow noopener" target="_blank">E.&nbsp;Concho&nbsp;Avenue</a>,
            <a href="https://commons.wikimedia.org/wiki/File:San_Angelo_September_2019_66_(Concho_River).jpg" rel="nofollow noopener" target="_blank">Concho&nbsp;River</a> &amp;
            <a href="https://commons.wikimedia.org/wiki/File:San_Angelo_September_2019_27_(Cathedral_Church_of_the_Sacred_Heart)_-_cropped.jpg" rel="nofollow noopener" target="_blank">Sacred&nbsp;Heart&nbsp;Cathedral</a>
            by Michael&nbsp;Barera, licensed under
            <a href="https://creativecommons.org/licenses/by-sa/4.0/" rel="license nofollow noopener" target="_blank">CC&nbsp;BY-SA&nbsp;4.0</a>.
            <a href="https://commons.wikimedia.org/wiki/File:Mason-Hughes_House_San_Angelo_Texas_2019.jpg" rel="nofollow noopener" target="_blank">Mason-Hughes&nbsp;House</a>
            by Larry&nbsp;D.&nbsp;Moore, licensed under
            <a href="https://creativecommons.org/licenses/by/4.0/" rel="license nofollow noopener" target="_blank">CC&nbsp;BY&nbsp;4.0</a>.
        </p>
    </aside>
</div><!-- /.cb-scroll-stage -->

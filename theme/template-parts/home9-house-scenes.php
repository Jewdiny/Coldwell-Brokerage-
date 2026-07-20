<?php
/**
 * Home "the house" scenes partial -- Home 9 preview.
 *
 * Identical to home8-pages-scenes.php in structure and content -- same 8 sections,
 * same cards, same live shortcodes -- with the cb9- namespace and one deliberate
 * difference:
 *
 *   THE PLATE IS FLAT-ONLY. While the house is running, each scene photograph
 *   hangs FRAMED on its room's far wall (home9.js hangArt), so the page must not
 *   also carry it as a backdrop or you would see the same picture twice: once on
 *   the wall, once floating in front of it. cb-home9.css hides it under .cb9-on.
 *   It stays in the markup because the flat fallback has no house, no wall, and
 *   therefore nowhere to hang anything.
 *
 * Home 8 is UNCHANGED and still ships. If you fix content here, fix it there.
 *
 * Original Home 8 docblock follows, since the structure is entirely its:
 *
 * Home 2's content, Home 5/7's hallway camera, and the layer Home 7's own
 * template docblock promised but never built: each section is a PAGE that floats
 * in the corridor and zooms as you scroll to it.
 *
 * STRUCTURE -- read before editing:
 *
 *   .cb9-scenes
 *     section.cb9-section[data-cb9-section=0..7]   SPACERS. Empty. Pure rect
 *                                                  providers; authored heights
 *                                                  drive the camera.
 *   .cb9-pages                                     Fixed stage, outside the flow.
 *     .cb9-page[data-cb9-page=0..7]                Geometry box. home9.js owns
 *                                                  `transform` here; nothing else
 *                                                  may write it.
 *       .cb9-page__float
 *         .cb9-page__skin                          TRANSPARENT. Owns opacity.
 *           img.cb9-page__plate                    Home 2's scene, low + masked.
 *           .cb9-page__scroll                      THE BROWSER OWNS THIS.
 *             .cb9-page__inner
 *               .cb9-lod                           Always rendered (cheap).
 *                 .cb9-card[data-cb9-card]
 *                   .cb9-card__inner               The glass.
 *               .cb9-page__body                    content-visibility gated.
 *                 .cb9-card[data-cb9-card] ...
 *
 * The page carries NO glass of its own -- the cards do, and the wireframe corridor
 * reads through the gaps between them. Put a background on .cb9-page__skin and you
 * are back to one big opaque box with the hallway hidden behind it.
 *
 * Cards are Home 7's: .cb9-card owns the form/crumble transform, .cb9-card__inner
 * owns the idle float. Never merge them -- they would clobber each other.
 *
 * Pages are NOT nested inside their spacers: spacers are rect providers, pages are
 * HUD, and keeping them apart stops the two concerns coupling by accident. The
 * `data-cb9-page` index must match its `data-cb9-section`, and the count must
 * equal home9.js's W[] length (8). That is the one hard coupling.
 *
 * Mirrors home-scenes.php (Home 2) for content; front-page.php is untouched.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

$hero_title    = get_theme_mod('cb_hero_title', 'Find Your Dream Home in San Angelo');
$hero_subtitle = get_theme_mod('cb_hero_subtitle', 'Discover luxury living with Coldwell Banker Legacy. Your trusted partner in San Angelo real estate.');

$cb_communities = function_exists('cb_get_communities') ? cb_get_communities() : [];
$cb_featured    = ['grape-creek', 'bentwood', 'college-hills', 'christoval', 'wall', 'lake-nasworthy'];

$cb9_nav = [
    0 => 'Arrival', 1 => 'Welcome', 2 => 'Listings', 3 => 'Legacy',
    4 => 'Buy', 5 => 'Communities', 6 => 'Sell', 7 => 'Connect',
];

/**
 * Per-card idle-float custom properties, cycled round-robin over 8 variants so no
 * two neighbours share a phase. Only the CSS keyframe fallback reads these -- when
 * Motion is present it drives .cb9-card__inner directly and html.cb9-motion
 * switches the keyframe off. Negative delays start each card mid-cycle.
 */
$cb9_i  = 0;
$cb9_fl = function () use (&$cb9_i) {
    $durs = [8.5, 10.0, 9.2, 11.5, 7.8, 12.4, 9.8, 10.8];
    $dels = [0.0, -2.3, -4.1, -1.2, -5.6, -3.0, -6.2, -0.7];
    $ys   = [10, 14, 8, 16, 12, 9, 15, 11];
    $rots = [0.6, -0.8, 0.5, -0.5, 0.9, -0.7, 0.4, -0.6];
    $i = $cb9_i++;
    return sprintf(
        'style="--fl-dur:%ss;--fl-delay:%ss;--fl-y:%spx;--fl-rot:%sdeg"',
        $durs[$i % 8], $dels[$i % 8], $ys[$i % 8], $rots[$i % 8]
    );
};

/**
 * Home 2's scene plate as the page's atmosphere.
 *
 * The same graded 2K stills Home 2 loads as WebGL textures (assets/images/webgl/).
 * Home 7's wireframe fork dropped them; Home 9 brings them back at low opacity,
 * masked to feather the edges, BEHIND the cards -- present as atmosphere without
 * burying the corridor. The webgl slug for scene 8 is `08-connect` (the scroll/
 * set calls the same scene `08-close`) -- they are different files; do not
 * "correct" one to the other.
 *
 * Eager, not lazy: a position:fixed page always intersects the viewport, so
 * loading="lazy" would resolve for all 8 on load anyway.
 */
$cb9_plates = [
    0 => '01-arrival.jpg',   1 => '02-welcome.jpg', 2 => '03-listings.jpg', 3 => '04-legacy.jpg',
    4 => '05-door.jpg',      5 => '06-communities-lake.webp', 6 => '07-value.jpg', 7 => '08-connect.jpg',
];
$cb9_plate = function ($i) use ($cb9_plates) {
    if (!isset($cb9_plates[$i])) { return; }
    printf(
        '<img class="cb9-page__plate" src="%s" alt="" aria-hidden="true" decoding="async"%s>',
        esc_url(CB_THEME_URI . '/assets/images/webgl/' . $cb9_plates[$i]),
        $i === 0 ? ' fetchpriority="high"' : ''
    );
};
?>

<div class="cb9-stage" id="cb9-stage">

    <canvas id="cb9-canvas" aria-hidden="true"></canvas>
    <div class="cb9-vignette" aria-hidden="true"></div>

    <nav class="cb9-nav" aria-label="Page sections">
        <?php foreach ($cb9_nav as $i => $label) : ?>
            <button class="cb9-nav__dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                    data-cb9-to="<?php echo esc_attr($i); ?>" type="button">
                <span class="cb9-nav__label"><?php echo esc_html($label); ?></span>
            </button>
        <?php endforeach; ?>
    </nav>

    <!-- SPACERS. Empty by design. Their heights are authored constants
         (cb-home8.css) with no relationship to content length -- that is what
         buys zero layout reads per frame. Inner scrollers absorb length. -->
    <div class="cb9-scenes">
        <section class="cb9-section cb9-section--hero" data-cb9-section="0" aria-hidden="true"></section>
        <section class="cb9-section" data-cb9-section="1" aria-hidden="true"></section>
        <section class="cb9-section" data-cb9-section="2" aria-hidden="true"></section>
        <section class="cb9-section" data-cb9-section="3" aria-hidden="true"></section>
        <section class="cb9-section" data-cb9-section="4" aria-hidden="true"></section>
        <section class="cb9-section" data-cb9-section="5" aria-hidden="true"></section>
        <section class="cb9-section" data-cb9-section="6" aria-hidden="true"></section>
        <section class="cb9-section" data-cb9-section="7" aria-hidden="true"></section>
    </div>

    <div class="cb9-pages">

        <!-- 0 -- ARRIVAL ------------------------------------------------ -->
        <section class="cb9-page" data-cb9-page="0" id="cb9-arrival" aria-label="Welcome">
            <div class="cb9-page__float">
                <div class="cb9-page__skin">
                    <?php $cb9_plate(0); ?>
                    <div class="cb9-page__scroll" tabindex="0">
                        <div class="cb9-page__inner">
                            <div class="cb9-lod">
                                <div class="cb9-card cb9-card--chip" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <span class="cb9-eyebrow">Live Well With Coldwell&#8480;</span>
                                        <span class="cb9-coords">31.46&deg;&nbsp;N &middot; 100.44&deg;&nbsp;W &middot; San Angelo, Texas</span>
                                    </div>
                                </div>
                                <div class="cb9-card cb9-card--title" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner"><h1 class="cb9-h1"><?php echo esc_html($hero_title); ?></h1></div>
                                </div>
                            </div>
                            <div class="cb9-page__body">
                                <div class="cb9-card cb9-card--lead" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner"><p class="cb9-sub"><?php echo esc_html($hero_subtitle); ?></p></div>
                                </div>
                                <div class="cb9-card cb9-card--ghost" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <span class="cb9-cue" aria-hidden="true">
                                            Scroll &mdash; walk the corridor
                                            <span class="cb9-cue__line"><span></span></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 1 -- WELCOME / QUICK ACTIONS -------------------------------- -->
        <section class="cb9-page" data-cb9-page="1" id="cb9-welcome" aria-label="How can we help">
            <div class="cb9-page__float">
                <div class="cb9-page__skin">
                    <?php $cb9_plate(1); ?>
                    <div class="cb9-page__scroll" tabindex="0">
                        <div class="cb9-page__inner">
                            <div class="cb9-lod">
                                <div class="cb9-card cb9-card--head" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <span class="cb9-eyebrow">Welcome to the Concho Valley</span>
                                        <h2 class="cb9-h2">At home in San&nbsp;Angelo.</h2>
                                        <p class="cb9-p">Whether you&rsquo;re buying, selling, or just dreaming &mdash; start here. Four ways we help you move forward.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="cb9-page__body">
                                <div class="cb9-grid cb9-grid--4">
                                    <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb9-card cb9-card--action" data-cb9-card data-cursor="Explore" <?php echo $cb9_fl(); ?>>
                                        <div class="cb9-card__inner">
                                            <h3 class="cb9-h3">Find a Home</h3>
                                            <p class="cb9-p">Browse available properties across San Angelo and the Concho Valley.</p>
                                            <span class="cb9-go">Explore <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                                        </div>
                                    </a>
                                    <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb9-card cb9-card--action" data-cb9-card data-cursor="Value" <?php echo $cb9_fl(); ?>>
                                        <div class="cb9-card__inner">
                                            <h3 class="cb9-h3">Sell Your Home</h3>
                                            <p class="cb9-p">Get a free home valuation and connect with an expert agent.</p>
                                            <span class="cb9-go">Get value <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                                        </div>
                                    </a>
                                    <a href="<?php echo esc_url(home_url('/our-team/')); ?>" class="cb9-card cb9-card--action" data-cb9-card data-cursor="Meet" <?php echo $cb9_fl(); ?>>
                                        <div class="cb9-card__inner">
                                            <h3 class="cb9-h3">Meet Our Team</h3>
                                            <p class="cb9-p">Connect with experienced agents who know San Angelo inside and out.</p>
                                            <span class="cb9-go">Meet us <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                                        </div>
                                    </a>
                                    <a href="<?php echo esc_url(home_url('/office/')); ?>" class="cb9-card cb9-card--action" data-cb9-card data-cursor="Visit" <?php echo $cb9_fl(); ?>>
                                        <div class="cb9-card__inner">
                                            <h3 class="cb9-h3">Visit Our Office</h3>
                                            <p class="cb9-p">Stop by our office on Knickerbocker Road. We&rsquo;d love to meet you.</p>
                                            <span class="cb9-go">Directions <?php echo cb_get_svg_icon('chevron-down'); ?></span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2 -- FEATURED LISTINGS (live MLS) --------------------------- -->
        <section class="cb9-page" data-cb9-page="2" id="cb9-listings" aria-label="Featured listings">
            <div class="cb9-page__float">
                <div class="cb9-page__skin">
                    <?php $cb9_plate(2); ?>
                    <div class="cb9-page__scroll" tabindex="0">
                        <div class="cb9-page__inner">
                            <div class="cb9-lod">
                                <div class="cb9-card cb9-card--head" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <span class="cb9-eyebrow">Featured Properties</span>
                                        <h2 class="cb9-h2">The latest on the market.</h2>
                                        <p class="cb9-p">A hand-picked look at premier San Angelo homes, updated live from the MLS.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="cb9-page__body">
                                <div class="cb9-card cb9-card--frame" data-cb9-card data-cb9-frame <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <?php echo do_shortcode('[cb_listings filter="featured" count="6" columns="3"]'); ?>
                                    </div>
                                </div>
                                <div class="cb9-card cb9-card--cta-row" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary">View All Properties</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3 -- LEGACY / STATS ----------------------------------------- -->
        <section class="cb9-page" data-cb9-page="3" id="cb9-legacy" aria-label="Our track record">
            <div class="cb9-page__float">
                <div class="cb9-page__skin">
                    <?php $cb9_plate(3); ?>
                    <div class="cb9-page__scroll" tabindex="0">
                        <div class="cb9-page__inner">
                            <div class="cb9-lod">
                                <div class="cb9-card cb9-card--head" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <span class="cb9-eyebrow">Since 2000</span>
                                        <h2 class="cb9-h2">A legacy of results in the Concho&nbsp;Valley.</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="cb9-page__body">
                                <div class="cb9-grid cb9-grid--4">
                                    <div class="cb9-card cb9-card--stat" data-cb9-card <?php echo $cb9_fl(); ?>>
                                        <div class="cb9-card__inner">
                                            <div class="cb9-stat__num" data-count="<?php echo esc_attr(get_theme_mod('cb_homes_sold', '1200')); ?>">0</div>
                                            <div class="cb9-stat__label">Homes Sold</div>
                                        </div>
                                    </div>
                                    <div class="cb9-card cb9-card--stat" data-cb9-card <?php echo $cb9_fl(); ?>>
                                        <div class="cb9-card__inner">
                                            <div class="cb9-stat__num" data-count="30">0</div>
                                            <div class="cb9-stat__label">Expert Agents</div>
                                        </div>
                                    </div>
                                    <div class="cb9-card cb9-card--stat" data-cb9-card <?php echo $cb9_fl(); ?>>
                                        <div class="cb9-card__inner">
                                            <div class="cb9-stat__num" data-count="<?php echo esc_attr(get_theme_mod('cb_years_serving', '25')); ?>">0</div>
                                            <div class="cb9-stat__label">Years Serving San Angelo</div>
                                        </div>
                                    </div>
                                    <div class="cb9-card cb9-card--stat" data-cb9-card <?php echo $cb9_fl(); ?>>
                                        <div class="cb9-card__inner">
                                            <div class="cb9-stat__num" data-count="20">0</div>
                                            <div class="cb9-stat__label">Communities Served</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4 -- BUYERS ------------------------------------------------- -->
        <section class="cb9-page" data-cb9-page="4" id="cb9-buy" aria-label="Browse homes">
            <div class="cb9-page__float">
                <div class="cb9-page__skin">
                    <?php $cb9_plate(4); ?>
                    <div class="cb9-page__scroll" tabindex="0">
                        <div class="cb9-page__inner">
                            <div class="cb9-lod">
                                <div class="cb9-card cb9-card--head" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <span class="cb9-eyebrow">For Buyers</span>
                                        <h2 class="cb9-h2">Open the door to San&nbsp;Angelo living.</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="cb9-page__body">
                                <div class="cb9-card cb9-card--cta" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <p class="cb9-p" style="margin-top:0">Search every active listing in the Concho Valley &mdash; filtered, mapped, and updated in real time.</p>
                                        <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Browse San Angelo Homes</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5 -- COMMUNITIES -------------------------------------------- -->
        <section class="cb9-page" data-cb9-page="5" id="cb9-communities" aria-label="Featured communities">
            <div class="cb9-page__float">
                <div class="cb9-page__skin">
                    <?php $cb9_plate(5); ?>
                    <div class="cb9-page__scroll" tabindex="0">
                        <div class="cb9-page__inner">
                            <div class="cb9-lod">
                                <div class="cb9-card cb9-card--head" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <span class="cb9-eyebrow">Explore the Area</span>
                                        <h2 class="cb9-h2">From the river to the ranch&nbsp;land.</h2>
                                        <p class="cb9-p">From downtown San Angelo to the scenic shores of Lake Nasworthy, find the neighborhood that fits your life.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="cb9-page__body">
                                <div class="cb9-grid cb9-grid--3">
                                    <?php foreach ($cb_featured as $slug) :
                                        if (!isset($cb_communities[$slug])) { continue; }
                                        $c = $cb_communities[$slug];
                                        $img_url = function_exists('cb_community_image_url') ? cb_community_image_url($c) : '';
                                    ?>
                                        <a href="<?php echo esc_url(home_url('/communities/' . $slug . '/')); ?>"
                                           class="cb9-card cb9-card--community" data-cb9-card data-cursor="View" <?php echo $cb9_fl(); ?>>
                                            <div class="cb9-card__inner">
                                                <?php if ($img_url) : ?>
                                                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($c['name'] . ', San Angelo TX'); ?>" class="cb9-community__img" decoding="async">
                                                <?php endif; ?>
                                                <div class="cb9-community__overlay">
                                                    <h3 class="cb9-h3"><?php echo esc_html($c['name']); ?></h3>
                                                    <span class="cb9-go">View Listings</span>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <div class="cb9-card cb9-card--cta-row" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <a href="<?php echo esc_url(home_url('/communities/')); ?>" class="cb-btn cb-btn--outline">Explore All Communities</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6 -- SELLERS + PROPERTY WATCH ------------------------------- -->
        <section class="cb9-page" data-cb9-page="6" id="cb9-sell" aria-label="Sell your home">
            <div class="cb9-page__float">
                <div class="cb9-page__skin">
                    <?php $cb9_plate(6); ?>
                    <div class="cb9-page__scroll" tabindex="0">
                        <div class="cb9-page__inner">
                            <div class="cb9-lod">
                                <div class="cb9-card cb9-card--head" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <span class="cb9-eyebrow">For Sellers</span>
                                        <h2 class="cb9-h2">What&rsquo;s my home worth today?</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="cb9-page__body">
                                <div class="cb9-grid cb9-grid--2">
                                    <div class="cb9-card cb9-card--cta" data-cb9-card <?php echo $cb9_fl(); ?>>
                                        <div class="cb9-card__inner">
                                            <p class="cb9-p" style="margin-top:0">Get a free, no-obligation valuation grounded in live San Angelo market data &mdash; usually within 24 hours.</p>
                                            <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Get My Home Value</a>
                                        </div>
                                    </div>
                                    <div class="cb9-card cb9-card--watch" data-cb9-card <?php echo $cb9_fl(); ?>>
                                        <div class="cb9-card__inner">
                                            <h3 class="cb9-h3">Never miss a listing</h3>
                                            <p class="cb9-p">Property Watch emails you the moment a home matching your criteria hits the market.</p>
                                            <form class="cb9-watch__form" data-cb-watch>
                                                <input type="email" class="cb9-watch__input" name="email" placeholder="Enter your email address" aria-label="Email address" required>
                                                <button type="submit" class="cb-btn cb-btn--primary">Sign Up</button>
                                            </form>
                                            <p class="cb9-watch__note">No spam. Unsubscribe anytime.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7 -- TESTIMONIALS + BLOG + BRAND CLOSE ---------------------- -->
        <section class="cb9-page" data-cb9-page="7" id="cb9-connect" aria-label="Reviews and stories">
            <div class="cb9-page__float">
                <div class="cb9-page__skin">
                    <?php $cb9_plate(7); ?>
                    <div class="cb9-page__scroll" tabindex="0">
                        <div class="cb9-page__inner">
                            <div class="cb9-lod">
                                <div class="cb9-card cb9-card--head" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <span class="cb9-eyebrow">Client Stories</span>
                                        <h2 class="cb9-h2">What our clients say.</h2>
                                        <p class="cb9-p">Real reviews from Coldwell Banker Legacy San Angelo clients &mdash; verified via Testimonial Tree.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="cb9-page__body">
                                <div class="cb9-card cb9-card--frame" data-cb9-card data-cb9-frame <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <?php echo do_shortcode('[cb_testimonials type="rotator"]'); ?>
                                    </div>
                                </div>
                                <div class="cb9-card cb9-card--cta-row" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <a href="<?php echo esc_url(home_url('/testimonials/')); ?>" class="cb-btn cb-btn--outline">Read All Reviews</a>
                                    </div>
                                </div>

                                <div class="cb9-card cb9-card--head" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <span class="cb9-eyebrow">From Our Blog</span>
                                        <h2 class="cb9-h2 cb9-h2--sm">Local insight &amp; market news.</h2>
                                    </div>
                                </div>
                                <div class="cb9-grid cb9-grid--3">
                                    <?php
                                    $blog_posts = new WP_Query([
                                        'post_type'      => 'post',
                                        'posts_per_page' => 3,
                                        'post_status'    => 'publish',
                                    ]);
                                    if ($blog_posts->have_posts()) :
                                        while ($blog_posts->have_posts()) : $blog_posts->the_post(); ?>
                                        <article class="cb9-card cb9-card--blog" data-cb9-card <?php echo $cb9_fl(); ?>>
                                            <div class="cb9-card__inner">
                                                <?php if (has_post_thumbnail()) : ?>
                                                    <?php /* loading=eager, not WordPress's default lazy: a position:fixed
                                                             page always intersects the viewport, so a lazy blog thumbnail
                                                             here can defer forever and never appear. */ ?>
                                                    <div class="cb9-blog__image"><?php the_post_thumbnail('cb-blog-thumb', ['loading' => 'eager', 'decoding' => 'async']); ?></div>
                                                <?php else : ?>
                                                    <div class="cb9-blog__image cb9-blog__image--ph" aria-hidden="true"></div>
                                                <?php endif; ?>
                                                <div class="cb9-blog__body">
                                                    <?php $categories = get_the_category(); if ($categories) : ?>
                                                        <span class="cb9-blog__cat"><?php echo esc_html($categories[0]->name); ?></span>
                                                    <?php endif; ?>
                                                    <h3 class="cb9-h3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                                    <p class="cb9-p"><?php echo esc_html(get_the_excerpt()); ?></p>
                                                    <span class="cb9-blog__meta"><?php echo get_the_date(); ?></span>
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
                                        <article class="cb9-card cb9-card--blog" data-cb9-card <?php echo $cb9_fl(); ?>>
                                            <div class="cb9-card__inner">
                                                <div class="cb9-blog__image cb9-blog__image--ph" aria-hidden="true"></div>
                                                <div class="cb9-blog__body">
                                                    <span class="cb9-blog__cat"><?php echo esc_html($p['cat']); ?></span>
                                                    <h3 class="cb9-h3"><a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php echo esc_html($p['title']); ?></a></h3>
                                                    <p class="cb9-p">Discover the latest insights about San Angelo real estate and community living.</p>
                                                    <span class="cb9-blog__meta"><?php echo esc_html($p['date']); ?></span>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach;
                                    endif; ?>
                                </div>

                                <div class="cb9-card cb9-card--mark" data-cb9-card <?php echo $cb9_fl(); ?>>
                                    <div class="cb9-card__inner">
                                        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/logos/monogram-vertical-stacked.svg'); ?>" alt="Coldwell Banker Legacy" class="cb9-mark__logo">
                                        <p class="cb9-mark__tag">Live Well With Coldwell&#8480;</p>
                                        <p class="cb9-mark__line">At home in San Angelo, Texas.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div><!-- /.cb9-pages -->

    <?php
    /*
     * Photography credits -- REQUIRED, not decorative.
     *
     * The scene plates are Wikimedia Commons photographs under CC BY-SA 4.0 /
     * CC BY 4.0. Those licences require attribution wherever the work is
     * displayed. STORYBOARD.md: "Attribution is required by CC and lives in a
     * .cb-photo-credits block at the bottom of front-page.php ... Keep it on the
     * page." Home 2 ships it; Home 7 legitimately dropped it because its
     * wireframe fork used no photographs at all. Home 9 puts the plates back, so
     * the credits come back with them.
     *
     * Mirrors the block in home-scenes.php / front-page.php. If the plates ever
     * go, this goes with them -- but while a plate renders, this ships.
     */
    ?>
    <aside class="cb9-credits" aria-label="Photography credits">
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
</div><!-- /.cb9-stage -->


<?php
/**
 * Homepage Template — "The House, filmed" (Home 10)
 *
 * THE OFFICIAL HOMEPAGE. This is what a visitor gets at the root domain.
 *
 * WHY THIS FILE AND NOT SETTINGS → READING
 * ----------------------------------------
 * front-page.php wins over everything in WordPress's template hierarchy for the
 * site's front page. Assigning the Home 10 page under Settings → Reading would
 * have done NOTHING while this file existed — WordPress would still have served
 * this. Making Home 10 the homepage therefore means making front-page.php render
 * it, which is what the single get_template_part() below does.
 *
 * WHAT THIS REPLACED
 * ------------------
 * The previous cinematic scroll homepage (.cb-scroll-stage / .cb-scene, driven by
 * page-animations/home.js). That design is NOT lost: template-parts/home-scenes.php
 * holds the same markup and is still rendered by templates/template-home2-webgl.php,
 * so it stays reachable as the Home 2 preview. Reverting is a one-line change here
 * plus restoring this file from git.
 *
 * DEGRADATION — this matters more here than on a preview
 * ------------------------------------------------------
 * deploy/cb-home10-preview.php only starts the filmed walk on desktop, with a
 * fine pointer, no reduced-motion preference and no Save-Data request. Everyone
 * else — every phone, every tablet — gets the flat stacked page, which is a
 * complete, readable, fully server-rendered version of the same content. That is
 * the design, not a fallback bolted on: all the live content (MLS listings,
 * stat counters, communities, testimonials, blog) is server-rendered and stays
 * crawlable with zero JS.
 *
 * SEO: unlike the previews, this page is INDEXED. No noindex here, and
 * cb_home10_suppress_extras() deliberately skips the front page so analytics and
 * the brokerage schema still run — they must, on the most important page.
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'San Angelo Real Estate | Coldwell Banker Legacy — Homes for Sale in the Concho Valley',
    // "since 2000" was wrong here too, and this string is the description Google
    // shows under the result. Their own office page says "For over 35 years";
    // third-party listings give 1980. Matches the Legacy section's eyebrow.
    'description' => 'Search live San Angelo MLS listings, luxury homes, and Concho Valley real estate. Coldwell Banker Legacy — serving the Concho Valley for over 35 years. (325) 944-9559.',
    'canonical'   => home_url('/'),
    'og_image'    => CB_THEME_URI . '/assets/images/home10/01-hall.jpg',
]);

get_header();

get_template_part('template-parts/home10-filmed-scenes');

get_footer();

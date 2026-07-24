<?php
/**
 * Home 10 "filmed" scenes partial.
 *
 * Home 10 is Home 9's CONTENT with a different world behind it: instead of a
 * real-time WebGL house, the rooms are photoreal stills and the moves between
 * them are short video clips of the camera walking there.
 *
 * WHY THIS FILE RENDERS HOME 9 AND REWRITES IT
 * --------------------------------------------
 * The eight panels are not retyped here. This partial buffers
 * template-parts/home9-house-scenes.php, lifts its .cb9-pages block, and
 * rewrites the OUTER wrapper of each panel. That is the same thing
 * harness/build-home10-harness.mjs does to the generated harness, and it is
 * deliberate on both sides: the copy, the shortcodes, the cards and the live
 * MLS/testimonial calls have exactly ONE source. Fix a listing headline once
 * and Home 9, Home 10 and both harnesses all change together. Duplicating ~450
 * lines of markup to change one class name would guarantee they drift.
 *
 * WHAT IS REWRITTEN, AND WHAT IS NOT
 * ----------------------------------
 *  - The outer <section class="cb9-page"> becomes <section class="cb10-page">.
 *    It cannot keep BOTH classes: cb-home9.css's flat fallback pins .cb9-page
 *    with `position: relative !important; width: auto !important`, and
 *    !important beats anything cb-home10.css can say about positioning a panel.
 *  - Everything INSIDE keeps its cb9-* classes on purpose. cb-home9.css styles
 *    all the components in a panel (headings, cards, buttons, grids) and its
 *    floating layout is nested under html.cb9-on, which this page never sets.
 *    So loading cb-home9.css gives us the flat COMPONENT styling for free with
 *    no floating layout attached, and cb-home10.css only has to own the shell.
 *  - img.cb9-page__plate is REMOVED, not hidden. Home 9 hangs those Home 2
 *    photographs framed on its room walls; Home 10 has its own photography in
 *    every frame and never shows them. Left in the markup they would still be
 *    fetched -- eight images on a page that already carries ~16MB of video --
 *    and they carry CC-BY attribution obligations for pictures nobody sees.
 *    Removing them here is also why this partial emits no photography credits
 *    block, while home9-house-scenes.php correctly does.
 *
 * If the Home 9 markup ever stops matching what the regexes below expect, this
 * bails to the plain Home 9 partial rather than emitting a broken page, and
 * says so in the error log. A silently empty homepage is the one outcome worth
 * writing code to avoid.
 *
 * @package CB_Legacy_Luxury
 */

if (!defined('ABSPATH')) { exit; }

/**
 * Nav labels, in section order. Must match home10.js's SECTIONS length (8) AND
 * the panel order in home9-house-scenes.php -- these are just labels for dots
 * that index by position, so if the panels are reordered and this is not, every
 * dot names the wrong room.
 *
 * Communities sits at 3 (client request), pushing Legacy and Front door down.
 */
$cb10_nav = [
    'Arrival', 'Welcome', 'Listings', 'Communities',
    'Legacy', 'Front door', 'Value', 'Connect',
];

ob_start();
get_template_part('template-parts/home9-house-scenes');
$cb10_src = ob_get_clean();

/**
 * Lift the .cb9-pages block. Matched on the authored comment marker rather than
 * by counting </div>s: the block contains dozens of nested divs and shortcode
 * output whose depth we do not control, so a balanced-tag scan over live MLS
 * markup is exactly the kind of thing that works until a listing has one extra
 * wrapper.
 */
$cb10_pages = '';
if (preg_match('~<div class="cb9-pages">(.*?)</div><!-- /\.cb9-pages -->~s', $cb10_src, $m)) {
    $cb10_pages = $m[1];
}

// Rewrite the outer wrapper of each panel, and drop the Home 9 plates.
$cb10_count = 0;
if ($cb10_pages !== '') {
    $cb10_pages = preg_replace_callback(
        '~<section class="cb9-page"~',
        function () use (&$cb10_count) {
            return '<section class="cb10-page" data-cb10-page="' . ($cb10_count++) . '"';
        },
        $cb10_pages
    );
    $cb10_pages = preg_replace('~<img class="cb9-page__plate"[^>]*>~', '', $cb10_pages);
}

// 8 panels or nothing. A partial count means the markup moved and the page
// would be missing rooms -- fall back to the Home 9 partial, which is whole.
if ($cb10_count !== count($cb10_nav)) {
    if (function_exists('error_log')) {
        error_log(sprintf(
            '[cb10] expected %d panels in home9-house-scenes, found %d -- the Home 9 markup moved. Falling back to Home 9.',
            count($cb10_nav),
            $cb10_count
        ));
    }
    echo $cb10_src; // phpcs:ignore WordPress.Security.EscapeOutput -- template markup
    return;
}
?>

<div class="cb10-shell" id="cb10-shell">

    <!-- The world. home10.js fills this with one <video> per section. -->
    <div class="cb10-stage" id="cb10-stage" aria-hidden="true"></div>

    <!-- The only thing making the document tall; height is set from JS. -->
    <div id="cb10-spacer"></div>

    <nav class="cb10-nav" aria-label="Rooms">
        <?php foreach ($cb10_nav as $i => $label) : ?>
            <button class="cb10-nav__dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                    data-cb10-to="<?php echo esc_attr($i); ?>"
                    type="button"
                    aria-label="<?php echo esc_attr($label); ?>"></button>
        <?php endforeach; ?>
    </nav>

    <div class="cb10-pages">
        <?php echo $cb10_pages; // phpcs:ignore WordPress.Security.EscapeOutput -- lifted, already-escaped template markup ?>
    </div>

</div><!-- /.cb10-shell -->

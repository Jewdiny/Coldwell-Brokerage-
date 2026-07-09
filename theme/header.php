<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    /* Set capability flags synchronously so CSS can branch with no flash of
       unstyled content. .cb-cinematic gates the pinned-scene homepage; it only
       has visual effect where scroll-home.css is enqueued (the front page). */
    (function (d) {
        var h = d.documentElement;
        h.className += ' js';
        try {
            if (window.matchMedia('(min-width: 1025px)').matches &&
                window.matchMedia('(prefers-reduced-motion: no-preference)').matches &&
                !window.matchMedia('(pointer: coarse)').matches) {
                h.className += ' cb-cinematic';
            }
        } catch (e) {}
    })(document);
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:wght@400;500;600;700&family=Josefin+Sans:wght@300;400;500;600&family=Roboto:wght@300;400;500;700&family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400&display=swap">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="cb-skip-link" href="#cb-main">Skip to content</a>

<!-- Header -->
<?php
// Transparent (overlay) header on the homepage and the Home 2 cinematic preview.
$cb_transparent_header = is_front_page()
    || (defined('CB_HOME2_TEMPLATE') && is_page_template(CB_HOME2_TEMPLATE));
?>
<header class="cb-header<?php echo $cb_transparent_header ? ' cb-header--transparent' : ''; ?>" id="cb-header">
    <div class="cb-header__inner">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="cb-header__logo" aria-label="Coldwell Banker Legacy &mdash; San Angelo home">
            <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/logos/monogram-horizontal-stacked.svg'); ?>" alt="Coldwell Banker Legacy &mdash; San Angelo" class="cb-header__logo-img">
        </a>

        <nav class="cb-nav" role="navigation" aria-label="<?php esc_attr_e('Primary Navigation', 'cb-legacy'); ?>">
            <div class="cb-nav__item">
                <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-nav__link">Find a Home</a>
            </div>
            <div class="cb-nav__item">
                <a href="#" class="cb-nav__link">
                    Luxury
                    <span class="cb-nav__arrow"><?php echo cb_get_svg_icon('chevron-down'); ?></span>
                </a>
                <div class="cb-nav__dropdown">
                    <a href="<?php echo esc_url(home_url('/luxury/')); ?>" class="cb-nav__dropdown-link">Luxury Listings</a>
                </div>
            </div>
            <div class="cb-nav__item">
                <a href="<?php echo esc_url(home_url('/our-team/')); ?>" class="cb-nav__link">Our Team</a>
            </div>
            <div class="cb-nav__item">
                <a href="#" class="cb-nav__link">
                    Resources
                    <span class="cb-nav__arrow"><?php echo cb_get_svg_icon('chevron-down'); ?></span>
                </a>
                <div class="cb-nav__dropdown">
                    <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-nav__dropdown-link">What is My Home Worth?</a>
                    <a href="<?php echo esc_url(home_url('/buyer-seller-resources/')); ?>" class="cb-nav__dropdown-link">Buyer &amp; Seller Tips</a>
                    <a href="<?php echo esc_url(home_url('/getting-a-home-loan/')); ?>" class="cb-nav__dropdown-link">Getting a Home Loan</a>
                    <a href="<?php echo esc_url(home_url('/corporate-relocation/')); ?>" class="cb-nav__dropdown-link">Corporate Relocation</a>
                    <a href="<?php echo esc_url(home_url('/market-report/')); ?>" class="cb-nav__dropdown-link">Market Report</a>
                </div>
            </div>
            <div class="cb-nav__item">
                <a href="<?php echo esc_url(home_url('/rentals/')); ?>" class="cb-nav__link">Rentals</a>
            </div>
            <div class="cb-nav__item">
                <a href="#" class="cb-nav__link">
                    About
                    <span class="cb-nav__arrow"><?php echo cb_get_svg_icon('chevron-down'); ?></span>
                </a>
                <div class="cb-nav__dropdown">
                    <a href="<?php echo esc_url(home_url('/about/')); ?>" class="cb-nav__dropdown-link">About Us</a>
                    <a href="<?php echo esc_url(home_url('/office/')); ?>" class="cb-nav__dropdown-link">Our Office</a>
                    <a href="<?php echo esc_url(home_url('/events/')); ?>" class="cb-nav__dropdown-link">Community Events</a>
                    <a href="<?php echo esc_url(home_url('/careers/')); ?>" class="cb-nav__dropdown-link">Careers</a>
                </div>
            </div>
            <div class="cb-nav__item">
                <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="cb-nav__link">Blog</a>
            </div>
            <div class="cb-nav__item">
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-nav__link cb-btn cb-btn--primary" style="padding:0.5rem 1.25rem;font-size:0.8125rem;">Contact Us</a>
            </div>
        </nav>

        <button class="cb-menu-toggle" id="cb-menu-toggle" aria-label="<?php esc_attr_e('Toggle Menu', 'cb-legacy'); ?>">
            <span class="cb-menu-toggle__line"></span>
            <span class="cb-menu-toggle__line"></span>
            <span class="cb-menu-toggle__line"></span>
        </button>
    </div>
</header>

<!-- Mobile Menu -->
<div class="cb-mobile-menu" id="cb-mobile-menu">
    <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-mobile-menu__link">Find a Home</a>
    <a href="<?php echo esc_url(home_url('/luxury/')); ?>" class="cb-mobile-menu__link">Luxury</a>
    <a href="<?php echo esc_url(home_url('/our-team/')); ?>" class="cb-mobile-menu__link">Our Team</a>
    <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-mobile-menu__link">Home Value</a>
    <a href="<?php echo esc_url(home_url('/rentals/')); ?>" class="cb-mobile-menu__link">Rentals</a>
    <a href="<?php echo esc_url(home_url('/about/')); ?>" class="cb-mobile-menu__link">About</a>
    <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="cb-mobile-menu__link">Blog</a>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-mobile-menu__link">Contact</a>
</div>

<main id="cb-main">

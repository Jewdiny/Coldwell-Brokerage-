<?php
/**
 * Template Name: About Us
 *
 * @package CB_Legacy_Luxury
 */

$phone   = get_theme_mod('cb_phone', '(325) 944-9559');
$address = get_theme_mod('cb_address', '3017 Knickerbocker, San Angelo, TX 76904');

cb_set_seo_meta([
    'title'       => "About Coldwell Banker Legacy | San Angelo's Premier Real Estate Brokerage",
    'description' => 'Learn about Coldwell Banker Legacy — locally owned, globally connected. 30+ agents serving San Angelo, Bentwood, Lake Nasworthy, Christoval, and the entire Concho Valley.',
    'canonical'   => get_permalink(),
]);

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Hero -->
<section class="cb-page-hero">
    <div class="cb-page-hero__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>');"></div>
    <div class="cb-page-hero__overlay"></div>
    <div class="cb-page-hero__content">
        <span class="cb-section__subtitle cb-reveal">Coldwell Banker Legacy</span>
        <h1 class="cb-reveal">Our Story</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
    </div>
</section>

<!-- About Intro -->
<section class="cb-section">
    <div class="cb-container" style="max-width:900px;">
        <div class="cb-about-intro">
            <div class="cb-about-intro__text cb-reveal">
                <h2>Your Trusted Real Estate Partner</h2>
                <p style="font-size:1.125rem;line-height:1.8;color:var(--cb-text-muted);margin-top:1.5rem;">
                    For over 35 years, Coldwell Banker Legacy has been a leading real estate brokerage in the San Angelo and Concho Valley area. Our deep roots in the community, combined with the global reach of the Coldwell Banker brand, provide our clients with unmatched service and market expertise.
                </p>
                <p style="font-size:1.125rem;line-height:1.8;color:var(--cb-text-muted);margin-top:1rem;">
                    Our team of over 30 dedicated agents brings diverse specializations — from first-time homebuyers to luxury estates, commercial properties to ranch land. We are more than a real estate office; we are your neighbors, committed to the growth and prosperity of San Angelo.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Timeline -->
<section class="cb-section cb-section--offwhite" id="cb-timeline">
    <div class="cb-container" style="max-width:800px;">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Our Journey</span>
            <h2 class="cb-section__title">Milestones</h2>
            <div class="cb-section__divider"></div>
        </div>

        <div class="cb-timeline">
            <div class="cb-timeline__line"></div>

            <?php /* The first two entries are the brand and the office, and they
                     are different dates: Coldwell Banker was founded in 1906 in San
                     Francisco; the San Angelo brokerage opened in 1980. The previous
                     first entry read "2001 — Founded in San Angelo", which was the
                     same error family as the old "Since 2000" eyebrow: it contradicted
                     the brokerage's own "over 35 years" and undercounted the office by
                     two decades. IMAGES: each card has a slot ready below; drop the
                     Canva timeline art in when it arrives. */ ?>
            <div class="cb-timeline__item cb-reveal--left">
                <div class="cb-timeline__year">1906</div>
                <div class="cb-timeline__card">
                    <h4>Coldwell Banker is Founded</h4>
                    <p>Colbert Coldwell and Benjamin Banker open in San Francisco, building the brand on a promise of honest, transparent representation.</p>
                </div>
            </div>

            <div class="cb-timeline__item cb-timeline__item--right cb-reveal--right">
                <div class="cb-timeline__year">1980</div>
                <div class="cb-timeline__card">
                    <h4>Opening in San Angelo</h4>
                    <p>Coldwell Banker Legacy opens its doors on Knickerbocker Road, beginning what is now more than four decades serving the Concho Valley.</p>
                </div>
            </div>

            <div class="cb-timeline__item cb-reveal--left">
                <div class="cb-timeline__year">2008</div>
                <div class="cb-timeline__card">
                    <h4>Expanded Services</h4>
                    <p>Added property management and rental services, becoming a full-service real estate brokerage.</p>
                </div>
            </div>

            <div class="cb-timeline__item cb-reveal--left">
                <div class="cb-timeline__year">2015</div>
                <div class="cb-timeline__card">
                    <h4>Global Luxury Program</h4>
                    <p>Earned designation as a Coldwell Banker Global Luxury office, bringing world-class marketing to San Angelo's finest properties.</p>
                </div>
            </div>

            <div class="cb-timeline__item cb-timeline__item--right cb-reveal--right">
                <div class="cb-timeline__year">2020</div>
                <div class="cb-timeline__card">
                    <h4>Digital Transformation</h4>
                    <p>Launched virtual tours and digital marketing initiatives, ensuring seamless service through challenging times.</p>
                </div>
            </div>

            <div class="cb-timeline__item cb-reveal--left">
                <div class="cb-timeline__year">2026</div>
                <div class="cb-timeline__card">
                    <h4>46 Years in the Concho Valley</h4>
                    <p>Forty-six years on from opening in San Angelo, still growing &mdash; a team of 30+ agents serving over 20 communities across the region.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Office Info -->
<section class="cb-section" id="cb-office">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Visit Us</span>
            <h2 class="cb-section__title">Our Office</h2>
            <div class="cb-section__divider"></div>
        </div>

        <div class="cb-office-grid">
            <div class="cb-office-info cb-reveal--left">
                <div class="cb-contact-info__item" style="margin-bottom:1.5rem;">
                    <div class="cb-contact-info__icon" style="color:var(--cb-gold);"><?php echo cb_get_svg_icon('map-pin'); ?></div>
                    <div>
                        <strong>Address</strong>
                        <p style="color:var(--cb-text-muted);"><?php echo esc_html($address); ?></p>
                    </div>
                </div>
                <div class="cb-contact-info__item" style="margin-bottom:1.5rem;">
                    <div class="cb-contact-info__icon" style="color:var(--cb-gold);"><?php echo cb_get_svg_icon('phone'); ?></div>
                    <div>
                        <strong>Phone</strong>
                        <p style="color:var(--cb-text-muted);"><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></p>
                    </div>
                </div>
                <div style="margin-top:2rem;">
                    <h4 style="margin-bottom:1rem;">Office Hours</h4>
                    <table class="cb-hours-table">
                        <tr><td>Monday - Friday</td><td>8:00 AM - 5:00 PM</td></tr>
                        <tr><td>Saturday</td><td>By appointment</td></tr>
                        <tr><td>Sunday</td><td>By Appointment</td></tr>
                    </table>
                </div>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--primary" style="margin-top:2rem;">Get Directions</a>
            </div>
            <div class="cb-office-map cb-reveal--right">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3388.8!2d-100.4595!3d31.4185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2s3017+Knickerbocker+Rd%2C+San+Angelo%2C+TX+76904!5e0!3m2!1sen!2sus!4v1"
                    width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy" title="Office Location">
                </iframe>
            </div>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

<?php
/**
 * Template Name: Contact Us
 *
 * @package CB_Legacy_Luxury
 */

$phone   = get_theme_mod('cb_phone', '(325) 944-9559');
$address = get_theme_mod('cb_address', '3017 Knickerbocker, San Angelo, TX 76904');
$email   = get_theme_mod('cb_email', 'info@homes-sanangelo.com');

cb_set_seo_meta([
    'title'       => 'Contact Coldwell Banker Legacy San Angelo | ' . $phone,
    'description' => "Contact Coldwell Banker Legacy at $phone or visit us at $address. San Angelo's premier real estate brokerage — luxury homes, MLS search, and expert local agents.",
    'canonical'   => get_permalink(),
]);

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Page Hero -->
<section class="cb-page-hero cb-page-hero--compact">
    <div class="cb-page-hero__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>');"></div>
    <div class="cb-page-hero__overlay"></div>
    <div class="cb-page-hero__content">
        <span class="cb-section__subtitle cb-reveal">Get In Touch</span>
        <h1 class="cb-reveal">Contact Us</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
    </div>
</section>

<!-- Contact Split Layout -->
<section class="cb-section">
    <div class="cb-container">
        <div class="cb-contact-grid">
            <!-- Form Side -->
            <div class="cb-contact-form cb-reveal--left">
                <h2 style="margin-bottom:0.5rem;">Send Us a Message</h2>
                <p style="color:var(--cb-text-muted);margin-bottom:2rem;">
                    Have a question about buying, selling, or renting? We would love to hear from you.
                </p>
                <form class="cb-form" id="cb-contact-form">
                    <div class="cb-form__row">
                        <div class="cb-form__group">
                            <label class="cb-form__label" for="contact-first">First Name</label>
                            <input type="text" id="contact-first" class="cb-form__input" required>
                        </div>
                        <div class="cb-form__group">
                            <label class="cb-form__label" for="contact-last">Last Name</label>
                            <input type="text" id="contact-last" class="cb-form__input" required>
                        </div>
                    </div>
                    <div class="cb-form__row">
                        <div class="cb-form__group">
                            <label class="cb-form__label" for="contact-email">Email Address</label>
                            <input type="email" id="contact-email" class="cb-form__input" required>
                        </div>
                        <div class="cb-form__group">
                            <label class="cb-form__label" for="contact-phone">Phone Number</label>
                            <input type="tel" id="contact-phone" class="cb-form__input">
                        </div>
                    </div>
                    <div class="cb-form__group">
                        <label class="cb-form__label" for="contact-subject">Subject</label>
                        <select id="contact-subject" class="cb-form__select">
                            <option value="">Select a topic...</option>
                            <option value="buying">Buying a Home</option>
                            <option value="selling">Selling a Home</option>
                            <option value="renting">Renting</option>
                            <option value="valuation">Home Valuation</option>
                            <option value="career">Career Opportunities</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="cb-form__group">
                        <label class="cb-form__label" for="contact-message">Message</label>
                        <textarea id="contact-message" class="cb-form__textarea" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="cb-btn cb-btn--primary cb-btn--lg" style="width:100%;">
                        Send Message
                    </button>
                </form>
            </div>

            <!-- Info Side -->
            <div class="cb-contact-info cb-reveal--right">
                <div class="cb-contact-info__card">
                    <h3 style="margin-bottom:1.5rem;">Office Information</h3>

                    <div class="cb-contact-info__item">
                        <div class="cb-contact-info__icon"><?php echo cb_get_svg_icon('map-pin'); ?></div>
                        <div>
                            <strong>Address</strong>
                            <p><?php echo esc_html($address); ?></p>
                        </div>
                    </div>

                    <div class="cb-contact-info__item">
                        <div class="cb-contact-info__icon"><?php echo cb_get_svg_icon('phone'); ?></div>
                        <div>
                            <strong>Phone</strong>
                            <p><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></p>
                        </div>
                    </div>

                    <div class="cb-contact-info__item">
                        <div class="cb-contact-info__icon"><?php echo cb_get_svg_icon('email'); ?></div>
                        <div>
                            <strong>Email</strong>
                            <p><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>
                        </div>
                    </div>

                    <div class="cb-contact-info__hours">
                        <strong>Office Hours</strong>
                        <table>
                            <tr><td>Monday - Friday</td><td>8:00 AM - 5:00 PM</td></tr>
                            <tr><td>Saturday</td><td>By appointment</td></tr>
                            <tr><td>Sunday</td><td>By Appointment</td></tr>
                        </table>
                    </div>
                </div>

                <!-- Map -->
                <div class="cb-contact-map cb-reveal">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3388.8!2d-100.4595!3d31.4185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2s3017+Knickerbocker+Rd%2C+San+Angelo%2C+TX+76904!5e0!3m2!1sen!2sus!4v1"
                        width="100%"
                        height="300"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Coldwell Banker Legacy Office Location">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

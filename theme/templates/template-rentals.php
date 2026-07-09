<?php
/**
 * Template Name: Rentals & Property Management
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'San Angelo Property Management & Rentals | Coldwell Banker Legacy',
    'description' => 'Full-service property management and rental homes in San Angelo, TX. Tenant screening, rent collection, maintenance, and luxury rentals across the Concho Valley.',
    'canonical'   => get_permalink(),
]);

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Hero -->
<section class="cb-page-hero cb-page-hero--compact">
    <div class="cb-page-hero__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>');"></div>
    <div class="cb-page-hero__overlay"></div>
    <div class="cb-page-hero__content">
        <span class="cb-section__subtitle cb-reveal">Property Management</span>
        <h1 class="cb-reveal">Rentals &amp; Management</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-reveal" style="max-width:600px;margin:1.5rem auto 0;opacity:0.9;font-size:1.125rem;">
            Professional property management and quality rental homes in San Angelo.
        </p>
    </div>
</section>

<!-- Services Grid -->
<section class="cb-section">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">For Property Owners</span>
            <h2 class="cb-section__title">Management Services</h2>
            <div class="cb-section__divider"></div>
        </div>

        <div class="cb-services-grid">
            <div class="cb-service-card cb-reveal">
                <div class="cb-service-card__front">
                    <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('home'); ?></div>
                    <h3>Tenant Placement</h3>
                </div>
                <div class="cb-service-card__back">
                    <p>Thorough tenant screening, background checks, credit verification, and reference checks to find reliable tenants for your property.</p>
                </div>
            </div>
            <div class="cb-service-card cb-reveal">
                <div class="cb-service-card__front">
                    <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('sell'); ?></div>
                    <h3>Rent Collection</h3>
                </div>
                <div class="cb-service-card__back">
                    <p>Consistent, timely rent collection with detailed financial reporting. Owners receive monthly statements and direct deposits.</p>
                </div>
            </div>
            <div class="cb-service-card cb-reveal">
                <div class="cb-service-card__front">
                    <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('office'); ?></div>
                    <h3>Maintenance</h3>
                </div>
                <div class="cb-service-card__back">
                    <p>24/7 maintenance coordination with trusted local contractors. Regular property inspections to protect your investment.</p>
                </div>
            </div>
            <div class="cb-service-card cb-reveal">
                <div class="cb-service-card__front">
                    <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('team'); ?></div>
                    <h3>Legal Compliance</h3>
                </div>
                <div class="cb-service-card__back">
                    <p>Stay compliant with Texas landlord-tenant laws. We handle lease agreements, notices, and eviction proceedings when necessary.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Available Rentals -->
<section class="cb-section cb-section--offwhite" id="cb-available-rentals">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Now Leasing</span>
            <h2 class="cb-section__title">Available Rentals</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">Browse our current rental listings in San Angelo.</p>
        </div>

        <div class="cb-property-grid">
            <?php
            $rentals = [
                ['price' => '$1,400/mo', 'address' => '1205 W Avenue N', 'beds' => 3, 'baths' => 2, 'sqft' => '1,450', 'badge' => 'Available'],
                ['price' => '$1,800/mo', 'address' => '3422 Green Meadow Dr', 'beds' => 4, 'baths' => 2, 'sqft' => '1,900', 'badge' => 'Available'],
                ['price' => '$950/mo', 'address' => '714 S Abe St', 'beds' => 2, 'baths' => 1, 'sqft' => '1,050', 'badge' => 'Available'],
            ];
            foreach ($rentals as $r) :
            ?>
            <div class="cb-property-card cb-reveal">
                <div class="cb-property-card__image">
                    <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/placeholder-property.jpg'); ?>" alt="<?php echo esc_attr($r['address']); ?>">
                    <span class="cb-property-card__badge"><?php echo esc_html($r['badge']); ?></span>
                    <div class="cb-property-card__price"><?php echo esc_html($r['price']); ?></div>
                </div>
                <div class="cb-property-card__body">
                    <h3 class="cb-property-card__address"><?php echo esc_html($r['address']); ?></h3>
                    <p class="cb-property-card__location">San Angelo, TX</p>
                    <div class="cb-property-card__details">
                        <span class="cb-property-card__detail"><?php echo cb_get_svg_icon('bed'); ?> <?php echo esc_html($r['beds']); ?> Beds</span>
                        <span class="cb-property-card__detail"><?php echo cb_get_svg_icon('bath'); ?> <?php echo esc_html($r['baths']); ?> Baths</span>
                        <span class="cb-property-card__detail"><?php echo cb_get_svg_icon('sqft'); ?> <?php echo esc_html($r['sqft']); ?> Sq Ft</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FAQ Accordion -->
<section class="cb-section" id="cb-rental-faq">
    <div class="cb-container" style="max-width:800px;">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Common Questions</span>
            <h2 class="cb-section__title">Rental FAQ</h2>
            <div class="cb-section__divider"></div>
        </div>

        <div class="cb-accordion">
            <div class="cb-accordion__item cb-reveal">
                <button class="cb-accordion__trigger">
                    <span>What is the application process?</span>
                    <span class="cb-accordion__icon">+</span>
                </button>
                <div class="cb-accordion__content">
                    <p>Submit an online application with ID, proof of income, and rental history. We run background and credit checks. Most applications are processed within 24-48 hours.</p>
                </div>
            </div>
            <div class="cb-accordion__item cb-reveal">
                <button class="cb-accordion__trigger">
                    <span>What is required to move in?</span>
                    <span class="cb-accordion__icon">+</span>
                </button>
                <div class="cb-accordion__content">
                    <p>Typically, first month's rent plus a security deposit equal to one month's rent. Some properties may require an additional pet deposit.</p>
                </div>
            </div>
            <div class="cb-accordion__item cb-reveal">
                <button class="cb-accordion__trigger">
                    <span>Are pets allowed?</span>
                    <span class="cb-accordion__icon">+</span>
                </button>
                <div class="cb-accordion__content">
                    <p>Pet policies vary by property. Many of our rentals are pet-friendly with a refundable pet deposit. Check individual listings for details.</p>
                </div>
            </div>
            <div class="cb-accordion__item cb-reveal">
                <button class="cb-accordion__trigger">
                    <span>How do I submit a maintenance request?</span>
                    <span class="cb-accordion__icon">+</span>
                </button>
                <div class="cb-accordion__content">
                    <p>Tenants can submit maintenance requests through our online portal 24/7, or call our office during business hours. Emergency maintenance is available around the clock.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cb-cta">
    <div class="cb-cta__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/cta-bg.jpg'); ?>');"></div>
    <div class="cb-cta__overlay"></div>
    <div class="cb-container">
        <div class="cb-cta__content">
            <h2 class="cb-cta__title cb-reveal">Own Rental Property?</h2>
            <p class="cb-reveal" style="max-width:560px;margin:0 auto 2rem;opacity:0.9;font-size:1.125rem;">
                Let us manage it for you. Free property management consultation available.
            </p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg cb-reveal">Get a Free Consultation</a>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

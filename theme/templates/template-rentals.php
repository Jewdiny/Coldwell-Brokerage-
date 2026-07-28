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

/* THE APPFOLIO PORTAL.
   One login (https://cbl.appfolio.com, supplied by the client) serves both
   audiences: tenants pay rent, submit maintenance requests and view their
   ledger; owners access documents and make contributions. The portal buttons
   on this page appear only when this URL is set -- a guessed or dead link on
   the page where people go to pay their rent is worse than no button, so if it
   is ever cleared these blocks fall back to the office phone, which always
   works. Configurable at Customizer > Contact Information > AppFolio Portal URL.
   Resolved once here because both the owner section and the tenant section
   below use it. */
$cb_portal = trim((string) get_theme_mod('cb_tenant_portal_url', 'https://cbl.appfolio.com'));
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

        <?php /* Existing owners -- as opposed to the prospective owners the cards
             above are pitched at -- use the same AppFolio portal as tenants to
             view statements and documents and to make contributions. Only shown
             when the portal URL is configured. */ ?>
        <?php if ($cb_portal) : ?>
        <div class="cb-owner-portal cb-reveal">
            <div class="cb-owner-portal__text">
                <h3>Already have your property with us?</h3>
                <p>Owners access statements and documents and make contributions through the AppFolio owner portal.</p>
            </div>
            <a href="<?php echo esc_url($cb_portal); ?>" class="cb-btn cb-btn--navy" target="_blank" rel="noopener">Owner Portal Login</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Tenant Resources -->
<section class="cb-section cb-section--offwhite" id="cb-tenant-resources">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">For Current Tenants</span>
            <h2 class="cb-section__title">Tenant Resources</h2>
            <div class="cb-section__divider"></div>
        </div>

        <div class="cb-tenant-actions">
            <div class="cb-tenant-action cb-reveal">
                <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('sell'); ?></div>
                <h3>Pay Rent</h3>
                <?php if ($cb_portal) : ?>
                    <p>Pay rent, view your ledger and manage your account in the resident portal, any time.</p>
                    <a href="<?php echo esc_url($cb_portal); ?>" class="cb-btn cb-btn--primary" target="_blank" rel="noopener">Log In to Pay</a>
                <?php else : ?>
                    <p>Call the office during business hours and we&rsquo;ll take your payment or set you up on the portal.</p>
                    <a href="tel:3259449559" class="cb-btn cb-btn--primary">(325) 944-9559</a>
                <?php endif; ?>
            </div>

            <div class="cb-tenant-action cb-reveal">
                <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('office'); ?></div>
                <h3>Maintenance Request</h3>
                <?php if ($cb_portal) : ?>
                    <p>Submit a non-urgent maintenance request and track its progress in the resident portal.</p>
                    <a href="<?php echo esc_url($cb_portal); ?>" class="cb-btn cb-btn--navy" target="_blank" rel="noopener">Submit in Portal</a>
                <?php else : ?>
                    <p>Something needs fixing? Tell us what and where and we&rsquo;ll get it scheduled.</p>
                    <a href="#cb-pm-form" class="cb-btn cb-btn--navy">Submit a Request</a>
                <?php endif; ?>
            </div>

            <div class="cb-tenant-action cb-reveal">
                <div class="cb-action-card__icon"><?php echo cb_get_svg_icon('phone'); ?></div>
                <h3>Emergency Maintenance</h3>
                <p>Burst pipe, no heat, no power, or anything unsafe &mdash; call us straight away, day or night.</p>
                <a href="tel:3259449559" class="cb-btn cb-btn--navy">(325) 944-9559</a>
            </div>
        </div>
    </div>
</section>

<!-- Available Rentals -->
<?php
/* THESE WERE INVENTED.
   This grid previously rendered three hard-coded listings -- 1205 W Avenue N,
   3422 Green Meadow Dr and 714 S Abe St, with prices, bed/bath counts and
   "Available" badges -- against a placeholder photo. None of them came from the
   MLS. They were sample data presented to the public as real inventory a person
   could enquire about and drive past.

   Now it queries the MLS for Coldwell Banker Legacy's OWN rentals only, per the
   brief: the brokerage's managed properties, not every rental in the county.
   Three outcomes, and each says what is actually true:

     listings   -> render them
     none       -> say we have nothing available right now
     API error  -> say the feed is unavailable and give a phone number

   The last case matters today: the Spark API key is returning 401, so this
   section currently shows the unavailable notice rather than silently rendering
   an empty grid that reads as "this brokerage has no rentals".

   The filter is exposed through the cb_rentals_filter hook so the expression
   can be corrected without a deploy -- the office name spelling and the
   rental property type both need confirming against live MLS data, which
   cannot be done while the key is disabled. */
$cb_rental_office = apply_filters('cb_rentals_office_name', 'Coldwell Banker Legacy');
$cb_rental_filter = apply_filters(
    'cb_rentals_filter',
    "StandardStatus Eq 'Active' And PropertyType Eq 'E' And ListOfficeName Contains '"
        . str_replace("'", "''", $cb_rental_office) . "'"
);

$cb_rentals = null;
if (class_exists('CB_Spark_Client')) {
    $cb_client  = new CB_Spark_Client();
    $cb_rentals = $cb_client->get_listings([
        'filter'  => $cb_rental_filter,
        'limit'   => 12,
        'orderby' => 'ListPrice asc',
    ]);
}
?>
<section class="cb-section" id="cb-available-rentals">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Now Leasing</span>
            <h2 class="cb-section__title">Available Rentals</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">Rental homes managed by Coldwell Banker Legacy in San Angelo and the Concho Valley.</p>
        </div>

        <?php if (is_array($cb_rentals) && !empty($cb_rentals)) : ?>
            <div class="cb-property-grid">
                <?php foreach ($cb_rentals as $cb_r) :
                    $cb_addr  = $cb_r['UnparsedAddress'] ?? '';
                    $cb_photo = CB_Spark_Client::photo_url($cb_r);
                    $cb_price = !empty($cb_r['ListPrice']) ? '$' . number_format((float) $cb_r['ListPrice']) . '/mo' : 'Call for price';
                ?>
                <a class="cb-property-card cb-reveal" href="<?php echo esc_url(CB_Spark_Client::detail_url($cb_r)); ?>">
                    <div class="cb-property-card__image">
                        <img src="<?php echo esc_url($cb_photo ?: CB_THEME_URI . '/assets/images/placeholder-property.jpg'); ?>" alt="<?php echo esc_attr($cb_addr); ?>" loading="lazy">
                        <span class="cb-property-card__badge">For Lease</span>
                        <div class="cb-property-card__price"><?php echo esc_html($cb_price); ?></div>
                    </div>
                    <div class="cb-property-card__body">
                        <h3 class="cb-property-card__address"><?php echo esc_html($cb_addr); ?></h3>
                        <p class="cb-property-card__location"><?php echo esc_html(trim(($cb_r['City'] ?? '') . ', ' . ($cb_r['StateOrProvince'] ?? ''), ' ,')); ?></p>
                        <div class="cb-property-card__details">
                            <span class="cb-property-card__detail"><?php echo cb_get_svg_icon('bed'); ?> <?php echo esc_html($cb_r['BedsTotal'] ?? '—'); ?> Beds</span>
                            <span class="cb-property-card__detail"><?php echo cb_get_svg_icon('bath'); ?> <?php echo esc_html($cb_r['BathsTotal'] ?? '—'); ?> Baths</span>
                            <span class="cb-property-card__detail"><?php echo cb_get_svg_icon('sqft'); ?> <?php echo esc_html(!empty($cb_r['BuildingAreaTotal']) ? number_format((float) $cb_r['BuildingAreaTotal']) : '—'); ?> Sq Ft</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

        <?php elseif (is_wp_error($cb_rentals) || $cb_rentals === null) : ?>
            <div class="cb-empty-state" style="text-align:center;max-width:38rem;margin:0 auto;padding:1rem 0;">
                <p style="font-size:1.0625rem;line-height:1.75;">
                    Our live rental listings aren&rsquo;t loading at the moment.
                </p>
                <p style="line-height:1.75;">
                    Call <a href="tel:3259449559">(325) 944-9559</a> and we&rsquo;ll tell you exactly what&rsquo;s available today.
                </p>
            </div>

        <?php else : ?>
            <div class="cb-empty-state" style="text-align:center;max-width:38rem;margin:0 auto;padding:1rem 0;">
                <p style="font-size:1.0625rem;line-height:1.75;">
                    We don&rsquo;t have any rentals available right now.
                </p>
                <p style="line-height:1.75;">
                    They move quickly &mdash; <a href="#cb-pm-form">tell us what you&rsquo;re looking for</a>
                    and we&rsquo;ll contact you the moment something fits.
                </p>
            </div>
        <?php endif; ?>
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

<!-- Property Management / Rental enquiry -->
<?php /* One form, two destinations. The radio decides whether this reaches the
     property management team or the rentals contact -- see cb_handle_pm_form().
     Previously the page's only call to action was a link to the general contact
     form, so an owner asking about management and a tenant reporting a leak both
     landed in the same sales inbox. */ ?>
<section class="cb-section cb-section--offwhite" id="cb-pm-form">
    <div class="cb-container" style="max-width:700px;">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Get in Touch</span>
            <h2 class="cb-section__title">Rentals &amp; Property Management</h2>
            <div class="cb-section__divider"></div>
        </div>

        <form class="cb-form cb-reveal" id="cb-pm-inquiry-form">
            <div class="cb-form__group">
                <span class="cb-form__label">What can we help with?</span>
                <div class="cb-form__radios" style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:0.5rem;">
                    <label style="display:flex;align-items:center;gap:0.5rem;font-weight:500;">
                        <input type="radio" name="inquiry_type" value="owner" checked>
                        I own a property to rent out
                    </label>
                    <label style="display:flex;align-items:center;gap:0.5rem;font-weight:500;">
                        <input type="radio" name="inquiry_type" value="renter">
                        I&rsquo;m looking to rent
                    </label>
                </div>
            </div>

            <div class="cb-form__row">
                <div class="cb-form__group">
                    <label class="cb-form__label" for="pm-name">Name</label>
                    <input type="text" id="pm-name" name="name" class="cb-form__input" required>
                </div>
                <div class="cb-form__group">
                    <label class="cb-form__label" for="pm-phone">Phone</label>
                    <input type="tel" id="pm-phone" name="phone" class="cb-form__input">
                </div>
            </div>

            <div class="cb-form__group">
                <label class="cb-form__label" for="pm-email">Email</label>
                <input type="email" id="pm-email" name="email" class="cb-form__input" required>
            </div>

            <div class="cb-form__group">
                <label class="cb-form__label" for="pm-address">Property or area</label>
                <input type="text" id="pm-address" name="address" class="cb-form__input" placeholder="Address, or the part of town you&rsquo;re after">
            </div>

            <div class="cb-form__group">
                <label class="cb-form__label" for="pm-message">How can we help?</label>
                <textarea id="pm-message" name="message" class="cb-form__textarea" rows="4"></textarea>
            </div>

            <button type="submit" class="cb-btn cb-btn--primary cb-btn--lg" style="width:100%;">Send</button>
            <p class="cb-form__status" id="cb-pm-status" role="status" aria-live="polite" style="margin-top:1rem;text-align:center;"></p>
        </form>
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
                Let us manage it for you &mdash; tenant screening, rent collection, maintenance and compliance, handled.
            </p>
            <a href="#cb-pm-form" class="cb-btn cb-btn--primary cb-btn--lg cb-reveal">Talk to Our Management Team</a>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

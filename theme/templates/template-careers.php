<?php
/**
 * Template Name: Careers
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'Real Estate Careers in San Angelo | Join Coldwell Banker Legacy',
    'description' => 'Now hiring San Angelo real estate agents. Join Coldwell Banker Legacy — competitive splits, luxury training, marketing support, and the strongest brand in the Concho Valley.',
    'canonical'   => get_permalink(),
]);

// JobPosting schema — eligible for Google for Jobs ("real estate jobs san angelo").
add_action('wp_head', function () {
    $posted = gmdate('Y-m-d', strtotime('-7 days'));
    $valid  = gmdate('Y-m-d', strtotime('+90 days'));
    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'JobPosting',
        'title'       => 'Real Estate Agent — San Angelo, TX',
        'description' => '<p>Coldwell Banker Legacy is seeking licensed and aspiring real estate agents to join our growing San Angelo team. We offer competitive commission splits, world-class Coldwell Banker training, marketing support, luxury-listing access through Coldwell Banker Global Luxury, and a collaborative office culture.</p><p><strong>Responsibilities:</strong> Help buyers and sellers navigate San Angelo and Concho Valley real estate transactions. Conduct showings, prepare market analyses, negotiate offers, and guide clients through closing.</p><p><strong>Qualifications:</strong> Texas Real Estate License (or willingness to obtain), strong communication, self-motivated.</p>',
        'datePosted'  => $posted,
        'validThrough' => $valid . 'T23:59:59',
        'employmentType' => 'CONTRACTOR',
        'hiringOrganization' => [
            '@type' => 'RealEstateAgent',
            '@id'   => home_url('/#brokerage'),
            'name'  => 'Coldwell Banker Legacy',
            'sameAs' => home_url('/'),
            'logo'  => CB_THEME_URI . '/assets/images/cb-logo.png',
        ],
        'jobLocation' => [
            '@type'   => 'Place',
            'address' => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => '3017 Knickerbocker',
                'addressLocality' => 'San Angelo',
                'addressRegion'   => 'TX',
                'postalCode'      => '76904',
                'addressCountry'  => 'US',
            ],
        ],
        'baseSalary' => [
            '@type'    => 'MonetaryAmount',
            'currency' => 'USD',
            'value'    => [
                '@type'    => 'QuantitativeValue',
                'unitText' => 'YEAR',
                'value'    => 'commission-based',
            ],
        ],
    ];
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
});

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Hero -->
<section class="cb-page-hero">
    <div class="cb-page-hero__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>');"></div>
    <div class="cb-page-hero__overlay"></div>
    <div class="cb-page-hero__content">
        <span class="cb-section__subtitle cb-reveal">Join Our Team</span>
        <h1 class="cb-reveal">Build Your Legacy</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-reveal" style="max-width:600px;margin:1.5rem auto 0;opacity:0.9;font-size:1.125rem;">
            Join San Angelo's premier real estate team and take your career to the next level.
        </p>
    </div>
</section>

<!-- Why Join Us -->
<section class="cb-section">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Why Coldwell Banker Legacy</span>
            <h2 class="cb-section__title">What We Offer</h2>
            <div class="cb-section__divider"></div>
        </div>

        <div class="cb-benefits-grid">
            <div class="cb-benefit-card cb-reveal">
                <div class="cb-benefit-card__number">01</div>
                <h3>Global Brand Power</h3>
                <p>Leverage the Coldwell Banker name — one of the most recognized brands in real estate worldwide.</p>
            </div>
            <div class="cb-benefit-card cb-reveal">
                <div class="cb-benefit-card__number">02</div>
                <h3>Cutting-Edge Technology</h3>
                <p>Access industry-leading tools, CRM systems, and marketing platforms to grow your business.</p>
            </div>
            <div class="cb-benefit-card cb-reveal">
                <div class="cb-benefit-card__number">03</div>
                <h3>Training & Mentorship</h3>
                <p>Comprehensive onboarding and ongoing education from experienced team leaders.</p>
            </div>
            <div class="cb-benefit-card cb-reveal">
                <div class="cb-benefit-card__number">04</div>
                <h3>Marketing Support</h3>
                <p>Professional photography, virtual tours, social media campaigns, and print materials.</p>
            </div>
            <div class="cb-benefit-card cb-reveal">
                <div class="cb-benefit-card__number">05</div>
                <h3>Luxury Certification</h3>
                <p>Opportunity to earn the prestigious Coldwell Banker Global Luxury Specialist designation.</p>
            </div>
            <div class="cb-benefit-card cb-reveal">
                <div class="cb-benefit-card__number">06</div>
                <h3>Community Roots</h3>
                <p>Be part of a team that genuinely cares about San Angelo and the Concho Valley community.</p>
            </div>
        </div>
    </div>
</section>

<!-- Agent Testimonials -->
<section class="cb-section cb-section--offwhite">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">From Our Agents</span>
            <h2 class="cb-section__title">Why They Choose Legacy</h2>
            <div class="cb-section__divider"></div>
        </div>

        <div class="cb-career-testimonials">
            <div class="cb-career-testimonial cb-reveal">
                <div class="cb-testimonial__quote-mark" style="font-size:4rem;">&ldquo;</div>
                <p style="font-size:1.125rem;font-style:italic;line-height:1.7;margin-bottom:1rem;">
                    The support system here is incredible. From day one, I had the tools and mentorship to build a thriving business. The Coldwell Banker brand opens doors that other brokerages simply cannot.
                </p>
                <strong>Kenneth Wright</strong>
                <span style="color:var(--cb-text-muted);display:block;font-size:0.875rem;">Kenneth and Mandy Team</span>
            </div>
        </div>
    </div>
</section>

<!-- Aceable Licensing Partnership -->
<section class="cb-section" id="cb-aceable">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">New to Real Estate?</span>
            <h2 class="cb-section__title">Get Your Texas Real Estate License &mdash; at a Discount</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">We've partnered with Aceable Agent &mdash; the #1-voted online real estate school &mdash; to give aspiring San Angelo agents an exclusive discount on Texas pre-licensing courses.</p>
        </div>

        <div class="cb-aceable-grid cb-reveal">
            <div class="cb-aceable-copy">
                <ul class="cb-aceable-features">
                    <li>
                        <span class="cb-aceable-features__icon">&#10003;</span>
                        <div>
                            <strong>Voted #1 Online Real Estate School</strong>
                            <span>Recognized by Fortune for the quality of its program.</span>
                        </div>
                    </li>
                    <li>
                        <span class="cb-aceable-features__icon">&#10003;</span>
                        <div>
                            <strong>Study Anytime, Anywhere</strong>
                            <span>Mobile-friendly coursework you can complete on your schedule.</span>
                        </div>
                    </li>
                    <li>
                        <span class="cb-aceable-features__icon">&#10003;</span>
                        <div>
                            <strong>Pass Guarantee + 24/7 Support</strong>
                            <span>If you don't pass, they refund your tuition. Help is always one tap away.</span>
                        </div>
                    </li>
                    <li>
                        <span class="cb-aceable-features__icon">&#10003;</span>
                        <div>
                            <strong>Exclusive Coldwell Banker Legacy Discount</strong>
                            <span>Sign up through our partner link to unlock special pricing.</span>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="cb-aceable-card">
                <div class="cb-aceable-card__partners">
                    <span class="cb-aceable-card__partner">Coldwell Banker Legacy<br><small>San Angelo</small></span>
                    <span class="cb-aceable-card__x">&times;</span>
                    <span class="cb-aceable-card__partner cb-aceable-card__partner--aceable">ACEABLE<br><small>Agent</small></span>
                </div>
                <h3 class="cb-aceable-card__title">Start Your Real Estate Career</h3>
                <p class="cb-aceable-card__desc">Begin your journey with the #1 online real estate school &mdash; on us.</p>
                <a href="https://preferred.aceable.com/r/CBLS-9Z62" target="_blank" rel="noopener sponsored" class="cb-btn cb-btn--primary cb-btn--lg cb-aceable-card__cta">
                    Sign Up Through Coldwell Banker Legacy
                </a>
                <p class="cb-aceable-card__disclosure">
                    Discount available through our partnership with Aceable Agent. preferred.aceable.com/r/CBLS-9Z62
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Application CTA -->
<section class="cb-section cb-section--navy" id="cb-career-apply">
    <div class="cb-container" style="text-align:center;">
        <span class="cb-section__subtitle cb-reveal" style="color:var(--cb-gold-light);">Ready to Start?</span>
        <h2 class="cb-reveal" style="color:var(--cb-white);margin-bottom:1.5rem;">Let's Talk About Your Future</h2>
        <p class="cb-reveal" style="max-width:600px;margin:0 auto 2.5rem;opacity:0.85;font-size:1.125rem;">
            Whether you're a seasoned agent or just starting your career, we'd love to discuss how Coldwell Banker Legacy can help you succeed.
        </p>
        <div class="cb-reveal">
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Apply Now</a>
            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', get_theme_mod('cb_phone', '(325) 944-9559'))); ?>" class="cb-btn cb-btn--outline cb-btn--lg" style="margin-left:1rem;">Call Us</a>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

<?php
/**
 * Template Name: Buyer & Seller Resources
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'San Angelo Home Buyer & Seller Resources | Coldwell Banker Legacy',
    'description' => 'Free San Angelo real estate guides — first-time home buyer steps, selling tips, mortgage calculators, and FAQs. Expert advice from Coldwell Banker Legacy.',
    'canonical'   => get_permalink(),
]);

// FAQPage schema — covers the questions buyers and sellers actually search.
add_action('wp_head', function () {
    $faqs = [
        [
            'q' => 'How do I start the home buying process in San Angelo?',
            'a' => 'Start by getting pre-approved for a mortgage so you know your budget. Then connect with a Coldwell Banker Legacy agent who knows the San Angelo market. We\'ll walk you through searching live MLS listings, scheduling showings, making offers, and closing — typically 30-45 days from offer to keys.',
        ],
        [
            'q' => 'What is the average home price in San Angelo, TX?',
            'a' => 'Median home prices in San Angelo run from $200,000 in established neighborhoods to $1M+ in luxury communities like Bentwood Country Club Estates and lakefront Lake Nasworthy. Use our live MLS search to see current pricing in any neighborhood.',
        ],
        [
            'q' => 'How long does it take to sell a home in San Angelo?',
            'a' => 'San Angelo homes typically sell in 45-90 days when properly priced. Luxury homes ($500K+) can take 60-120 days. Coldwell Banker Legacy provides a free home valuation and market analysis to price your home for the fastest sale.',
        ],
        [
            'q' => 'Do you handle property management and rentals?',
            'a' => 'Yes — Coldwell Banker Legacy offers full-service property management for residential and luxury rentals throughout San Angelo and the Concho Valley. We handle tenant screening, rent collection, maintenance, and reporting.',
        ],
        [
            'q' => 'What neighborhoods do you serve?',
            'a' => 'We serve all of San Angelo, plus Bentwood, College Hills, Lake Nasworthy, Grape Creek, Christoval, Wall, and the entire Concho Valley region. Each of our agents specializes in specific neighborhoods.',
        ],
        [
            'q' => 'How do I get a free home valuation?',
            'a' => 'Visit our home valuation page or call (325) 944-9559. We provide a complimentary comparative market analysis based on recent San Angelo sales, current market conditions, and your home\'s specific features.',
        ],
    ];
    $items = array_map(function ($f) {
        return [
            '@type'          => 'Question',
            'name'           => $f['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
        ];
    }, $faqs);
    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $items,
    ];
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
});

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Hero -->
<section class="cb-page-hero cb-page-hero--compact">
    <div class="cb-page-hero__bg" style="background-image:url('<?php echo esc_url(CB_THEME_URI . '/assets/images/hero-default.jpg'); ?>');"></div>
    <div class="cb-page-hero__overlay"></div>
    <div class="cb-page-hero__content">
        <span class="cb-section__subtitle cb-reveal">Knowledge Center</span>
        <h1 class="cb-reveal">Buyer &amp; Seller Resources</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
    </div>
</section>

<!-- Tabbed Content -->
<section class="cb-section">
    <div class="cb-container" style="max-width:900px;">
        <!-- Tab Navigation -->
        <div class="cb-tabs" id="cb-resource-tabs">
            <button class="cb-tabs__btn cb-tabs__btn--active" data-tab="buyers">Buyers</button>
            <button class="cb-tabs__btn" data-tab="sellers">Sellers</button>
            <button class="cb-tabs__btn" data-tab="loans">Home Loans</button>
            <button class="cb-tabs__btn" data-tab="relocation">Relocation</button>
            <button class="cb-tabs__btn" data-tab="tools">Move Meter</button>
            <div class="cb-tabs__indicator"></div>
        </div>

        <!-- Tab: Buyers -->
        <div class="cb-tab-content cb-tab-content--active" id="tab-buyers">
            <div class="cb-reveal">
                <h2 style="margin-bottom:1rem;">Home Buying Guide</h2>
                <p style="color:var(--cb-text-muted);font-size:1.125rem;line-height:1.8;margin-bottom:2rem;">
                    Buying a home is one of the most significant decisions you'll make. Our experienced agents are here to guide you every step of the way.
                </p>

                <div class="cb-steps">
                    <div class="cb-step">
                        <div class="cb-step__number">1</div>
                        <div class="cb-step__content">
                            <h4>Get Pre-Qualified</h4>
                            <p>Connect with a lender to understand your budget and strengthen your offer when you find the right home.</p>
                        </div>
                    </div>
                    <div class="cb-step">
                        <div class="cb-step__number">2</div>
                        <div class="cb-step__content">
                            <h4>Find Your Agent</h4>
                            <p>Partner with a Coldwell Banker Legacy agent who knows the San Angelo market and your target neighborhoods.</p>
                        </div>
                    </div>
                    <div class="cb-step">
                        <div class="cb-step__number">3</div>
                        <div class="cb-step__content">
                            <h4>Search & Tour Homes</h4>
                            <p>Browse listings online, schedule showings, and discover the property that fits your lifestyle and budget.</p>
                        </div>
                    </div>
                    <div class="cb-step">
                        <div class="cb-step__number">4</div>
                        <div class="cb-step__content">
                            <h4>Make an Offer</h4>
                            <p>Your agent will help you craft a competitive offer and negotiate terms in your best interest.</p>
                        </div>
                    </div>
                    <div class="cb-step">
                        <div class="cb-step__number">5</div>
                        <div class="cb-step__content">
                            <h4>Close & Move In</h4>
                            <p>Navigate inspections, appraisals, and closing with expert guidance. Welcome home!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Sellers -->
        <div class="cb-tab-content" id="tab-sellers">
            <div class="cb-reveal">
                <h2 style="margin-bottom:1rem;">Home Selling Guide</h2>
                <p style="color:var(--cb-text-muted);font-size:1.125rem;line-height:1.8;margin-bottom:2rem;">
                    Selling your home requires preparation, pricing strategy, and expert marketing. Here's how to maximize your sale.
                </p>

                <div class="cb-steps">
                    <div class="cb-step">
                        <div class="cb-step__number">1</div>
                        <div class="cb-step__content">
                            <h4>Get a Home Valuation</h4>
                            <p>Understand your home's current market value with a free Comparative Market Analysis from our team.</p>
                        </div>
                    </div>
                    <div class="cb-step">
                        <div class="cb-step__number">2</div>
                        <div class="cb-step__content">
                            <h4>Prepare Your Home</h4>
                            <p>Declutter, clean, and make strategic improvements. Our agents provide a customized pre-listing checklist.</p>
                        </div>
                    </div>
                    <div class="cb-step">
                        <div class="cb-step__number">3</div>
                        <div class="cb-step__content">
                            <h4>List & Market</h4>
                            <p>Professional photography, virtual tours, MLS syndication, and targeted marketing to reach qualified buyers.</p>
                        </div>
                    </div>
                    <div class="cb-step">
                        <div class="cb-step__number">4</div>
                        <div class="cb-step__content">
                            <h4>Review Offers & Negotiate</h4>
                            <p>Your agent presents all offers and negotiates the best terms, price, and timeline for your situation.</p>
                        </div>
                    </div>
                    <div class="cb-step">
                        <div class="cb-step__number">5</div>
                        <div class="cb-step__content">
                            <h4>Close the Sale</h4>
                            <p>We manage inspections, appraisals, and all closing details so you can focus on your next chapter.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Home Loans -->
        <div class="cb-tab-content" id="tab-loans">
            <div class="cb-reveal">
                <h2 style="margin-bottom:1rem;">Getting a Home Loan</h2>
                <p style="color:var(--cb-text-muted);font-size:1.125rem;line-height:1.8;margin-bottom:2rem;">
                    Understanding the mortgage process is key to a smooth home purchase. Here are essential tips for navigating your home loan.
                </p>

                <div class="cb-info-cards">
                    <div class="cb-info-card">
                        <h4 style="color:var(--cb-gold);margin-bottom:0.75rem;">Do</h4>
                        <ul style="list-style:none;line-height:2;">
                            <li>&#10003; Get pre-qualified before house hunting</li>
                            <li>&#10003; Keep your credit score stable</li>
                            <li>&#10003; Save for closing costs (2-5% of price)</li>
                            <li>&#10003; Provide all documents promptly</li>
                            <li>&#10003; Maintain steady employment</li>
                        </ul>
                    </div>
                    <div class="cb-info-card">
                        <h4 style="color:var(--cb-navy);margin-bottom:0.75rem;">Don't</h4>
                        <ul style="list-style:none;line-height:2;">
                            <li>&#10007; Change jobs during the process</li>
                            <li>&#10007; Make large purchases on credit</li>
                            <li>&#10007; Open new credit accounts</li>
                            <li>&#10007; Co-sign loans for others</li>
                            <li>&#10007; Move money between accounts unexplained</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Relocation -->
        <div class="cb-tab-content" id="tab-relocation">
            <div class="cb-reveal">
                <h2 style="margin-bottom:1rem;">Corporate Relocation</h2>
                <p style="color:var(--cb-text-muted);font-size:1.125rem;line-height:1.8;margin-bottom:2rem;">
                    Moving to San Angelo? Our relocation specialists make the transition seamless, whether you're coming from across Texas or across the country.
                </p>

                <div class="cb-info-cards">
                    <div class="cb-info-card" style="flex:1;">
                        <h4>Relocation Services Include</h4>
                        <ul style="list-style:none;line-height:2.2;margin-top:1rem;">
                            <li>&#10003; Personalized area orientation tours</li>
                            <li>&#10003; School district information and comparisons</li>
                            <li>&#10003; Neighborhood guides tailored to your lifestyle</li>
                            <li>&#10003; Temporary housing assistance</li>
                            <li>&#10003; Connection with local service providers</li>
                            <li>&#10003; Spouse/partner career assistance resources</li>
                            <li>&#10003; Coordination with your employer's relocation program</li>
                        </ul>
                    </div>
                </div>

                <div style="text-align:center;margin-top:2.5rem;">
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Contact Our Relocation Team</a>
                </div>
            </div>
        </div>

        <!-- Tab: Move Meter Tool -->
        <div class="cb-tab-content" id="tab-tools">
            <div class="cb-reveal">
                <h2 style="margin-bottom:1rem;">Compare San Angelo to Anywhere</h2>
                <p style="color:var(--cb-text-muted);font-size:1.125rem;line-height:1.8;margin-bottom:2rem;">
                    Thinking of moving to San Angelo &mdash; or just curious how the Concho Valley compares to your current city? Use the official <strong>Coldwell Banker Move Meter</strong> to compare cost of living, schools, weather, commute, and quality-of-life metrics between any two U.S. cities. Free, instant, no signup required.
                </p>

                <div class="cb-widget-frame">
                    <iframe
                        src="https://cbprod.g-co.agency/move-meter"
                        title="Coldwell Banker Move Meter"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allow="geolocation"
                    ></iframe>
                </div>

                <p style="text-align:center;margin-top:1.5rem;color:var(--cb-text-muted);font-size:0.9375rem;">
                    Powered by Coldwell Banker. Data via Sperling's BestPlaces.
                </p>
            </div>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

<?php
/**
 * Template Name: Buyer & Seller Resources
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'San Angelo Home Buyer & Seller Resources | Coldwell Banker Legacy',
    'description' => 'San Angelo real estate guides — first-time home buyer steps, selling tips, home loan advice, the quarterly market report, and FAQs from Coldwell Banker Legacy.',
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
            'a' => 'San Angelo homes typically sell in 45-90 days when properly priced. Luxury homes ($500K+) can take 60-120 days. Coldwell Banker Legacy provides a home valuation and market analysis, with no obligation, to price your home for the fastest sale.',
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
            'q' => 'How do I find out what my home is worth?',
            'a' => 'There are two ways. Start with the instant CB Estimate on our home valuation page for an automated ballpark figure. For a more accurate number, request a comparative market analysis and a local agent will price your home against recent San Angelo sales, current market conditions, and your home\'s specific features. Or call (325) 944-9559.',
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
            <button class="cb-tabs__btn" data-tab="market">Market Report</button>
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
                            <p>Start with the instant <a href="<?php echo esc_url(home_url('/home-value/')); ?>">CB Estimate</a>, then have our team prepare a Comparative Market Analysis for a more accurate figure.</p>
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
                <?php /* Matches the relocation page: this serves anyone moving
                     here, not only corporate transferees, and carries no school
                     commentary. */ ?>
                <h2 style="margin-bottom:1rem;">Relocating to San Angelo</h2>
                <p style="color:var(--cb-text-muted);font-size:1.125rem;line-height:1.8;margin-bottom:2rem;">
                    Moving to San Angelo? Our relocation specialists make the transition seamless &mdash; whether you're transferring with an employer, reporting to Goodfellow, or simply moving here by choice.
                </p>

                <div class="cb-info-cards">
                    <div class="cb-info-card" style="flex:1;">
                        <h4>Relocation Services Include</h4>
                        <ul style="list-style:none;line-height:2.2;margin-top:1rem;">
                            <li>&#10003; Personalized area orientation tours</li>
                            <li>&#10003; Neighborhood guides tailored to your lifestyle</li>
                            <li>&#10003; Temporary housing assistance</li>
                            <li>&#10003; Connection with local service providers</li>
                            <li>&#10003; Spouse/partner career assistance resources</li>
                            <li>&#10003; Coordination with your employer's relocation program</li>
                            <li>&#10003; Rental options through our property management team</li>
                        </ul>
                    </div>
                </div>

                <div style="text-align:center;margin-top:2.5rem;">
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">Contact Our Relocation Team</a>
                </div>
            </div>
        </div>

        <!-- Tab: Quarterly Market Report -->
        <?php /* Links to /market-report/ rather than embedding [cb_market_stats]
             here. The shortcode makes a blocking Spark call, and tab panels are
             all in the DOM whether or not they are opened -- so embedding would
             put an API round-trip on every Resources page load, and would render
             its "temporarily unavailable" notice inside a tab labelled Market
             Report whenever the feed is down. The live numbers stay on the page
             built for them. */ ?>
        <div class="cb-tab-content" id="tab-market">
            <div class="cb-reveal">
                <h2 style="margin-bottom:1rem;">Quarterly Market Report</h2>
                <p style="color:var(--cb-text-muted);font-size:1.125rem;line-height:1.8;margin-bottom:2rem;">
                    Every quarter we publish where the San Angelo and Concho Valley market actually stands &mdash; not national headlines, the numbers from our own MLS.
                </p>

                <div class="cb-info-cards">
                    <div class="cb-info-card" style="flex:1;">
                        <h4>What the Report Covers</h4>
                        <ul style="list-style:none;line-height:2.2;margin-top:1rem;">
                            <li>&#10003; Median sale price and how it moved this quarter</li>
                            <li>&#10003; Average days on market</li>
                            <li>&#10003; Active inventory and months of supply</li>
                            <li>&#10003; List-to-sale price ratio</li>
                            <li>&#10003; Active listing counts by community</li>
                            <li>&#10003; What it means if you're buying or selling right now</li>
                        </ul>
                    </div>
                </div>

                <div style="text-align:center;margin-top:2.5rem;display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                    <a href="<?php echo esc_url(home_url('/market-report/')); ?>" class="cb-btn cb-btn--primary cb-btn--lg">View the Current Market Report</a>
                    <a href="<?php echo esc_url(home_url('/home-value/')); ?>" class="cb-btn cb-btn--navy cb-btn--lg">What Is My Home Worth?</a>
                </div>
            </div>
        </div>

        <!-- Tab: Move Meter Tool -->
        <div class="cb-tab-content" id="tab-tools">
            <div class="cb-reveal">
                <h2 style="margin-bottom:1rem;">Compare San Angelo to Anywhere</h2>
                <p style="color:var(--cb-text-muted);font-size:1.125rem;line-height:1.8;margin-bottom:2rem;">
                    Thinking of moving to San Angelo &mdash; or just curious how the Concho Valley compares to your current city? Use the official <strong>Coldwell Banker Move Meter</strong> to compare cost of living, schools, weather, commute, and quality-of-life metrics between any two U.S. cities. Instant, no signup required.
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

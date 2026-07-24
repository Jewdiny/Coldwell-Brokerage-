<?php
/**
 * Template Name: Home Value
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => "What Is My Home Worth? San Angelo Home Valuation | Coldwell Banker Legacy",
    // "25+ years" contradicted the brokerage's own "over 35 years" used on the
    // office, about and homepage copy. One number, everywhere.
    'description' => "Two ways to value your San Angelo home: an instant CB Estimate\u{00AE}, or a custom comparative market analysis from a local agent. Serving the Concho Valley for over 35 years.",
    'canonical'   => get_permalink(),
]);

get_header();
?>

<div style="padding-top:var(--header-height);">

<!-- Hero -->
<section class="cb-valuation-hero">
    <div class="cb-valuation-hero__bg"></div>
    <div class="cb-valuation-hero__content">
        <span class="cb-section__subtitle cb-reveal" style="color:var(--cb-gold-light);">Home Valuation</span>
        <h1 class="cb-reveal" style="color:var(--cb-white);">What Is Your Home Worth?</h1>
        <div class="cb-section__divider" style="margin:1.5rem auto 0;"></div>
        <p class="cb-reveal" style="max-width:600px;margin:1.5rem auto 0;color:rgba(255,255,255,0.9);font-size:1.25rem;">
            Two ways to find out &mdash; start with the instant estimate, then go deeper.
        </p>
    </div>
</section>

<!-- Two Ways to Value Your Home -->
<?php /* The page already ran CB Estimate first and the agent CMA second, but a
     visitor landing here had no way to know a second, more accurate option
     existed until they had scrolled past the widget. This states the choice up
     front and anchors to each, so the CMA is a deliberate step rather than
     something you find by accident. */ ?>
<section class="cb-section" style="padding-bottom:0;">
    <div class="cb-container" style="max-width:900px;">
        <div class="cb-value-options">
            <a class="cb-value-option cb-reveal" href="#cb-estimate">
                <span class="cb-value-option__num">1</span>
                <h2 class="cb-value-option__title">Instant CB Estimate&reg;</h2>
                <p class="cb-value-option__desc">
                    An automated ballpark value from Coldwell Banker&rsquo;s algorithm and public
                    records. Enter your address, see a number in seconds.
                </p>
                <span class="cb-value-option__cta">Start here &darr;</span>
            </a>
            <a class="cb-value-option cb-value-option--accurate cb-reveal" href="#cb-valuation-form-section">
                <span class="cb-value-option__num">2</span>
                <h2 class="cb-value-option__title">Want a More Accurate Estimate?</h2>
                <p class="cb-value-option__desc">
                    A local Coldwell Banker Legacy agent prepares a Comparative Market Analysis
                    from live MLS comps and what your home actually has. No obligation.
                </p>
                <span class="cb-value-option__cta">Request a CMA &darr;</span>
            </a>
        </div>
    </div>
</section>

<!-- CB Estimate Instant Widget -->
<section class="cb-section" id="cb-estimate">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Option 1 &mdash; Instant Estimate</span>
            <h2 class="cb-section__title">Get Your CB Estimate&reg;</h2>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc">Powered by Coldwell Banker's proprietary algorithm and aggregated public-records data &mdash; an instant ballpark value for any San Angelo home. Enter your address below to see your estimate in seconds.</p>
        </div>

        <div class="cb-reveal cb-widget-frame cb-widget-frame--estimate">
            <iframe
                src="https://cbprod.g-co.agency/cb-estimate"
                title="CB Estimate &mdash; Instant Home Valuation"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
        </div>

        <p style="text-align:center;margin-top:1.5rem;color:var(--cb-text-muted);font-size:0.9375rem;max-width:720px;margin-left:auto;margin-right:auto;">
            CB Estimate&reg; is an automated estimate &mdash; an excellent starting point. For the most accurate value, request a custom CMA below from a local Coldwell Banker Legacy agent who knows your neighborhood.
        </p>
    </div>
</section>

<!-- Valuation Form -->
<section class="cb-section cb-section--offwhite" id="cb-valuation-form-section">
    <div class="cb-container" style="max-width:700px;">
        <div class="cb-valuation-card cb-reveal">
            <p style="text-align:center;color:var(--cb-gold);font-weight:600;letter-spacing:0.08em;text-transform:uppercase;font-size:0.8125rem;margin-bottom:0.5rem;">Option 2 &mdash; Agent Analysis</p>
            <h2 style="text-align:center;margin-bottom:0.5rem;">Want a More Accurate Estimate?</h2>
            <p style="text-align:center;color:var(--cb-text-muted);margin-bottom:2.5rem;">
                Have a local agent prepare a custom Comparative Market Analysis based on live MLS comps. No obligation.
            </p>

            <!-- Multi-step Form -->
            <form class="cb-form cb-multistep" id="cb-valuation-form">
                <!-- Progress Bar -->
                <div class="cb-multistep__progress">
                    <div class="cb-multistep__bar">
                        <div class="cb-multistep__fill" id="cb-form-progress" style="width:33%;"></div>
                    </div>
                    <div class="cb-multistep__steps">
                        <span class="cb-multistep__step cb-multistep__step--active">Property</span>
                        <span class="cb-multistep__step">Contact</span>
                        <span class="cb-multistep__step">Details</span>
                    </div>
                </div>

                <!-- Step 1: Property Address -->
                <div class="cb-multistep__panel cb-multistep__panel--active" data-step="1">
                    <div class="cb-form__group">
                        <label class="cb-form__label" for="val-address">Property Address</label>
                        <input type="text" id="val-address" class="cb-form__input cb-form__input--lg" placeholder="Enter your property address..." required>
                        <div class="cb-form__input-glow"></div>
                    </div>
                    <div class="cb-form__row">
                        <div class="cb-form__group">
                            <label class="cb-form__label" for="val-city">City</label>
                            <input type="text" id="val-city" class="cb-form__input" value="San Angelo" required>
                        </div>
                        <div class="cb-form__group">
                            <label class="cb-form__label" for="val-zip">ZIP Code</label>
                            <input type="text" id="val-zip" class="cb-form__input" placeholder="76901" required>
                        </div>
                    </div>
                    <button type="button" class="cb-btn cb-btn--primary cb-btn--lg cb-multistep__next" style="width:100%;">Continue</button>
                </div>

                <!-- Step 2: Contact Info -->
                <div class="cb-multistep__panel" data-step="2">
                    <div class="cb-form__row">
                        <div class="cb-form__group">
                            <label class="cb-form__label" for="val-name">Full Name</label>
                            <input type="text" id="val-name" class="cb-form__input" required>
                        </div>
                        <div class="cb-form__group">
                            <label class="cb-form__label" for="val-phone">Phone Number</label>
                            <input type="tel" id="val-phone" class="cb-form__input" required>
                        </div>
                    </div>
                    <div class="cb-form__group">
                        <label class="cb-form__label" for="val-email">Email Address</label>
                        <input type="email" id="val-email" class="cb-form__input" required>
                    </div>
                    <div style="display:flex;gap:1rem;">
                        <button type="button" class="cb-btn cb-btn--navy cb-multistep__prev" style="flex:1;">Back</button>
                        <button type="button" class="cb-btn cb-btn--primary cb-multistep__next" style="flex:2;">Continue</button>
                    </div>
                </div>

                <!-- Step 3: Additional Details -->
                <div class="cb-multistep__panel" data-step="3">
                    <div class="cb-form__group">
                        <label class="cb-form__label" for="val-motivation">What best describes your situation?</label>
                        <select id="val-motivation" class="cb-form__select">
                            <option value="">Select one...</option>
                            <option value="sell-asap">I Need To Sell ASAP</option>
                            <option value="considering">I'm Considering Selling</option>
                            <option value="refinance">I Want To Refinance</option>
                            <option value="sell-buy">I Need To Sell So I Can Buy</option>
                            <option value="browsing">Just Browsing</option>
                        </select>
                    </div>
                    <div class="cb-form__group">
                        <label class="cb-form__label" for="val-notes">Anything else we should know?</label>
                        <textarea id="val-notes" class="cb-form__textarea" rows="3" placeholder="Optional: Tell us about your property, timeline, or any questions..."></textarea>
                    </div>
                    <div style="display:flex;gap:1rem;">
                        <button type="button" class="cb-btn cb-btn--navy cb-multistep__prev" style="flex:1;">Back</button>
                        <button type="submit" class="cb-btn cb-btn--primary cb-btn--lg cb-multistep__submit" style="flex:2;">
                            Request My Report
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Trust Indicators -->
        <div class="cb-trust-indicators cb-reveal">
            <div class="cb-trust-item">
                <strong>No Obligation</strong>
                <p>Private and confidential. Your information is secure.</p>
            </div>
            <div class="cb-trust-item">
                <strong>Expert Analysis</strong>
                <p>Prepared by a local San Angelo agent.</p>
            </div>
            <div class="cb-trust-item">
                <strong>Fast Response</strong>
                <p>Receive your report within 24 hours.</p>
            </div>
        </div>
    </div>
</section>

<!-- Market Snapshot -->
<section class="cb-section cb-section--offwhite" id="cb-market-snapshot">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">San Angelo Real Estate</span>
            <h2 class="cb-section__title">Quarterly Market Snapshot</h2>
            <div class="cb-section__divider"></div>
        </div>

        <div class="cb-stats">
            <div class="cb-stat cb-reveal">
                <div class="cb-stat__number" data-count="245" data-suffix="">$245K</div>
                <div class="cb-stat__label">Median Home Price</div>
            </div>
            <div class="cb-stat cb-reveal">
                <div class="cb-stat__number" data-count="38" data-suffix="">38</div>
                <div class="cb-stat__label">Avg. Days on Market</div>
            </div>
            <div class="cb-stat cb-reveal">
                <div class="cb-stat__number" data-count="97" data-suffix="%">97%</div>
                <div class="cb-stat__label">List-to-Sale Ratio</div>
            </div>
            <div class="cb-stat cb-reveal">
                <div class="cb-stat__number" data-count="8" data-suffix="%">+8%</div>
                <div class="cb-stat__label">YoY Price Growth</div>
            </div>
        </div>
    </div>
</section>

</div>

<?php get_footer(); ?>

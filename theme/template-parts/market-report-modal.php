<?php
/**
 * Quarterly market report signup modal.
 *
 * Shown on community pages. The reader is already on a page about one specific
 * area, so the offer is that area's numbers rather than a generic newsletter --
 * which is the only reason anyone gives an email address on a page like this.
 *
 * Behaviour is deliberately restrained, mirroring the Property Watch pop-up on
 * the homepage:
 *   - it waits until the reader has actually engaged (scrolled past the fold),
 *     so it never lands before the page has said anything;
 *   - dismissing or submitting it sets a localStorage flag and it never returns;
 *   - it is inert markup without JS, and hidden by [hidden] until opened, so a
 *     reader with scripts off is not left with a modal stuck across the page.
 *
 * Expects $cb_name (the community's display name) in scope; falls back to the
 * Concho Valley so it cannot render a sentence with a hole in it.
 *
 * @package CB_Legacy_Luxury
 */

$cb_mr_area = isset($cb_name) && $cb_name !== '' ? $cb_name : 'the Concho Valley';
?>
<div class="cb-signup-modal" id="cb-market-report-modal" role="dialog" aria-modal="true"
     aria-labelledby="cb-mr-title" hidden>
    <div class="cb-signup-modal__scrim" data-cb-mr-close></div>
    <div class="cb-signup-modal__box" role="document">
        <button class="cb-signup-modal__x" type="button" data-cb-mr-close aria-label="Close">&times;</button>
        <span class="cb-signup-modal__eyebrow">Quarterly Market Report</span>
        <h2 class="cb-signup-modal__title" id="cb-mr-title">
            How is the <?php echo esc_html($cb_mr_area); ?> market doing?
        </h2>
        <p class="cb-signup-modal__desc">
            Every quarter we send the actual numbers &mdash; median sale price, days on market,
            what is selling and what is sitting. No sales pitch, just the data.
        </p>

        <form class="cb-signup-modal__form" id="cb-market-report-form">
            <input type="hidden" name="area" value="<?php echo esc_attr($cb_mr_area); ?>">
            <label class="cb-visually-hidden" for="cb-mr-email">Email address</label>
            <input type="email" id="cb-mr-email" name="email" class="cb-form__input"
                   placeholder="you@example.com" required autocomplete="email">
            <button type="submit" class="cb-btn cb-btn--primary">Send It</button>
        </form>

        <p class="cb-signup-modal__status" id="cb-mr-status" role="status" aria-live="polite"></p>
        <p class="cb-signup-modal__fine">Four emails a year. Unsubscribe anytime.</p>
    </div>
</div>

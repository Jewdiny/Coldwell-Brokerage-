/**
 * Auto-advance the Testimonial Tree rotator.
 *
 * The widget (cb_testimonials type="rotator") renders its own carousel with
 * prev/next arrows but does NOT advance on its own -- a visitor only sees the
 * first review unless they click. This ticks it forward on a timer so the
 * reviews rotate.
 *
 * It drives the widget's OWN next control rather than re-implementing a
 * carousel, which matters for the "new reviews appear over time" requirement:
 * the reviews are pulled live from the Testimonial Tree account (widget 71288),
 * so whatever is approved there is what rotates here. Nothing is hardcoded, and
 * a review added next month is picked up with no code change.
 *
 * Deliberately defensive: Testimonial Tree is third-party markup we don't
 * control. Every step is guarded so that if they change their DOM, this quietly
 * stops rotating rather than throwing -- the manual arrows still work either way.
 */
(function () {
  'use strict';

  // Respect reduced motion: no auto-advance. The arrows remain usable by hand.
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }

  var INTERVAL = 6000; // ms between reviews

  function advance(widget) {
    // Prefer the widget's own scoped control so multiple widgets never collide.
    var btn = widget.querySelector('.nav-arrow.right-arrow, .right-arrow, [onclick*="nextTestimonial"]');
    if (btn) { try { btn.click(); return true; } catch (e) {} }
    if (typeof window.nextTestimonial === 'function') { try { window.nextTestimonial(); return true; } catch (e) {} }
    return false;
  }

  /**
   * Render the current review into OUR OWN card.
   *
   * Testimonial Tree's widget lays itself out badly inside this narrow fixed
   * panel -- it reserved hundreds of pixels of empty white and let the arrows
   * overlap the text, and no amount of CSS wrestled it into a clean, compact
   * shape. So the TT widget is kept in the DOM purely as the live data source
   * and rotation engine (hidden via CSS on .js), and its current review is read
   * out and drawn into a small card we fully control -- which also lets the
   * review match the dark panel instead of a clashing white box.
   *
   * html.js gates the swap, so a scripts-off visitor still sees TT's own widget.
   */
  function ensureCard(widget) {
    var host = (widget.closest && widget.closest('.cb9-card__inner')) || widget.parentElement;
    if (!host) { return null; }
    var card = host.querySelector('.cb-review');
    if (!card) {
      card = document.createElement('blockquote');
      card.className = 'cb-review';
      // Deliberately NOT aria-live: announcing a fresh review every few seconds
      // would be hostile to a screen-reader user. It reads as ordinary content.
      card.innerHTML =
        '<div class="cb-review__stars" aria-hidden="true"></div>' +
        '<p class="cb-review__text"></p>' +
        '<cite class="cb-review__author"></cite>';
      // Sit it above our own "Read All Reviews" button when that is present.
      var btn = host.querySelector('.cb9-quotes__all');
      if (btn) { host.insertBefore(card, btn); } else { host.appendChild(card); }
    }
    return card;
  }

  function render(widget) {
    var textEl = widget.querySelector('.testimonial-text');
    var text = textEl ? (textEl.textContent || '').trim() : '';
    if (!text) { return; }

    var card = ensureCard(widget);
    if (!card) { return; }

    var starsEl = widget.querySelector('.star-rating');
    var authorEl = widget.querySelector('.author-name');
    var author = authorEl ? (authorEl.textContent || '').replace(/^[\s\-–—]+/, '').trim() : '';

    card.querySelector('.cb-review__stars').innerHTML = starsEl ? starsEl.innerHTML : '';
    card.querySelector('.cb-review__text').textContent = '“' + text + '”';
    card.querySelector('.cb-review__author').textContent = author ? ('— ' + author) : '';

    // Replay the slide-in: drop the class, force a reflow, add it back.
    card.classList.remove('cb-review--in');
    void card.offsetWidth;
    card.classList.add('cb-review--in');
  }

  /** Re-render whenever TT swaps the quote -- covers the timer and any manual
   *  advance -- so our card always shows what TT currently has. */
  function watch(widget) {
    if (!window.MutationObserver) { render(widget); return; }
    var scope = widget.querySelector('.testimonial-content') || widget;
    var last = '';
    var obs = new MutationObserver(function () {
      var t = widget.querySelector('.testimonial-text');
      var now = t ? (t.textContent || '').trim() : '';
      if (now && now !== last) { last = now; render(widget); }
    });
    obs.observe(scope, { childList: true, subtree: true, characterData: true });
    render(widget); // initial paint
  }

  function attach(widget) {
    // We render our own card from this widget's data; hide the original from
    // assistive tech so the review is not announced twice.
    widget.setAttribute('aria-hidden', 'true');

    var timer = null;
    function start() { if (!timer && !document.hidden) { timer = window.setInterval(function () { advance(widget); }, INTERVAL); } }
    function stop() { if (timer) { window.clearInterval(timer); timer = null; } }

    // Pause while a reader is hovering or tabbing through it, and while the tab
    // is in the background -- rotating an unseen carousel is wasted work.
    widget.addEventListener('mouseenter', stop);
    widget.addEventListener('mouseleave', start);
    widget.addEventListener('focusin', stop);
    widget.addEventListener('focusout', start);
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });

    watch(widget);
    start();
  }

  function boot() {
    var widgets = document.querySelectorAll('.cb-tt-widget--rotator');
    if (!widgets.length) { return; }

    Array.prototype.forEach.call(widgets, function (w) {
      // The arrows are injected asynchronously by the TT script, so poll briefly
      // until a control exists rather than assuming it's there at DOMContentLoaded.
      var tries = 0;
      var poll = window.setInterval(function () {
        tries++;
        if (w.querySelector('.nav-arrow') || typeof window.nextTestimonial === 'function') {
          window.clearInterval(poll);
          attach(w);
        } else if (tries > 40) { // ~20s, then give up quietly
          window.clearInterval(poll);
        }
      }, 500);
    });
  }

  if (document.readyState !== 'loading') { boot(); }
  else { document.addEventListener('DOMContentLoaded', boot); }
})();

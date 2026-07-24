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

  function attach(widget) {
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

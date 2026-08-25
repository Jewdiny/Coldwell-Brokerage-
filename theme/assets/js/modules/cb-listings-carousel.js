/**
 * Arrow controls for the featured-listings carousel (.cb9-carousel) on the
 * merged Home 10 listings panel.
 *
 * The .cb9-carousel__grid element is the [cb_listings] property grid, which CSS
 * lays out as a horizontal scroll-snap row. These buttons scroll it one card at
 * a time and disable at the ends; the whole pair hides when there is nothing to
 * scroll (e.g. the MLS feed is down and only a notice renders). Pure
 * enhancement -- swipe and trackpad already work, and if any piece is missing
 * this quietly does nothing.
 */
(function () {
  'use strict';

  function init(root) {
    var grid = root.querySelector('.cb9-carousel__grid');
    var prev = root.querySelector('[data-cb9-carousel-prev]');
    var next = root.querySelector('[data-cb9-carousel-next]');
    if (!prev || !next) { return; }
    // No grid means the MLS feed rendered a notice instead of cards -- hide the
    // arrows rather than leaving two controls with nothing to scroll.
    if (!grid) { prev.style.display = next.style.display = 'none'; return; }

    function step() {
      var first = grid.children[0];
      var w = first ? first.getBoundingClientRect().width : grid.clientWidth * 0.8;
      var cs = window.getComputedStyle(grid);
      var gap = parseFloat(cs.columnGap || cs.gap) || 16;
      return w + gap;
    }

    function update() {
      var overflow = grid.scrollWidth > grid.clientWidth + 4;
      prev.style.display = next.style.display = overflow ? '' : 'none';
      if (!overflow) { return; }
      var max = grid.scrollWidth - grid.clientWidth - 2;
      prev.disabled = grid.scrollLeft <= 2;
      next.disabled = grid.scrollLeft >= max;
    }

    prev.addEventListener('click', function () { grid.scrollBy({ left: -step(), behavior: 'smooth' }); });
    next.addEventListener('click', function () { grid.scrollBy({ left: step(), behavior: 'smooth' }); });
    grid.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
    window.addEventListener('resize', update);

    // The grid is filled by a shortcode at render; re-check after layout settles.
    update();
    window.setTimeout(update, 300);
    window.setTimeout(update, 1200);

    // --- auto-advance -----------------------------------------------------
    // The row drifts one card at a time and loops back at the end, so the map
    // above keeps cycling through the newest homes on its own. It holds still
    // while the reader is hovering, focused, mid-swipe or on a hidden tab, and
    // stays off entirely for reduced-motion users.
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var timer = null, idle = null;
    var INTERVAL = 4500;

    function overflowing() { return grid.scrollWidth > grid.clientWidth + 4; }
    function atEnd() { return grid.scrollLeft >= grid.scrollWidth - grid.clientWidth - 4; }
    function advance() {
      if (!overflowing()) { return; }
      if (atEnd()) { grid.scrollTo({ left: 0, behavior: 'smooth' }); }
      else { grid.scrollBy({ left: step(), behavior: 'smooth' }); }
    }
    function start() { if (!reduce && !timer) { timer = window.setInterval(advance, INTERVAL); } }
    function stop() { if (timer) { window.clearInterval(timer); timer = null; } }
    function nudge() {           // interaction: hold, then resume once idle
      stop();
      if (idle) { window.clearTimeout(idle); }
      idle = window.setTimeout(start, 2500);
    }

    root.addEventListener('pointerenter', stop);
    root.addEventListener('pointerleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);
    root.addEventListener('pointerdown', nudge);
    root.addEventListener('keydown', nudge);
    grid.addEventListener('wheel', nudge, { passive: true });
    prev.addEventListener('click', nudge);
    next.addEventListener('click', nudge);
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { stop(); } else { start(); }
    });

    start();
  }

  function boot() {
    Array.prototype.forEach.call(document.querySelectorAll('.cb9-carousel'), init);
  }

  if (document.readyState !== 'loading') { boot(); }
  else { document.addEventListener('DOMContentLoaded', boot); }
})();

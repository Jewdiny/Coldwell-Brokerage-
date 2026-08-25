/**
 * Centred, auto-advancing carousel for the merged Home 10 listings panel.
 *
 * The .cb9-carousel__grid element is the [cb_listings] property grid, laid out
 * by CSS as a horizontal scroll-snap row in CENTRE mode: the presented card
 * sits in the middle with its neighbours peeking in about halfway on each side.
 *
 * This module drives that row. Instead of the browser's own smooth scroll it
 * animates scrollLeft on a requestAnimationFrame with an ease-in-out curve, so
 * every move -- arrow, auto-advance, or a pin click over on the map -- glides to
 * the next card and lands with it centred. Snap is switched off for the duration
 * of each animation so mandatory snapping cannot fight the tween, then restored.
 *
 * The row also advances on its own and loops, holding still while the reader is
 * hovering, focused, mid-swipe or on a hidden tab, and staying off entirely for
 * reduced-motion users. Pure enhancement: swipe and trackpad already work, and
 * if any piece is missing this quietly does nothing.
 */
(function () {
  'use strict';

  var EASE_MS = 700;
  function easeInOutCubic(p) { return p < 0.5 ? 4 * p * p * p : 1 - Math.pow(-2 * p + 2, 3) / 2; }

  function init(root) {
    var grid = root.querySelector('.cb9-carousel__grid');
    var prev = root.querySelector('[data-cb9-carousel-prev]');
    var next = root.querySelector('[data-cb9-carousel-next]');
    if (!prev || !next) { return; }
    // No grid means the MLS feed rendered a notice instead of cards -- hide the
    // arrows rather than leaving two controls with nothing to scroll.
    if (!grid) { prev.style.display = next.style.display = 'none'; return; }

    function cards() { return grid.querySelectorAll('.cb-property-card'); }
    function maxLeft() { return grid.scrollWidth - grid.clientWidth; }
    function clampLeft(x) { return Math.max(0, Math.min(x, maxLeft())); }

    // The scrollLeft that puts `card` dead centre in the viewport.
    function centeredLeft(card) {
      var gr = grid.getBoundingClientRect();
      var cr = card.getBoundingClientRect();
      return grid.scrollLeft + (cr.left - gr.left) - (grid.clientWidth - cr.width) / 2;
    }

    // Index of the card nearest the viewport centre right now.
    function activeIndex() {
      var cs = cards();
      if (!cs.length) { return -1; }
      var mid = grid.getBoundingClientRect().left + grid.clientWidth / 2;
      var best = 0, bd = Infinity;
      for (var i = 0; i < cs.length; i++) {
        var cr = cs[i].getBoundingClientRect();
        var d = Math.abs((cr.left + cr.width / 2) - mid);
        if (d < bd) { bd = d; best = i; }
      }
      return best;
    }

    var anim = null;
    function animateTo(target) {
      target = clampLeft(target);
      if (anim) { window.cancelAnimationFrame(anim); anim = null; }
      var start = grid.scrollLeft, dist = target - start;
      if (Math.abs(dist) < 1) { return; }
      var t0 = null, prevSnap = grid.style.scrollSnapType;
      grid.style.scrollSnapType = 'none';   // don't let mandatory snap fight the tween
      function frame(ts) {
        if (t0 === null) { t0 = ts; }
        var p = Math.min(1, (ts - t0) / EASE_MS);
        grid.scrollLeft = start + dist * easeInOutCubic(p);
        if (p < 1) { anim = window.requestAnimationFrame(frame); }
        else { anim = null; grid.style.scrollSnapType = prevSnap || 'x mandatory'; }
      }
      anim = window.requestAnimationFrame(frame);
    }

    function go(i, wrap) {
      var cs = cards();
      if (!cs.length) { return; }
      if (wrap) { i = i < 0 ? cs.length - 1 : (i >= cs.length ? 0 : i); }
      else { i = Math.max(0, Math.min(i, cs.length - 1)); }
      animateTo(centeredLeft(cs[i]));
    }

    // Let the map's pin clicks centre a card through the same eased path.
    grid.__cbCenter = function (card) { animateTo(centeredLeft(card)); };

    function update() {
      var overflow = grid.scrollWidth > grid.clientWidth + 4;
      prev.style.display = next.style.display = overflow ? '' : 'none';
      if (!overflow) { return; }
      prev.disabled = grid.scrollLeft <= 2;
      next.disabled = grid.scrollLeft >= maxLeft() - 2;
    }

    // Arrows step one card and stop at the ends (no jarring rewind).
    prev.addEventListener('click', function () { go(activeIndex() - 1, false); });
    next.addEventListener('click', function () { go(activeIndex() + 1, false); });
    grid.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
    window.addEventListener('resize', update);

    update();
    window.setTimeout(update, 300);
    window.setTimeout(update, 1200);

    // --- auto-advance -----------------------------------------------------
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var timer = null, idle = null;
    var INTERVAL = 4500;

    function advance() {
      if (grid.scrollWidth <= grid.clientWidth + 4) { return; }
      go(activeIndex() + 1, true);   // loops back to the first at the end
    }
    function start() { if (!reduce && !timer) { timer = window.setInterval(advance, INTERVAL); } }
    function stop() { if (timer) { window.clearInterval(timer); timer = null; } }
    function nudge() {              // interaction: hold, then resume once idle
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

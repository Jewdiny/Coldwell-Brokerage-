/*
 * cb-webgl/cursor.js  ->  window.CBCursor
 *
 * Custom desktop cursor for the Coldwell Banker WebGL scrolltelling homepage.
 * Per CONTRACT.md section 8 + BRIEF "Cursor".
 *
 * - Dot: 6px gold, instant (tracks pointer with no lag).
 * - Ring: 32px, 1.5px gold border @50%, lerp 0.10 toward pointer.
 * - Hover .cb-btn, a, .cb-property-card, [data-cursor]: ring scale 1.6, dot fades,
 *   optional label from [data-cursor] (e.g. "View" / "Open").
 * - Dark scenes: ring/dot shift to cream/white (CBCursor.setDark(bool)).
 * - Skipped entirely on coarse pointer / touch / reduced-motion (init() returns false).
 *
 * Classic browser script. Attaches exactly ONE global: window.CBCursor.
 * Reads only globals defined earlier in load order (none required here — pure DOM/RAF).
 * No allocations inside the per-frame loop (scratch numbers reused).
 */
(function (window, document) {
  'use strict';

  // Brand tokens (do not invent colors).
  var GOLD = '#1F69FF'; // rerouted to CB Bright Blue per BRAND.md (was gold #C9A84C)
  var GOLD_RING_LIGHT = 'rgba(31, 105, 255, 0.5)'; // CB Bright Blue @50% (light scenes)
  var WHITE_RING_DARK = 'rgba(255, 255, 255, 0.6)'; // dark scenes
  var CREAM = '#F0EBE0';

  var RING_LERP = 0.10;
  var DOT_SIZE = 6;   // px
  var RING_SIZE = 32; // px
  var HOVER_SCALE = 1.6;

  // Selectors that trigger hover state.
  var HOVER_SELECTOR = '.cb-btn, a, .cb-property-card, [data-cursor]';

  var CBCursor = {
    _active: false,
    _dark: false,

    // DOM nodes
    _dot: null,
    _ring: null,
    _label: null,

    // pointer + interpolated ring position
    _mouseX: 0,
    _mouseY: 0,
    _ringX: 0,
    _ringY: 0,

    _hovering: false,
    _visible: false,
    _rafId: 0,

    // bound handlers (kept for clean removal)
    _onMove: null,
    _onOver: null,
    _onOut: null,
    _onDown: null,
    _onUp: null,
    _onEnterWin: null,
    _onLeaveWin: null,
    _tick: null
  };

  function prefersReducedMotion() {
    return !!(window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function isCoarseOrTouch() {
    // Coarse pointer (touch / stylus) or no fine pointer hover support.
    if (window.matchMedia) {
      if (window.matchMedia('(pointer: coarse)').matches) { return true; }
      if (window.matchMedia('(hover: none)').matches) { return true; }
    }
    // Fallback touch detection.
    if ('ontouchstart' in window) { return true; }
    if (navigator && (navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0)) {
      return true;
    }
    return false;
  }

  // Walk up to find the nearest element matching HOVER_SELECTOR (for [data-cursor] label).
  function closestHover(el) {
    while (el && el !== document.documentElement) {
      if (el.nodeType === 1 && el.matches && el.matches(HOVER_SELECTOR)) {
        return el;
      }
      el = el.parentNode;
    }
    return null;
  }

  function applyColors() {
    if (!CBCursor._dot || !CBCursor._ring || !CBCursor._label) { return; }
    if (CBCursor._dark) {
      CBCursor._dot.style.backgroundColor = CREAM;
      CBCursor._ring.style.borderColor = WHITE_RING_DARK;
      CBCursor._label.style.color = CREAM;
    } else {
      CBCursor._dot.style.backgroundColor = GOLD;
      CBCursor._ring.style.borderColor = GOLD_RING_LIGHT;
      CBCursor._label.style.color = GOLD;
    }
  }

  function setHover(on, targetEl) {
    if (on === CBCursor._hovering && !on) { return; }
    CBCursor._hovering = on;

    if (on) {
      CBCursor._ring.style.transform =
        'translate3d(' + CBCursor._ringX + 'px,' + CBCursor._ringY + 'px,0) ' +
        'translate(-50%,-50%) scale(' + HOVER_SCALE + ')';
      CBCursor._ring.style.opacity = '1';
      CBCursor._dot.style.opacity = '0';

      var label = '';
      if (targetEl && targetEl.getAttribute) {
        label = targetEl.getAttribute('data-cursor') || '';
      }
      if (label) {
        CBCursor._label.textContent = label;
        CBCursor._label.style.opacity = '1';
      } else {
        CBCursor._label.textContent = '';
        CBCursor._label.style.opacity = '0';
      }
    } else {
      CBCursor._ring.style.opacity = CBCursor._visible ? '1' : '0';
      CBCursor._dot.style.opacity = CBCursor._visible ? '1' : '0';
      CBCursor._label.style.opacity = '0';
      CBCursor._label.textContent = '';
    }
  }

  function setVisible(on) {
    if (on === CBCursor._visible) { return; }
    CBCursor._visible = on;
    if (!CBCursor._dot) { return; }
    CBCursor._dot.style.opacity = (on && !CBCursor._hovering) ? '1' : '0';
    CBCursor._ring.style.opacity = on ? '1' : '0';
    if (!on) {
      CBCursor._label.style.opacity = '0';
    }
  }

  function frame() {
    // Guard against a teardown race: if destroy() nulled the nodes (or init
    // never finished), do not touch DOM and do not re-arm the loop.
    if (!CBCursor._active || !CBCursor._dot || !CBCursor._ring || !CBCursor._label) {
      return;
    }

    // Ring eases toward pointer (lerp 0.10). Dot is instant.
    CBCursor._ringX += (CBCursor._mouseX - CBCursor._ringX) * RING_LERP;
    CBCursor._ringY += (CBCursor._mouseY - CBCursor._ringY) * RING_LERP;

    // Dot — instant.
    CBCursor._dot.style.transform =
      'translate3d(' + CBCursor._mouseX + 'px,' + CBCursor._mouseY + 'px,0) ' +
      'translate(-50%,-50%)';

    // Ring — eased, with hover scale.
    var scale = CBCursor._hovering ? HOVER_SCALE : 1;
    CBCursor._ring.style.transform =
      'translate3d(' + CBCursor._ringX + 'px,' + CBCursor._ringY + 'px,0) ' +
      'translate(-50%,-50%) scale(' + scale + ')';

    // Label rides with the ring.
    CBCursor._label.style.transform =
      'translate3d(' + CBCursor._ringX + 'px,' + CBCursor._ringY + 'px,0) ' +
      'translate(-50%,-50%)';

    CBCursor._rafId = window.requestAnimationFrame(CBCursor._tick);
  }

  function buildNodes() {
    var dot = document.createElement('div');
    dot.className = 'cb-cursor-dot';
    dot.setAttribute('aria-hidden', 'true');

    var ring = document.createElement('div');
    ring.className = 'cb-cursor-ring';
    ring.setAttribute('aria-hidden', 'true');

    var label = document.createElement('div');
    label.className = 'cb-cursor-label';
    label.setAttribute('aria-hidden', 'true');

    // Inline styles so the cursor works even if cb-webgl.css is missing the rules.
    var dotS = dot.style;
    dotS.position = 'fixed';
    dotS.top = '0';
    dotS.left = '0';
    dotS.width = DOT_SIZE + 'px';
    dotS.height = DOT_SIZE + 'px';
    dotS.borderRadius = '50%';
    dotS.backgroundColor = GOLD;
    dotS.pointerEvents = 'none';
    dotS.zIndex = '2147483647';
    dotS.opacity = '0';
    dotS.willChange = 'transform, opacity';
    dotS.transition = 'opacity 0.18s ease';
    dotS.mixBlendMode = 'normal';

    var ringS = ring.style;
    ringS.position = 'fixed';
    ringS.top = '0';
    ringS.left = '0';
    ringS.width = RING_SIZE + 'px';
    ringS.height = RING_SIZE + 'px';
    ringS.borderRadius = '50%';
    ringS.border = '1.5px solid ' + GOLD_RING_LIGHT;
    ringS.boxSizing = 'border-box';
    ringS.pointerEvents = 'none';
    ringS.zIndex = '2147483646';
    ringS.opacity = '0';
    ringS.willChange = 'transform, opacity';
    ringS.transition = 'opacity 0.25s ease, border-color 0.35s ease';

    var labelS = label.style;
    labelS.position = 'fixed';
    labelS.top = '0';
    labelS.left = '0';
    labelS.pointerEvents = 'none';
    labelS.zIndex = '2147483647';
    labelS.opacity = '0';
    labelS.color = GOLD;
    labelS.fontFamily = "'Josefin Sans', sans-serif";
    labelS.fontSize = '11px';
    labelS.letterSpacing = '0.12em';
    labelS.textTransform = 'uppercase';
    labelS.fontWeight = '600';
    labelS.whiteSpace = 'nowrap';
    labelS.willChange = 'transform, opacity';
    labelS.transition = 'opacity 0.18s ease';

    CBCursor._dot = dot;
    CBCursor._ring = ring;
    CBCursor._label = label;

    document.body.appendChild(ring);
    document.body.appendChild(dot);
    document.body.appendChild(label);
  }

  /**
   * Initialize the custom cursor.
   * @returns {boolean} true if active, false if skipped (coarse/touch/reduced-motion).
   */
  CBCursor.init = function () {
    if (CBCursor._active) { return true; }

    // Skip entirely on coarse pointer / touch / reduced-motion.
    if (isCoarseOrTouch() || prefersReducedMotion()) {
      return false;
    }
    if (!document.body) {
      return false;
    }

    buildNodes();

    // Initialize positions to viewport center to avoid a jump on first move.
    CBCursor._mouseX = window.innerWidth * 0.5;
    CBCursor._mouseY = window.innerHeight * 0.5;
    CBCursor._ringX = CBCursor._mouseX;
    CBCursor._ringY = CBCursor._mouseY;

    applyColors();

    CBCursor._onMove = function (e) {
      CBCursor._mouseX = e.clientX;
      CBCursor._mouseY = e.clientY;
      if (!CBCursor._visible) { setVisible(true); }
    };

    CBCursor._onOver = function (e) {
      var target = closestHover(e.target);
      if (target) {
        setHover(true, target);
      }
    };

    CBCursor._onOut = function (e) {
      // Only clear hover when leaving the hover target entirely.
      var from = closestHover(e.target);
      var to = closestHover(e.relatedTarget);
      if (from && from !== to) {
        setHover(false, null);
      }
    };

    CBCursor._onDown = function () {
      if (!CBCursor._ring) { return; }
      CBCursor._ring.style.opacity = CBCursor._visible ? '0.7' : '0';
    };

    CBCursor._onUp = function () {
      if (!CBCursor._ring) { return; }
      CBCursor._ring.style.opacity = CBCursor._visible ? '1' : '0';
    };

    CBCursor._onLeaveWin = function () { setVisible(false); };
    CBCursor._onEnterWin = function () { setVisible(true); };

    document.addEventListener('mousemove', CBCursor._onMove, { passive: true });
    document.addEventListener('mouseover', CBCursor._onOver, { passive: true });
    document.addEventListener('mouseout', CBCursor._onOut, { passive: true });
    document.addEventListener('mousedown', CBCursor._onDown, { passive: true });
    document.addEventListener('mouseup', CBCursor._onUp, { passive: true });
    // mouseleave/enter on documentElement to hide when pointer exits the window.
    document.documentElement.addEventListener('mouseleave', CBCursor._onLeaveWin, { passive: true });
    document.documentElement.addEventListener('mouseenter', CBCursor._onEnterWin, { passive: true });

    CBCursor._tick = frame;
    CBCursor._active = true;
    CBCursor._rafId = window.requestAnimationFrame(CBCursor._tick);

    return true;
  };

  /**
   * Toggle dark-scene styling (cream/white) vs light-scene styling (gold).
   * @param {boolean} isDark
   */
  CBCursor.setDark = function (isDark) {
    CBCursor._dark = !!isDark;
    if (!CBCursor._active) { return; }
    applyColors();
  };

  /**
   * Tear down the cursor and restore native pointer behavior.
   */
  CBCursor.destroy = function () {
    if (!CBCursor._active) { return; }
    CBCursor._active = false;

    if (CBCursor._rafId) {
      window.cancelAnimationFrame(CBCursor._rafId);
      CBCursor._rafId = 0;
    }

    document.removeEventListener('mousemove', CBCursor._onMove);
    document.removeEventListener('mouseover', CBCursor._onOver);
    document.removeEventListener('mouseout', CBCursor._onOut);
    document.removeEventListener('mousedown', CBCursor._onDown);
    document.removeEventListener('mouseup', CBCursor._onUp);
    if (document.documentElement) {
      document.documentElement.removeEventListener('mouseleave', CBCursor._onLeaveWin);
      document.documentElement.removeEventListener('mouseenter', CBCursor._onEnterWin);
    }

    if (CBCursor._dot && CBCursor._dot.parentNode) {
      CBCursor._dot.parentNode.removeChild(CBCursor._dot);
    }
    if (CBCursor._ring && CBCursor._ring.parentNode) {
      CBCursor._ring.parentNode.removeChild(CBCursor._ring);
    }
    if (CBCursor._label && CBCursor._label.parentNode) {
      CBCursor._label.parentNode.removeChild(CBCursor._label);
    }

    CBCursor._dot = null;
    CBCursor._ring = null;
    CBCursor._label = null;

    CBCursor._onMove = null;
    CBCursor._onOver = null;
    CBCursor._onOut = null;
    CBCursor._onDown = null;
    CBCursor._onUp = null;
    CBCursor._onEnterWin = null;
    CBCursor._onLeaveWin = null;
    CBCursor._tick = null;

    CBCursor._hovering = false;
    CBCursor._visible = false;
  };

  window.CBCursor = CBCursor;

})(window, document);

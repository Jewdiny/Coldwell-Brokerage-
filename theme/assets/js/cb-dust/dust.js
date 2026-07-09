/* =========================================================================
   dust.js — Coldwell Banker "Dust / Floating Cards" homepage (Home 3 preview)

   A dependency-free Canvas-2D engine that:
     • paints a deep-blue field with a slow, luminous ambient dust drift;
     • forms each glass card "out of dust" as it enters the viewport
       (particles converge onto the card's silhouette, then the card fades in);
     • crumbles each card "into dust" as it leaves (particles explode from the
       card's silhouette and rise/disperse into the ambient field).

   Progressive enhancement: init() adds html.cb-dust-on only after the 2D
   context starts. Without it (no JS / no canvas / reduced motion / touch), the
   default cb-dust.css renders every card static and fully legible. Exposes
   window.CBDust.init(opts). Safe to call once; re-entry is ignored.
   ========================================================================= */
(function (window, document) {
  'use strict';

  if (window.CBDust) { return; }

  var raf = window.requestAnimationFrame ||
            function (cb) { return window.setTimeout(function () { cb(now()); }, 16); };
  var caf = window.cancelAnimationFrame || window.clearTimeout;
  function now() { return (window.performance && performance.now) ? performance.now() : Date.now(); }

  // ---- module state -------------------------------------------------------
  var canvas = null, ctx = null;
  var dpr = 1, vw = 0, vh = 0;
  var grad = null;
  var ambient = [], bursts = [], pool = [];
  var sprites = [];
  var rafId = null, lastT = 0, started = false, initialized = false;

  var GLOBAL_CAP = 1200;   // hard ceiling on simultaneous burst particles
  var TAU = Math.PI * 2;

  // Brand-leaning dust palette (mostly light blues + white, occasional spark).
  var PALETTE = [
    [255, 255, 255], // white
    [255, 255, 255],
    [200, 219, 242], // tide-ish
    [184, 207, 234], // tide
    [120, 170, 235], // celestial
    [80,  140, 255]  // bright blue spark
  ];

  // ---- helpers ------------------------------------------------------------
  function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }
  function rand(a, b) { return a + Math.random() * (b - a); }
  function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

  function makeSprite(rgb) {
    var s = 48, c = document.createElement('canvas');
    c.width = c.height = s;
    var g = c.getContext('2d');
    var r = s / 2;
    var grd = g.createRadialGradient(r, r, 0, r, r, r);
    var col = rgb[0] + ',' + rgb[1] + ',' + rgb[2];
    grd.addColorStop(0,   'rgba(' + col + ',1)');
    grd.addColorStop(0.4, 'rgba(' + col + ',0.55)');
    grd.addColorStop(1,   'rgba(' + col + ',0)');
    g.fillStyle = grd;
    g.fillRect(0, 0, s, s);
    return c;
  }

  function getP() { return pool.length ? pool.pop() : {}; }
  function freeP(p) { if (pool.length < 3000) { pool.push(p); } }

  // ---- canvas sizing + background ----------------------------------------
  function resize() {
    vw = window.innerWidth;
    vh = window.innerHeight;
    dpr = Math.min(window.devicePixelRatio || 1, 1.5);  // capped: full-screen fill every frame
    canvas.width = Math.round(vw * dpr);
    canvas.height = Math.round(vh * dpr);
    canvas.style.width = vw + 'px';
    canvas.style.height = vh + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    buildGradient();
    seedAmbient();
  }

  function buildGradient() {
    // Radial from above-centre — matches the CSS body fallback gradient.
    grad = ctx.createRadialGradient(vw * 0.5, -vh * 0.25, 0, vw * 0.5, -vh * 0.25, vh * 1.5);
    grad.addColorStop(0,    '#143a8f');
    grad.addColorStop(0.32, '#0b2666');
    grad.addColorStop(0.62, '#071b4d');
    grad.addColorStop(1,    '#050f2e');
  }

  function seedAmbient() {
    var target = clamp(Math.round((vw * vh) / 24000), 40, 100);
    ambient.length = 0;
    for (var i = 0; i < target; i++) {
      ambient.push({
        x: Math.random() * vw,
        y: Math.random() * vh,
        vx: rand(-7, 7),
        vy: rand(-9, 4),
        size: rand(0.7, 2.3),
        a: rand(0.10, 0.42),
        tw: Math.random() * TAU,
        tws: rand(0.4, 1.3),
        sp: (Math.random() < 0.12) ? 5 : (Math.random() < 0.5 ? 3 : 2)
      });
    }
  }

  // ---- bursts (form = converge, crumble = explode) ------------------------
  function spawnBurst(rect, mode, isFrame) {
    if (!rect || rect.width <= 0 || rect.height <= 0) { return; }
    if (bursts.length > GLOBAL_CAP) { return; }

    var x0 = rect.left, w = rect.width;
    var top = Math.max(rect.top, -60);
    var bot = Math.min(rect.top + rect.height, vh + 60);
    if (bot <= top) { return; }

    var area = w * (bot - top);
    var target = isFrame ? 36 : 54;
    var spacing = Math.max(isFrame ? 44 : 26, Math.sqrt(area / target));
    var cx = x0 + w / 2, cy = top + (bot - top) / 2;
    var spark = isFrame ? 0.05 : 0.12;

    for (var gy = top; gy < bot; gy += spacing) {
      for (var gx = x0; gx < x0 + w; gx += spacing) {
        if (bursts.length > GLOBAL_CAP) { return; }
        var jx = (Math.random() - 0.5) * spacing;
        var jy = (Math.random() - 0.5) * spacing;
        var px = gx + jx, py = gy + jy;
        var p = getP();
        p.sprite = (Math.random() < spark) ? 5 : (Math.random() < 0.45 ? 3 : 2);
        p.size = isFrame ? rand(1.0, 2.4) : rand(1.3, 3.2);
        p.glow = p.size * 5.5;
        p.age = 0;
        p.a0 = rand(0.45, 0.95);

        if (mode === 'out') {
          // crumble: start on the silhouette, fly outward + rise, then fade.
          var ang = Math.atan2(py - cy, px - cx) + rand(-0.55, 0.55);
          var spd = rand(18, 120);
          p.x = px; p.y = py;
          p.vx = Math.cos(ang) * spd;
          p.vy = Math.sin(ang) * spd - rand(20, 75); // upward bias
          p.life = rand(0.95, 1.9);
          p.mode = 'out';
        } else {
          // form: start scattered, converge onto the silhouette, then fade.
          var a2 = Math.random() * TAU;
          var d = rand(55, 165);
          p.sx = px + Math.cos(a2) * d;
          p.sy = py + Math.sin(a2) * d - 18;
          p.x = p.sx; p.y = p.sy;
          p.tx = px; p.ty = py;
          p.life = rand(0.5, 0.95);
          p.mode = 'in';
        }
        bursts.push(p);
      }
    }
  }

  // ---- main loop ----------------------------------------------------------
  function frame(t) {
    rafId = raf(frame);
    var dt = lastT ? (t - lastT) / 1000 : 0.016;
    lastT = t;
    if (dt > 0.05) { dt = 0.05; }     // clamp after tab-switch / jank
    update(dt);
    render();
  }

  function update(dt) {
    var i, p;
    // ambient drift + wrap
    for (i = 0; i < ambient.length; i++) {
      p = ambient[i];
      p.x += p.vx * dt;
      p.y += p.vy * dt;
      p.tw += p.tws * dt;
      if (p.x < -10) { p.x = vw + 10; } else if (p.x > vw + 10) { p.x = -10; }
      if (p.y < -10) { p.y = vh + 10; } else if (p.y > vh + 10) { p.y = -10; }
    }
    // bursts
    for (i = bursts.length - 1; i >= 0; i--) {
      p = bursts[i];
      p.age += dt;
      var lt = p.age / p.life;
      if (lt >= 1) { bursts.splice(i, 1); freeP(p); continue; }
      if (p.mode === 'out') {
        p.x += p.vx * dt;
        p.y += p.vy * dt;
        p.vy += 6 * dt;           // gentle settle of the rise
        p.vx *= (1 - 0.6 * dt);   // drag
      } else {
        var e = easeOutCubic(lt);
        p.x = p.sx + (p.tx - p.sx) * e;
        p.y = p.sy + (p.ty - p.sy) * e;
      }
    }
  }

  function render() {
    // opaque blue field (also clears previous frame — no trails)
    ctx.globalCompositeOperation = 'source-over';
    ctx.globalAlpha = 1;
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, vw, vh);

    ctx.globalCompositeOperation = 'lighter';  // luminous, glowing dust
    var i, p, sp, a;
    for (i = 0; i < ambient.length; i++) {
      p = ambient[i];
      a = p.a * (0.6 + 0.4 * Math.sin(p.tw));
      if (a <= 0.01) { continue; }
      sp = sprites[p.sp];
      ctx.globalAlpha = a;
      var g0 = p.size * 5;
      ctx.drawImage(sp, p.x - g0 / 2, p.y - g0 / 2, g0, g0);
    }
    for (i = 0; i < bursts.length; i++) {
      p = bursts[i];
      var lt = p.age / p.life;
      if (p.mode === 'out') {
        a = p.a0 * (1 - lt);
      } else {
        a = p.a0 * (1 - lt * lt);   // fade as it lands; card CSS takes over
      }
      if (a <= 0.01) { continue; }
      sp = sprites[p.sprite];
      ctx.globalAlpha = a;
      ctx.drawImage(sp, p.x - p.glow / 2, p.y - p.glow / 2, p.glow, p.glow);
    }
    ctx.globalAlpha = 1;
    ctx.globalCompositeOperation = 'source-over';
  }

  function start() { if (!started) { started = true; lastT = 0; rafId = raf(frame); } }
  function stop() { if (started) { started = false; caf(rafId); rafId = null; } }

  // ---- card form / crumble orchestration ----------------------------------
  function staggerFor(rectTop) {
    return clamp((rectTop / vh) * 200, 0, 260);
  }

  function clearForming(el) {
    el.classList.remove('is-forming');
  }

  function formCard(el, delay) {
    el.classList.remove('is-crumbling');
    el.classList.add('is-forming');
    el.style.transitionDelay = (delay / 1000) + 's';
    // force the start state to register before flipping to formed
    raf(function () { el.classList.add('is-formed'); });
    var isFrame = el.hasAttribute('data-dust-frame');
    window.setTimeout(function () {
      if (!document.hidden) { spawnBurst(el.getBoundingClientRect(), 'in', isFrame); }
    }, delay);
    window.setTimeout(function () { clearForming(el); }, delay + 950);
    countUp(el);
  }

  function crumbleCard(el, delay) {
    el.classList.remove('is-formed');
    el.classList.add('is-crumbling');
    el.style.transitionDelay = (delay / 1000) + 's';
    var isFrame = el.hasAttribute('data-dust-frame');
    window.setTimeout(function () {
      if (!document.hidden) { spawnBurst(el.getBoundingClientRect(), 'out', isFrame); }
    }, delay);
  }

  function countUp(el) {
    var nums = el.querySelectorAll('.cb-dust-stat__num[data-count]');
    if (!nums.length) { return; }
    for (var i = 0; i < nums.length; i++) {
      (function (n) {
        if (n.__counted) { return; }
        n.__counted = true;
        var target = parseInt(n.getAttribute('data-count'), 10) || 0;
        var t0 = now(), dur = 1600;
        (function step(t) {
          var pr = clamp((t - t0) / dur, 0, 1);
          n.textContent = Math.round(target * easeOutCubic(pr)).toLocaleString();
          if (pr < 1) { raf(step); }
        })(t0);
      })(nums[i]);
    }
  }

  function observeCards() {
    var cards = document.querySelectorAll('.cb-dust-card[data-dust]');
    if (!cards.length) { return; }

    if (!('IntersectionObserver' in window)) {
      for (var k = 0; k < cards.length; k++) {
        cards[k].classList.add('is-formed');
        cards[k].__dustState = 'formed';
      }
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      for (var j = 0; j < entries.length; j++) {
        var e = entries[j], el = e.target;
        var delay = staggerFor(e.boundingClientRect.top);
        if (e.isIntersecting) {
          if (el.__dustState !== 'formed') { formCard(el, delay); el.__dustState = 'formed'; }
        } else {
          if (el.__dustState === 'formed') { crumbleCard(el, delay); el.__dustState = 'crumbled'; }
        }
      }
    }, { root: null, rootMargin: '-8% 0px -14% 0px', threshold: [0, 0.18] });

    for (var c = 0; c < cards.length; c++) { cards[c].__dustState = 'pre'; io.observe(cards[c]); }

    // Safety sweep: anything still hidden but actually on-screen after load
    // (e.g. an observer hiccup) gets formed so nothing is ever stuck invisible.
    window.setTimeout(function () {
      for (var s = 0; s < cards.length; s++) {
        var el = cards[s];
        if (el.__dustState === 'pre') {
          var r = el.getBoundingClientRect();
          if (r.top < vh && r.bottom > 0) { formCard(el, 0); el.__dustState = 'formed'; }
        }
      }
    }, 1400);
  }

  function observeSections() {
    var dots = document.querySelectorAll('.cb-dust-nav__dot');
    var sections = document.querySelectorAll('[data-dust-section]');
    if (sections.length && 'IntersectionObserver' in window && dots.length) {
      var sio = new IntersectionObserver(function (entries) {
        for (var i = 0; i < entries.length; i++) {
          if (entries[i].isIntersecting) {
            var idx = entries[i].target.getAttribute('data-dust-section');
            for (var d = 0; d < dots.length; d++) {
              dots[d].classList.toggle('is-active', dots[d].getAttribute('data-dust-to') === idx);
            }
          }
        }
      }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });
      for (var s = 0; s < sections.length; s++) { sio.observe(sections[s]); }
    }
    for (var k = 0; k < dots.length; k++) {
      dots[k].addEventListener('click', function () {
        var t = document.querySelector('[data-dust-section="' + this.getAttribute('data-dust-to') + '"]');
        if (t) { t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
      });
    }
  }

  // ---- init ---------------------------------------------------------------
  function init(opts) {
    if (initialized) { return true; }
    opts = opts || {};

    // Respect reduced motion — leave the static, legible fallback in place.
    try {
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return false;
      }
    } catch (e) {}

    var sel = opts.canvas || '#cb-dust-canvas';
    canvas = (typeof sel === 'string') ? document.querySelector(sel) : sel;
    if (!canvas || !canvas.getContext) { return false; }
    ctx = canvas.getContext('2d');
    if (!ctx) { return false; }

    try {
      for (var i = 0; i < PALETTE.length; i++) { sprites[i] = makeSprite(PALETTE[i]); }

      document.documentElement.classList.add('cb-dust-on');
      resize();
      observeCards();
      observeSections();

      var rt;
      window.addEventListener('resize', function () {
        window.clearTimeout(rt);
        rt = window.setTimeout(resize, 180);
      }, { passive: true });

      document.addEventListener('visibilitychange', function () {
        if (document.hidden) { stop(); } else { start(); }
      });

      start();
      initialized = true;
      return true;
    } catch (err) {
      document.documentElement.classList.remove('cb-dust-on');
      stop();
      if (window.console) { console.warn('[cb-dust] init failed; using static fallback.', err); }
      return false;
    }
  }

  window.CBDust = { init: init };

})(window, document);

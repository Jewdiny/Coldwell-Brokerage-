/* =========================================================================
   fusion.js — Coldwell Banker "Fusion" homepage (Home 4 preview)

   Combines the best of the two earlier concepts:
     • Home 2 (cinematic WebGL): a real Three.js 3D particle nebula with depth,
       parallax, additive glow + the custom cursor.
     • Home 3 (dust cards): floating glass info-cards that FORM from dust and
       CRUMBLE into dust on scroll — but here the dust is injected into the SAME
       3D particle field, so a card literally breaks apart into the nebula and
       the nebula re-gathers into the next card. One luminous system, not two.

   Smoothness lesson carried over: cards never use backdrop-filter; the field is
   a single Points draw call (ambient particles are static + GPU-twinkled, only
   live burst particles are CPU-updated), so scrolling stays at 60fps.

   Progressive enhancement: init() adds html.cb-fusion-on only after the WebGL
   context starts. Otherwise the static, legible glass-card fallback remains
   (cb-fusion.css). Exposes window.CBFusion.init(opts). Requires window.THREE.
   ========================================================================= */
(function (window, document) {
  'use strict';

  if (window.CBFusion) { return; }

  var THREE = window.THREE;
  var raf = window.requestAnimationFrame || function (cb) { return setTimeout(function () { cb(now()); }, 16); };
  var caf = window.cancelAnimationFrame || window.clearTimeout;
  function now() { return (window.performance && performance.now) ? performance.now() : Date.now(); }

  // ---- config -------------------------------------------------------------
  var AMB = 1600;            // ambient (static) particles
  var BURST = 1300;          // reserved burst slots
  var COUNT = AMB + BURST;
  var CAMZ = 22;
  var FOV = 58;
  var TAU = Math.PI * 2;

  // Brand dust palette as 0..1 rgb (mostly light blues + white; sparks brighter).
  var PAL = [
    [1.00, 1.00, 1.00],   // white
    [1.00, 1.00, 1.00],
    [0.78, 0.86, 0.96],   // tide
    [0.72, 0.81, 0.92],
    [0.36, 0.56, 0.87],   // celestial
    [0.16, 0.45, 1.00]    // bright blue spark
  ];

  // ---- state --------------------------------------------------------------
  var canvas, renderer, scene, camera, geom, material, points;
  var pos, color, size, alpha, phase;        // Float32 buffers
  var posAttr, alpAttr;
  // burst CPU state (index 0..BURST-1 maps to particle index AMB+i)
  var bMode = new Float32Array(BURST);       // 0 idle, 1 out, 2 in
  var bAge = new Float32Array(BURST), bLife = new Float32Array(BURST), bA0 = new Float32Array(BURST);
  var bsx = new Float32Array(BURST), bsy = new Float32Array(BURST), bsz = new Float32Array(BURST);
  var btx = new Float32Array(BURST), bty = new Float32Array(BURST), btz = new Float32Array(BURST);
  var bvx = new Float32Array(BURST), bvy = new Float32Array(BURST), bvz = new Float32Array(BURST);
  var bPtr = 0;

  var vw = 0, vh = 0, dpr = 1, halfW = 0, halfH = 0;
  var rafId = null, lastT = 0, uTime = 0, started = false, initialized = false;
  var mouseNX = 0, mouseNY = 0, camX = 0, camY = 0, scrollFrac = 0;
  var _fpsAcc = 0, _fpsN = 0, _degraded = false;   // one-shot adaptive quality

  var _v = null, _dir = null, _out = null; // scratch THREE.Vector3

  function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }
  function rand(a, b) { return a + Math.random() * (b - a); }
  function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

  // ---- shaders ------------------------------------------------------------
  var VERT = [
    'attribute vec3 aColor;',
    'attribute float aSize;',
    'attribute float aAlpha;',
    'attribute float aPhase;',
    'uniform float uTime;',
    'uniform float uPixelRatio;',
    'varying float vAlpha;',
    'varying vec3 vColor;',
    'void main(){',
    '  vec4 mv = modelViewMatrix * vec4(position, 1.0);',
    '  gl_Position = projectionMatrix * mv;',
    '  float dist = -mv.z;',
    '  gl_PointSize = aSize * uPixelRatio * (120.0 / max(dist, 1.0));',
    '  float tw = 0.62 + 0.38 * sin(uTime * 0.9 + aPhase);',
    '  float depthFade = clamp((48.0 - dist) / 44.0, 0.0, 1.0);',
    '  vAlpha = aAlpha * tw * depthFade;',
    '  vColor = aColor;',
    '}'
  ].join('\n');

  var FRAG = [
    'precision mediump float;',
    'varying float vAlpha;',
    'varying vec3 vColor;',
    'void main(){',
    '  vec2 uv = gl_PointCoord - 0.5;',
    '  float d = dot(uv, uv);',
    '  if (d > 0.25) discard;',
    '  float a = smoothstep(0.25, 0.0, d);',
    '  gl_FragColor = vec4(vColor, a * vAlpha);',
    '}'
  ].join('\n');

  // ---- frustum + ambient seeding -----------------------------------------
  function computeExtents() {
    halfH = Math.tan((FOV * 0.5) * Math.PI / 180) * CAMZ;
    halfW = halfH * (vw / vh);
  }

  function seedAmbient() {
    var i, c;
    for (i = 0; i < AMB; i++) {
      pos[i * 3]     = rand(-halfW * 1.3, halfW * 1.3);
      pos[i * 3 + 1] = rand(-halfH * 1.7, halfH * 1.7);
      pos[i * 3 + 2] = rand(-14, 6);
      c = PAL[(Math.random() < 0.16) ? (2 + (Math.random() * 4 | 0)) : (Math.random() < 0.5 ? 0 : 2)];
      color[i * 3] = c[0]; color[i * 3 + 1] = c[1]; color[i * 3 + 2] = c[2];
      size[i]  = rand(0.7, 2.1);
      alpha[i] = rand(0.35, 0.9);
      phase[i] = Math.random() * TAU;
    }
  }

  function initBurstBuffers() {
    for (var i = 0; i < BURST; i++) {
      var k = AMB + i;
      pos[k * 3] = 0; pos[k * 3 + 1] = 0; pos[k * 3 + 2] = -999; // parked off-screen
      color[k * 3] = 1; color[k * 3 + 1] = 1; color[k * 3 + 2] = 1;
      size[k] = 1; alpha[k] = 0; phase[k] = Math.random() * TAU;
      bMode[i] = 0; bLife[i] = 0; bAge[i] = 0;
    }
  }

  // ---- screen px -> world point on the z=0 plane (current camera) ---------
  function screenToWorld(px, py) {
    camera.updateMatrixWorld();
    _v.set((px / vw) * 2 - 1, -((py / vh) * 2 - 1), 0.5).unproject(camera);
    _dir.copy(_v).sub(camera.position).normalize();
    var t = (0 - camera.position.z) / _dir.z;
    _out.copy(camera.position).add(_dir.multiplyScalar(t));
    return _out;
  }

  // ---- bursts -------------------------------------------------------------
  function spawnBurst(rect, mode, isFrame) {
    if (!rect || rect.width <= 0 || rect.height <= 0) { return; }
    var x0 = rect.left, w = rect.width;
    var top = Math.max(rect.top, -60);
    var bot = Math.min(rect.top + rect.height, vh + 60);
    if (bot <= top) { return; }

    var area = w * (bot - top);
    var target = isFrame ? 34 : 50;
    var spacing = Math.max(isFrame ? 46 : 28, Math.sqrt(area / target));
    var cx = x0 + w / 2, cy = top + (bot - top) / 2;
    var cWorld = screenToWorld(cx, cy);
    var ccx = cWorld.x, ccy = cWorld.y;
    var spark = isFrame ? 0.05 : 0.13;

    for (var gy = top; gy < bot; gy += spacing) {
      for (var gx = x0; gx < x0 + w; gx += spacing) {
        var px = gx + (Math.random() - 0.5) * spacing;
        var py = gy + (Math.random() - 0.5) * spacing;
        var wld = screenToWorld(px, py);
        var i = bPtr; bPtr = (bPtr + 1) % BURST;
        var k = AMB + i;

        var c = PAL[(Math.random() < spark) ? 5 : (Math.random() < 0.5 ? 0 : 2 + (Math.random() * 2 | 0))];
        color[k * 3] = c[0]; color[k * 3 + 1] = c[1]; color[k * 3 + 2] = c[2];
        size[k] = isFrame ? rand(0.6, 1.6) : rand(0.8, 2.1);
        bA0[i] = rand(0.5, 1.0);
        bAge[i] = 0;

        if (mode === 'out') {
          bMode[i] = 1;
          bLife[i] = rand(1.0, 1.9);
          pos[k * 3] = wld.x; pos[k * 3 + 1] = wld.y; pos[k * 3 + 2] = rand(-1, 1.5);
          var ang = Math.atan2(wld.y - ccy, wld.x - ccx) + rand(-0.6, 0.6);
          var spd = rand(1.5, 7.0);
          bvx[i] = Math.cos(ang) * spd;
          bvy[i] = Math.sin(ang) * spd + rand(1.0, 3.5); // rise
          bvz[i] = rand(-2.5, 3.5);                      // pop in depth
        } else {
          bMode[i] = 2;
          bLife[i] = rand(0.55, 1.0);
          btx[i] = wld.x; bty[i] = wld.y; btz[i] = rand(-0.8, 0.8);
          var a2 = Math.random() * TAU, rr = rand(2.5, 7.5);
          bsx[i] = wld.x + Math.cos(a2) * rr;
          bsy[i] = wld.y + Math.sin(a2) * rr;
          bsz[i] = rand(-6, 4);
          pos[k * 3] = bsx[i]; pos[k * 3 + 1] = bsy[i]; pos[k * 3 + 2] = bsz[i];
        }
        alpha[k] = bA0[i];
      }
    }
    posAttr.needsUpdate = true;
    alpAttr.needsUpdate = true;
  }

  function updateBursts(dt) {
    var any = false;
    for (var i = 0; i < BURST; i++) {
      if (bMode[i] === 0) { continue; }
      bAge[i] += dt;
      var t = bAge[i] / bLife[i];
      var k = AMB + i;
      if (t >= 1) {
        bMode[i] = 0; alpha[k] = 0; pos[k * 3 + 2] = -999;
        any = true; continue;
      }
      if (bMode[i] === 1) {
        pos[k * 3]     += bvx[i] * dt;
        pos[k * 3 + 1] += bvy[i] * dt;
        pos[k * 3 + 2] += bvz[i] * dt;
        bvy[i] -= 1.4 * dt;                 // settle the rise
        bvx[i] *= (1 - 0.5 * dt); bvz[i] *= (1 - 0.5 * dt);
        alpha[k] = bA0[i] * (1 - t);
      } else {
        var e = easeOutCubic(t);
        pos[k * 3]     = bsx[i] + (btx[i] - bsx[i]) * e;
        pos[k * 3 + 1] = bsy[i] + (bty[i] - bsy[i]) * e;
        pos[k * 3 + 2] = bsz[i] + (btz[i] - bsz[i]) * e;
        alpha[k] = bA0[i] * (1 - t * t);
      }
      any = true;
    }
    if (any) { posAttr.needsUpdate = true; alpAttr.needsUpdate = true; }
  }

  // ---- loop ---------------------------------------------------------------
  function frame(t) {
    rafId = raf(frame);
    var dt = lastT ? (t - lastT) / 1000 : 0.016;
    lastT = t;
    if (dt > 0.05) { dt = 0.05; }
    uTime += dt;
    material.uniforms.uTime.value = uTime;

    // Adaptive quality: if a machine sustains < ~42fps over the first ~90 frames,
    // drop the pixel ratio once to claw back GPU headroom (keeps scroll smooth).
    if (!_degraded) {
      _fpsAcc += dt; _fpsN++;
      if (_fpsN >= 90) {
        var avg = _fpsAcc / _fpsN;
        if (avg > 0.024 && dpr > 1.0) {
          dpr = 1.0; renderer.setPixelRatio(dpr); material.uniforms.uPixelRatio.value = dpr;
        }
        _degraded = true;
      }
    }

    // camera parallax (mouse + slow drift + scroll), no rotation = clean pan.
    var driftX = Math.sin(uTime * 0.13) * 1.1;
    var driftY = Math.cos(uTime * 0.10) * 0.8;
    var tX = mouseNX * 2.6 + driftX;
    var tY = mouseNY * 1.9 + driftY - (scrollFrac - 0.5) * 6.0;
    camX += (tX - camX) * 0.045;
    camY += (tY - camY) * 0.045;
    camera.position.x = camX;
    camera.position.y = camY;

    updateBursts(dt);
    renderer.render(scene, camera);
  }
  function start() { if (!started) { started = true; lastT = 0; rafId = raf(frame); } }
  function stop() { if (started) { started = false; caf(rafId); rafId = null; } }

  // ---- resize -------------------------------------------------------------
  function resize() {
    vw = window.innerWidth; vh = window.innerHeight;
    dpr = Math.min(window.devicePixelRatio || 1, 1.25);
    renderer.setPixelRatio(dpr);
    renderer.setSize(vw, vh, false);
    camera.aspect = vw / vh;
    camera.updateProjectionMatrix();
    material.uniforms.uPixelRatio.value = dpr;
    computeExtents();
  }

  // ---- card form / crumble (ported from Home 3, bursts → 3D field) --------
  function staggerFor(rectTop) { return clamp((rectTop / vh) * 200, 0, 260); }

  function formCard(el, delay) {
    el.classList.remove('is-crumbling');
    el.classList.add('is-forming');
    el.style.transitionDelay = (delay / 1000) + 's';
    raf(function () { el.classList.add('is-formed'); });
    var isFrame = el.hasAttribute('data-fusion-frame');
    setTimeout(function () { if (!document.hidden) { spawnBurst(el.getBoundingClientRect(), 'in', isFrame); } }, delay);
    setTimeout(function () { el.classList.remove('is-forming'); }, delay + 950);
    countUp(el);
  }
  function crumbleCard(el, delay) {
    el.classList.remove('is-formed');
    el.classList.add('is-crumbling');
    el.style.transitionDelay = (delay / 1000) + 's';
    var isFrame = el.hasAttribute('data-fusion-frame');
    setTimeout(function () { if (!document.hidden) { spawnBurst(el.getBoundingClientRect(), 'out', isFrame); } }, delay);
  }
  function countUp(el) {
    var nums = el.querySelectorAll('.cb-fusion-stat__num[data-count]');
    if (!nums.length) { return; }
    for (var i = 0; i < nums.length; i++) {
      (function (n) {
        if (n.__c) { return; } n.__c = true;
        var target = parseInt(n.getAttribute('data-count'), 10) || 0, t0 = now(), dur = 1600;
        (function step(t) {
          var p = clamp((t - t0) / dur, 0, 1);
          n.textContent = Math.round(target * easeOutCubic(p)).toLocaleString();
          if (p < 1) { raf(step); }
        })(t0);
      })(nums[i]);
    }
  }

  function observeCards() {
    var cards = document.querySelectorAll('.cb-fusion-card[data-fusion]');
    if (!cards.length) { return; }
    if (!('IntersectionObserver' in window)) {
      for (var k = 0; k < cards.length; k++) { cards[k].classList.add('is-formed'); cards[k].__s = 'formed'; }
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      for (var j = 0; j < entries.length; j++) {
        var e = entries[j], el = e.target, delay = staggerFor(e.boundingClientRect.top);
        if (e.isIntersecting) { if (el.__s !== 'formed') { formCard(el, delay); el.__s = 'formed'; } }
        else { if (el.__s === 'formed') { crumbleCard(el, delay); el.__s = 'crumbled'; } }
      }
    }, { rootMargin: '-8% 0px -14% 0px', threshold: [0, 0.18] });
    for (var c = 0; c < cards.length; c++) { cards[c].__s = 'pre'; io.observe(cards[c]); }
    setTimeout(function () {
      for (var s = 0; s < cards.length; s++) {
        var el = cards[s];
        if (el.__s === 'pre') { var r = el.getBoundingClientRect(); if (r.top < vh && r.bottom > 0) { formCard(el, 0); el.__s = 'formed'; } }
      }
    }, 1400);
  }

  function observeSections() {
    var dots = document.querySelectorAll('.cb-fusion-nav__dot');
    var sections = document.querySelectorAll('[data-fusion-section]');
    if (sections.length && 'IntersectionObserver' in window && dots.length) {
      var sio = new IntersectionObserver(function (entries) {
        for (var i = 0; i < entries.length; i++) {
          if (entries[i].isIntersecting) {
            var idx = entries[i].target.getAttribute('data-fusion-section');
            for (var d = 0; d < dots.length; d++) { dots[d].classList.toggle('is-active', dots[d].getAttribute('data-fusion-to') === idx); }
          }
        }
      }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });
      for (var s = 0; s < sections.length; s++) { sio.observe(sections[s]); }
    }
    for (var k = 0; k < dots.length; k++) {
      dots[k].addEventListener('click', function () {
        var t = document.querySelector('[data-fusion-section="' + this.getAttribute('data-fusion-to') + '"]');
        if (t) { t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
      });
    }
  }

  function trackScroll() {
    function upd() {
      var max = Math.max(1, (document.documentElement.scrollHeight || 0) - window.innerHeight);
      scrollFrac = clamp((window.pageYOffset || 0) / max, 0, 1);
    }
    window.addEventListener('scroll', upd, { passive: true });
    upd();
  }

  // ---- init ---------------------------------------------------------------
  function init(opts) {
    if (initialized) { return true; }
    opts = opts || {};
    if (!THREE) { return false; }
    try {
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return false; }
    } catch (e) {}

    var sel = opts.canvas || '#cb-fusion-canvas';
    canvas = (typeof sel === 'string') ? document.querySelector(sel) : sel;
    if (!canvas) { return false; }

    try {
      // alpha:true + default premultipliedAlpha (true) is the correct combo for
      // additive glow over the CSS gradient — premultipliedAlpha:false multiplies
      // the glow back down by its own alpha and renders it nearly invisible.
      renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: false, powerPreference: 'high-performance' });
    } catch (e) { renderer = null; }
    if (!renderer) { return false; }

    try {
      _v = new THREE.Vector3(); _dir = new THREE.Vector3(); _out = new THREE.Vector3();
      renderer.setClearColor(0x000000, 0);
      vw = window.innerWidth; vh = window.innerHeight;
      dpr = Math.min(window.devicePixelRatio || 1, 1.25);

      scene = new THREE.Scene();
      camera = new THREE.PerspectiveCamera(FOV, vw / vh, 0.1, 140);
      camera.position.set(0, 0, CAMZ);
      computeExtents();

      pos = new Float32Array(COUNT * 3);
      color = new Float32Array(COUNT * 3);
      size = new Float32Array(COUNT);
      alpha = new Float32Array(COUNT);
      phase = new Float32Array(COUNT);
      seedAmbient();
      initBurstBuffers();

      geom = new THREE.BufferGeometry();
      posAttr = new THREE.BufferAttribute(pos, 3);
      alpAttr = new THREE.BufferAttribute(alpha, 1);
      posAttr.setUsage(THREE.DynamicDrawUsage);
      alpAttr.setUsage(THREE.DynamicDrawUsage);
      geom.setAttribute('position', posAttr);
      geom.setAttribute('aColor', new THREE.BufferAttribute(color, 3));
      geom.setAttribute('aSize', new THREE.BufferAttribute(size, 1));
      geom.setAttribute('aAlpha', alpAttr);
      geom.setAttribute('aPhase', new THREE.BufferAttribute(phase, 1));

      material = new THREE.ShaderMaterial({
        uniforms: { uTime: { value: 0 }, uPixelRatio: { value: dpr } },
        vertexShader: VERT, fragmentShader: FRAG,
        transparent: true, blending: THREE.AdditiveBlending,
        depthTest: false, depthWrite: false
      });

      points = new THREE.Points(geom, material);
      points.frustumCulled = false;
      scene.add(points);

      renderer.setPixelRatio(dpr);
      renderer.setSize(vw, vh, false);

      document.documentElement.classList.add('cb-fusion-on');

      observeCards();
      observeSections();
      trackScroll();
      if (window.CBCursor && window.CBCursor.init) { try { window.CBCursor.init(); } catch (e2) {} }

      window.addEventListener('mousemove', function (e) {
        mouseNX = (e.clientX / vw) * 2 - 1;
        mouseNY = -((e.clientY / vh) * 2 - 1);
      }, { passive: true });

      var rt;
      window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(resize, 180); }, { passive: true });
      document.addEventListener('visibilitychange', function () { if (document.hidden) { stop(); } else { start(); } });

      start();
      initialized = true;
      return true;
    } catch (err) {
      document.documentElement.classList.remove('cb-fusion-on');
      stop();
      if (renderer && renderer.dispose) { try { renderer.dispose(); } catch (e3) {} }
      if (window.console) { console.warn('[cb-fusion] init failed; using static fallback.', err); }
      return false;
    }
  }

  window.CBFusion = { init: init };

})(window, document);

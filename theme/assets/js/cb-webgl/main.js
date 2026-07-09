/*!
 * CB Legacy — WebGL Scrolltelling Homepage : ENTRY + GLUE
 * Global: window.CBWebGL
 * Implements CONTRACT.md §3 (scene registry), §4 (pacing map),
 *           §9 (capability + fallback + entry), §10 (capture harness).
 *
 * Load order (classic scripts, no modules):
 *   THREE -> CBShaders -> CBEngine -> CBScenes -> CBCursor -> CBWebGL(this)
 * This module reads only globals defined earlier in that order.
 *
 * UMD Three.js (window.THREE, r160). No import/export, no examples/jsm.
 */
(function (window, document) {
  'use strict';

  var THREE = window.THREE;

  /* ----------------------------------------------------------------------
   * §3  SCENE REGISTRY
   * 8 configs, index = scene order. `image` is resolved at init() from
   * basePath: image = basePath + slug + '.jpg'.
   * dark / vignette / grain / layer per the §2 asset table.
   * -------------------------------------------------------------------- */
  var SCENES = [
    {
      slug: '01-arrival',
      image: '',
      dark: false,
      vignette: 0.30,
      grain: 0.025,
      layer: 'arrival',
      params: {}
    },
    {
      slug: '02-welcome',
      image: '',
      dark: false,
      vignette: 0.28,
      grain: 0.022,
      layer: 'welcome',
      params: {}
    },
    {
      slug: '03-listings',
      image: '',
      dark: false,
      vignette: 0.30,
      grain: 0.022,
      layer: 'listings',
      params: {}
    },
    {
      slug: '04-legacy',
      image: '',
      dark: true,
      vignette: 0.52,
      grain: 0.038,
      layer: 'legacy',
      params: {}
    },
    {
      slug: '05-door',
      image: '',
      dark: false,
      vignette: 0.42,
      grain: 0.028,
      layer: 'door',
      params: {}
    },
    {
      slug: '06-communities',
      image: '',
      dark: false,
      vignette: 0.32,
      grain: 0.024,
      layer: 'communities',
      params: {}
    },
    {
      slug: '07-value',
      image: '',
      dark: false,
      vignette: 0.30,
      grain: 0.022,
      layer: 'value',
      params: {}
    },
    {
      slug: '08-connect',
      image: '',
      dark: true,
      vignette: 0.48,
      grain: 0.034,
      layer: 'connect',
      params: {}
    }
  ];

  /* ----------------------------------------------------------------------
   * §4  PACING MAP  (global progress 0..1 over 600vh)
   * Ordered bands. Hold bands render one scene; transition bands blend two.
   * -------------------------------------------------------------------- */
  var BANDS = [
    { start: 0.00, end: 0.18, type: 'hold', scene: 0 },
    { start: 0.18, end: 0.22, type: 'trans', from: 0, to: 1, transition: 'landing' },
    { start: 0.22, end: 0.35, type: 'hold', scene: 1 },
    { start: 0.35, end: 0.38, type: 'trans', from: 1, to: 2, transition: 'gallerySpread' },
    { start: 0.38, end: 0.50, type: 'hold', scene: 2 },
    { start: 0.50, end: 0.53, type: 'trans', from: 2, to: 3, transition: 'nightFalls' },
    { start: 0.53, end: 0.63, type: 'hold', scene: 3 },
    { start: 0.63, end: 0.66, type: 'trans', from: 3, to: 4, transition: 'dawnBreak' },
    { start: 0.66, end: 0.74, type: 'hold', scene: 4 },
    { start: 0.74, end: 0.77, type: 'trans', from: 4, to: 5, transition: 'river' },
    { start: 0.77, end: 0.86, type: 'hold', scene: 5 },
    { start: 0.86, end: 0.88, type: 'trans', from: 5, to: 6, transition: 'goldenMoment' },
    { start: 0.88, end: 0.94, type: 'hold', scene: 6 },
    { start: 0.94, end: 0.96, type: 'trans', from: 6, to: 7, transition: 'theClose' },
    { start: 0.96, end: 1.00, type: 'hold', scene: 7 }
  ];

  function clamp01(v) {
    return v < 0 ? 0 : (v > 1 ? 1 : v);
  }
  function clamp(v, lo, hi) {
    return v < lo ? lo : (v > hi ? hi : v);
  }

  /*
   * progressToState(p) -> {
   *   fromIndex, toIndex, t, inTransition, transition,
   *   localProg            // 0..1 within the relevant scene's hold band
   * }
   *
   * In a HOLD band: inTransition=false, fromIndex=toIndex=scene, transition=null,
   *   localProg = position within this hold band.
   * In a TRANSITION band: inTransition=true, t = blend across the band,
   *   localProg here reflects the OUTGOING scene at ~1.0 (it is finishing). The
   *   engine drives both scenes' update() separately during render().
   */
  function progressToState(p) {
    p = clamp01(p);
    var i, b;
    for (i = 0; i < BANDS.length; i++) {
      b = BANDS[i];
      // inclusive of the final band end
      if (p <= b.end || i === BANDS.length - 1) {
        if (b.type === 'hold') {
          var hp = (b.end - b.start) > 0
            ? (p - b.start) / (b.end - b.start)
            : 0;
          return {
            fromIndex: b.scene,
            toIndex: b.scene,
            t: 0,
            inTransition: false,
            transition: null,
            localProg: clamp01(hp)
          };
        }
        // transition band
        var tt = (b.end - b.start) > 0
          ? (p - b.start) / (b.end - b.start)
          : 0;
        return {
          fromIndex: b.from,
          toIndex: b.to,
          t: clamp01(tt),
          inTransition: true,
          transition: b.transition,
          localProg: 1.0
        };
      }
    }
    // Fallback (should not hit): final scene held.
    return {
      fromIndex: 7, toIndex: 7, t: 0,
      inTransition: false, transition: null, localProg: 1.0
    };
  }

  /* ----------------------------------------------------------------------
   * Capability helpers (§9 gate). Engine owns the WebGL probe; we add the
   * device/preference gates here so we can bail before touching GL.
   * -------------------------------------------------------------------- */
  function isMobile() {
    // < 768px OR coarse-only pointer (touch primary).
    var narrow = (window.innerWidth || document.documentElement.clientWidth || 0) < 768;
    var coarse = false;
    if (window.matchMedia) {
      coarse = window.matchMedia('(pointer: coarse)').matches &&
               !window.matchMedia('(pointer: fine)').matches;
    }
    return narrow || coarse;
  }

  function reducedMotion() {
    return !!(window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  /* ----------------------------------------------------------------------
   * Internal runtime state
   * -------------------------------------------------------------------- */
  var _engine = null;
  var _sceneObjs = new Array(SCENES.length);   // CBScenes sceneObj per index
  var _textures = new Array(SCENES.length);    // THREE.Texture per index
  var _texLoaded = new Array(SCENES.length);   // boolean per index
  var _initialized = false;
  var _disposed = false;
  var _capture = false;
  var _lenis = null;
  var _rafId = 0;
  var _startTime = 0;
  var _contextLost = false;

  // forced/derived progress
  var _globalProgress = 0;
  var _captureProgress = 0;

  // diagnostics (verification harness)
  var _diag = { texOk: 0, texErr: 0, built: false, renders: 0, lastErr: '' };

  // viewport dims captured at init (used by lazy scene builder)
  var _w = 0, _h = 0;

  // scroll plumbing for normal mode
  var _scrollEl = null;
  var _usingScrollTrigger = false;
  var _gsapTicker = false;

  // scratch (no per-frame allocations)
  var _mouse = { x: 0, y: 0 };
  var _renderArg = {
    fromIndex: 0, toIndex: 0, t: 0, localProg: 0,
    inTransition: false, transition: null,
    time: 2.0, mouse: null,
    vignette: 0.3, grain: 0.025, exposure: 1.0
  };

  var _lastDark = null;

  /* ----------------------------------------------------------------------
   * Texture preload — TextureLoader for all 8 (§9 / §10).
   * -------------------------------------------------------------------- */
  function preloadTextures(onAllReady) {
    var loader = new THREE.TextureLoader();
    // crossOrigin='anonymous' is REQUIRED to use a texture from a *different*
    // http(s) origin (e.g. a CDN) in WebGL, but it BREAKS file:// loads (no CORS
    // response) and is needless same-origin. Three defaults it to 'anonymous', so
    // we must explicitly clear it (null => no crossorigin attribute) unless the
    // images are genuinely cross-origin http(s).
    var firstImg = (SCENES[0] && SCENES[0].image) ? SCENES[0].image : '';
    var crossOrigin = null;
    if (/^https?:/i.test(firstImg)) {
      try {
        crossOrigin = (firstImg.indexOf(location.origin) === 0) ? null : 'anonymous';
      } catch (e) { crossOrigin = 'anonymous'; }
    }
    loader.crossOrigin = crossOrigin;
    var remaining = SCENES.length;
    var i;

    function markDone() {
      remaining--;
      if (remaining <= 0 && typeof onAllReady === 'function') {
        onAllReady();
      }
    }

    for (i = 0; i < SCENES.length; i++) {
      (function (idx) {
        _texLoaded[idx] = false;
        loader.load(
          SCENES[idx].image,
          function (tex) {
            configureTexture(tex);
            _textures[idx] = tex;
            _texLoaded[idx] = true;
            _diag.texOk++;
            // If the scene was already built with a placeholder, swap nothing —
            // scenes are built AFTER first-pass load below, so this is the source.
            markDone();
          },
          undefined,
          function (err) {
            // On error still produce a usable (black) texture so we never blank.
            _textures[idx] = makeFallbackTexture();
            _texLoaded[idx] = true;
            _diag.texErr++;
            _diag.lastErr = 'tex' + idx + ' load failed';
            markDone();
          }
        );
      })(i);
    }
  }

  function configureTexture(tex) {
    tex.minFilter = THREE.LinearFilter;
    tex.magFilter = THREE.LinearFilter;
    tex.generateMipmaps = false;
    tex.wrapS = THREE.ClampToEdgeWrapping;
    tex.wrapT = THREE.ClampToEdgeWrapping;
    // r160 color space
    if ('colorSpace' in tex && THREE.SRGBColorSpace) {
      tex.colorSpace = THREE.SRGBColorSpace;
    } else if ('encoding' in tex && THREE.sRGBEncoding) {
      tex.encoding = THREE.sRGBEncoding;
    }
    tex.needsUpdate = true;
  }

  function makeFallbackTexture() {
    var c = document.createElement('canvas');
    c.width = 4; c.height = 4;
    var ctx = c.getContext('2d');
    ctx.fillStyle = '#0A1730';
    ctx.fillRect(0, 0, 4, 4);
    var tex = new THREE.CanvasTexture(c);
    configureTexture(tex);
    return tex;
  }

  /* ----------------------------------------------------------------------
   * Build all 8 scene objects via CBScenes.build and register with engine.
   * Called after textures preload so each builder gets its real texture.
   * -------------------------------------------------------------------- */
  // Lazy: build + register one scene the first time it is needed for a render.
  // Building all 8 heavy scenes (star fields, terrain, RTs) upfront blows the
  // memory budget of software WebGL (SwiftShader) and is wasteful on real GPUs
  // too — only the 1–2 scenes visible at the current scroll position are ever
  // needed at once.
  function ensureScene(i) {
    if (i < 0 || i >= SCENES.length) { return; }
    if (_sceneObjs[i]) { return; }
    var cfg = SCENES[i];
    var tex = _textures[i] || makeFallbackTexture();
    var sceneObj = window.CBScenes.build(cfg.layer, {
      texture: tex,
      width: _w,
      height: _h,
      params: cfg.params || {}
    });
    _sceneObjs[i] = sceneObj;
    _engine.registerScene(i, {
      scene: sceneObj.scene,
      camera: sceneObj.camera,
      update: sceneObj.update
    });
    _diag.built = true;
  }

  /* ----------------------------------------------------------------------
   * Per-frame: compute pacing state, ramp postFX, and render. The engine
   * drives each scene's update() inside render(); we feed it localProg via the
   * render arg (see below). NO allocations here.
   * -------------------------------------------------------------------- */
  function computeAndRender(time) {
    if (_contextLost || _disposed || !_engine) { return; }

    var state = progressToState(_globalProgress);

    // Lazy-build only the scene(s) this frame needs (memory-friendly).
    ensureScene(state.fromIndex);
    if (state.inTransition) { ensureScene(state.toIndex); }

    // --- Base postFX from the dominant scene ---
    var fromCfg = SCENES[state.fromIndex];
    var toCfg = SCENES[state.toIndex];

    var grain = fromCfg.grain;
    var vignette = fromCfg.vignette;
    var exposure = 1.0;

    if (state.inTransition) {
      // Blend base post values across the transition for a smooth handoff.
      var bt = state.t;
      grain = fromCfg.grain + (toCfg.grain - fromCfg.grain) * bt;
      vignette = fromCfg.vignette + (toCfg.vignette - fromCfg.vignette) * bt;

      // --- Transition-specific ramps (§4 / brief) ---
      if (state.transition === 'nightFalls') {
        // grain spikes 0.02 -> 0.06 -> 0.03 (peak at t=0.5, settles to 0.03).
        // baseline ramps 0.02->0.03 linearly; a sin() bump adds the mid spike.
        var baseline = 0.02 + (0.03 - 0.02) * bt;      // 0.02 -> 0.03
        var spike = Math.sin(bt * Math.PI) * 0.03;     // 0 -> 0.03 -> 0 (peak ~0.06)
        grain = baseline + spike;
      } else if (state.transition === 'dawnBreak') {
        // exposure spikes 1.0 -> 1.4 -> 1.0 (peak at t=0.5)
        exposure = 1.0 + Math.sin(bt * Math.PI) * 0.4;
      } else if (state.transition === 'goldenMoment') {
        // gentle exposure lift to support the gold flash
        exposure = 1.0 + Math.sin(bt * Math.PI) * 0.12;
      }
    }

    // --- Dark scene toggle (cursor + dataset) ---
    var dark = state.inTransition
      ? (state.t < 0.5 ? fromCfg.dark : toCfg.dark)
      : fromCfg.dark;
    applyDark(dark);

    // --- Fill render arg (reused object) ---
    // The engine owns scene update() (CONTRACT §5/§7): it calls
    // update(localProg, time, mouse) inside render(). The engine reads two
    // separate channels (engine.js render()):
    //   - `t`         = transition blend factor (only used when inTransition).
    //   - `localProg` = the scene's local progress within its HOLD band; the
    //                   engine calls update(localProg,...) for the held scene.
    // In a hold band we therefore MUST supply state.localProg here, or scenes get
    // no update at all (doors never open, dolly/value never advance). In a
    // transition band the engine drives from@1.0 / to@0.0 itself and ignores
    // localProg, using `t` for the shader blend.
    _renderArg.fromIndex = state.fromIndex;
    _renderArg.toIndex = state.toIndex;
    _renderArg.t = state.t;
    _renderArg.localProg = state.localProg;
    _renderArg.inTransition = state.inTransition;
    _renderArg.transition = state.transition;
    _renderArg.time = _capture ? 2.0 : time;
    _renderArg.mouse = _mouse;
    _renderArg.vignette = vignette;
    _renderArg.grain = grain;
    _renderArg.exposure = exposure;

    _engine.render(_renderArg);
    _diag.renders++;
  }

  /*
   * NOTE: scene update() is driven by the ENGINE, not here.
   * CONTRACT §5/§7: engine.render() internally calls each visible scene's
   * update(localProg, time, mouse). main.js delivers the local progress the
   * engine should use via the render arg (see computeAndRender):
   *   - hold band      -> _renderArg.t carries the hold's localProg
   *   - transition band-> engine drives from@1.0 / to@0.0 itself per §7
   * A previous version pre-called update() here, but the engine immediately
   * re-called update() during render() and overwrote those values — so the
   * pre-call was dead work and the hold localProg was lost. Do not re-add it.
   */

  function applyDark(dark) {
    if (dark === _lastDark) { return; }
    _lastDark = dark;
    if (dark) {
      document.documentElement.dataset.cbDark = '1';
    } else if (document.documentElement.dataset.cbDark) {
      delete document.documentElement.dataset.cbDark;
    }
    if (window.CBCursor && typeof window.CBCursor.setDark === 'function') {
      window.CBCursor.setDark(!!dark);
    }
  }

  /* ----------------------------------------------------------------------
   * Mouse -> normalized [-1,1] for parallax (skipped in capture mode).
   * -------------------------------------------------------------------- */
  function onMouseMove(e) {
    var w = window.innerWidth || 1;
    var h = window.innerHeight || 1;
    _mouse.x = (e.clientX / w) * 2 - 1;
    _mouse.y = -((e.clientY / h) * 2 - 1);
  }

  /* ----------------------------------------------------------------------
   * Normal-mode scroll wiring.
   * Prefers Lenis + GSAP/ScrollTrigger; falls back to a plain scroll listener.
   * globalProgress = scrollY / (scrollHeight - innerHeight).
   * -------------------------------------------------------------------- */
  function computeLinearProgress() {
    var doc = document.documentElement;
    var body = document.body;
    var scrollY = window.pageYOffset || doc.scrollTop || body.scrollTop || 0;
    var scrollH = Math.max(
      doc.scrollHeight, body.scrollHeight,
      doc.offsetHeight, body.offsetHeight
    );
    var denom = scrollH - (window.innerHeight || doc.clientHeight || 0);
    if (denom <= 0) { return 0; }
    return clamp01(scrollY / denom);
  }

  // Scene hold-band starts (MUST match the BANDS hold entries above) — used to
  // anchor each scene to its real DOM section.
  var _holdStarts = [0.00, 0.22, 0.38, 0.53, 0.66, 0.77, 0.88, 0.96];

  // DOM-anchored progress. A linear scrollY->progress map assumes every scene is
  // the same height, but the live sections are not (the listings grid and the
  // testimonials+blog close section are far taller than a viewport). That made the
  // backdrop scene drift ahead of the content ("What's my home worth?" over the
  // starfield, etc.). Here we map scrollY piecewise through the actual section
  // offsets so scene i's backdrop is held while section i is in view and the
  // transition plays as the next section arrives. Falls back to linear if the
  // sections can't be found. Read live so it stays correct as async content
  // (MLS cards, testimonials) changes the page height.
  function computeScrollProgress() {
    var sections = document.querySelectorAll('.cb-scene[data-scene]');
    var n = Math.min(sections.length, _holdStarts.length);
    if (n < 2) { return computeLinearProgress(); }
    var doc = document.documentElement;
    var body = document.body;
    var y = window.pageYOffset || doc.scrollTop || body.scrollTop || 0;
    var vh = window.innerHeight || doc.clientHeight || 0;
    var maxScroll = Math.max(1,
      Math.max(doc.scrollHeight, body.scrollHeight, doc.offsetHeight, body.offsetHeight)
      - vh);
    var sc = [], pr = [], prev = -1, i, top;
    for (i = 0; i < n; i++) {
      // Anchor scene i to when its section's top reaches the viewport CENTER (not
      // the very top), so the backdrop flips in step with the section the visitor
      // is actually reading — removes the half-viewport lead.
      top = sections[i].getBoundingClientRect().top + y - vh * 0.5;
      if (top < 0) { top = 0; }
      if (top <= prev) { top = prev + 1; }               // force strictly ascending
      prev = top;
      sc.push(Math.min(top, maxScroll));
      pr.push(_holdStarts[i]);
    }
    sc.push(maxScroll > prev ? maxScroll : prev + 1);
    pr.push(1.0);
    if (y <= sc[0]) { return pr[0]; }
    for (i = 1; i < sc.length; i++) {
      if (y <= sc[i]) {
        var f = (sc[i] - sc[i - 1]) > 0 ? (y - sc[i - 1]) / (sc[i] - sc[i - 1]) : 0;
        return clamp01(pr[i - 1] + (pr[i] - pr[i - 1]) * f);
      }
    }
    return pr[pr.length - 1];
  }

  function onPlainScroll() {
    _globalProgress = computeScrollProgress();
  }

  function setupNormalScroll() {
    var Lenis = window.Lenis;
    var gsap = window.gsap;
    var ScrollTrigger = window.ScrollTrigger;

    if (Lenis) {
      try {
        _lenis = new Lenis({
          duration: 1.1,
          smoothWheel: true,
          smoothTouch: false
        });
      } catch (err) {
        _lenis = null;
      }
    }

    if (_lenis && gsap && ScrollTrigger) {
      // Integrate Lenis + ScrollTrigger; keep native document scroll.
      _usingScrollTrigger = true;
      _lenis.on('scroll', ScrollTrigger.update);
      gsap.ticker.add(function (t) {
        // gsap ticker time is seconds; Lenis wants ms
        _lenis.raf(t * 1000);
      });
      _gsapTicker = true;
      if (gsap.ticker.lagSmoothing) { gsap.ticker.lagSmoothing(0); }

      ScrollTrigger.create({
        trigger: _scrollEl || document.body,
        start: 'top top',
        end: 'bottom bottom',
        onUpdate: function (self) {
          _globalProgress = clamp01(self.progress);
        }
      });
      // Seed initial progress
      _globalProgress = computeScrollProgress();
    } else if (_lenis) {
      // Lenis without ScrollTrigger: drive raf from our loop, read scroll each frame.
      _globalProgress = computeScrollProgress();
    } else {
      // Plain scroll listener fallback.
      window.addEventListener('scroll', onPlainScroll, { passive: true });
      window.addEventListener('resize', onPlainScroll, { passive: true });
      _globalProgress = computeScrollProgress();
    }
  }

  /* ----------------------------------------------------------------------
   * RAF loop (normal mode).
   * -------------------------------------------------------------------- */
  function loop(now) {
    if (_disposed) { return; }
    _rafId = window.requestAnimationFrame(loop);

    var time = now - _startTime;

    // If Lenis is driving its own raf (via gsap ticker) we don't call raf here.
    if (_lenis && !_gsapTicker) {
      _lenis.raf(now);
    }
    // If using Lenis without ScrollTrigger, refresh progress from scroll pos.
    if (_lenis && !_usingScrollTrigger) {
      _globalProgress = computeScrollProgress();
    }

    computeAndRender(time);
  }

  /* ----------------------------------------------------------------------
   * Resize handling.
   * -------------------------------------------------------------------- */
  function onResize() {
    if (!_engine || _disposed) { return; }
    var w = window.innerWidth;
    var h = window.innerHeight;
    var dpr = window.devicePixelRatio || 1;
    _engine.resize(w, h, dpr);
    if (!_capture && !_usingScrollTrigger) {
      _globalProgress = computeScrollProgress();
    }
  }

  /* ----------------------------------------------------------------------
   * WebGL context loss guard.
   * -------------------------------------------------------------------- */
  function onContextLost(e) {
    e.preventDefault();
    _contextLost = true;
    if (_rafId) {
      window.cancelAnimationFrame(_rafId);
      _rafId = 0;
    }
  }
  function onContextRestored() {
    _contextLost = false;
    if (!_disposed && !_capture && !_rafId) {
      _startTime = performance.now();
      _rafId = window.requestAnimationFrame(loop);
    }
  }

  /* ----------------------------------------------------------------------
   * §10  CAPTURE MODE
   * ?cb_capture=1 & ?cb_progress=float
   *   - no Lenis, freeze time=2.0, mouse={0,0}
   *   - force globalProgress = cb_progress
   *   - render once now, render again after all textures loaded
   *   - then window.__cbReady = true; document.title = 'CB_READY'
   * -------------------------------------------------------------------- */
  function parseCaptureParams() {
    var q = {};
    var search = (location.search || '').replace(/^\?/, '');
    if (search) {
      var parts = search.split('&');
      for (var i = 0; i < parts.length; i++) {
        var kv = parts[i].split('=');
        q[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1] || '');
      }
    }
    var capture = q.cb_capture === '1' || q.cb_capture === 'true';
    var prog = parseFloat(q.cb_progress);
    if (isNaN(prog)) { prog = 0; }
    return { capture: capture, progress: clamp01(prog) };
  }

  function captureRenderOnce() {
    _mouse.x = 0;
    _mouse.y = 0;
    _globalProgress = _captureProgress;
    computeAndRender(2.0);
  }

  function signalCaptureReady() {
    window.__cbReady = true;
    document.title = 'CB_READY';
  }

  /* ----------------------------------------------------------------------
   * §9  ENTRY  —  CBWebGL.init({ basePath, canvas, capture })
   * -------------------------------------------------------------------- */
  function init(opts) {
    if (_initialized) { return false; }
    opts = opts || {};

    // Resolve canvas
    var canvas = opts.canvas;
    if (typeof canvas === 'string') {
      canvas = document.querySelector(canvas);
    }
    if (!canvas) {
      canvas = document.getElementById('cb-webgl');
    }
    if (!canvas) { return false; }

    // Resolve base path & image urls (§3)
    var basePath = opts.basePath || '';
    for (var i = 0; i < SCENES.length; i++) {
      SCENES[i].image = basePath + SCENES[i].slug + '.jpg';
    }

    // Determine capture mode (explicit opt OR query string).
    var cap = parseCaptureParams();
    _capture = !!opts.capture || cap.capture;
    _captureProgress = cap.progress;

    // --- §9 capability gate ---
    // In capture mode we still require WebGL, but bypass mobile/reduced-motion
    // (headless screenshots may report coarse pointer / reduced motion).
    var glOk = !!(window.CBEngine && window.CBEngine.supported &&
                  window.CBEngine.supported());
    if (!glOk) {
      return false; // leave DOM/CSS fallback in place
    }
    if (!_capture && (isMobile() || reducedMotion())) {
      return false; // leave DOM/CSS fallback in place
    }

    _initialized = true;

    // Engine — create FIRST, before marking the document. THREE.WebGLRenderer can
    // throw even when a bare getContext() probe succeeded (GPU memory pressure,
    // driver blocklists, virtualized/headless GPUs). If creation throws or yields
    // nothing, bail to the DOM/CSS fallback WITHOUT adding html.cb-webgl-on — else
    // the class would be stuck on with the fallback hidden and no recovery (a blank
    // cinematic screen). Adding the class only after a successful create keeps the
    // success path identical while making the failure path fail safe.
    var w = window.innerWidth;
    var h = window.innerHeight;
    _w = w; _h = h;
    var dpr = window.devicePixelRatio || 1;
    try {
      _engine = window.CBEngine.create({
        canvas: canvas,
        width: w,
        height: h,
        dpr: dpr
      });
    } catch (engErr) {
      _engine = null;
      if (window.console) { console.warn('[cb] WebGL engine create failed:', engErr); }
    }
    if (!_engine) {
      _initialized = false;
      return false; // leave the DOM/CSS fallback in place
    }

    // Mark the document so CSS reveals the canvas + hides DOM media/native cursor.
    document.documentElement.classList.add('cb-webgl-on');

    // Context-loss guard
    canvas.addEventListener('webglcontextlost', onContextLost, false);
    canvas.addEventListener('webglcontextrestored', onContextRestored, false);

    // Cursor (no-op internally in capture / coarse / reduced-motion)
    if (window.CBCursor && typeof window.CBCursor.init === 'function' && !_capture) {
      window.CBCursor.init();
    }

    // Resize listener (both modes; capture re-renders explicitly)
    window.addEventListener('resize', onResize, { passive: true });

    // Preload textures, then build scenes + register, then run.
    // We build scenes only after preload so each builder gets its real texture.
    // To present a non-black first frame in capture asap, we render a first
    // pass after the first build (which may use fallback textures briefly).
    var built = false;

    function buildAndStart() {
      if (built) { return; }
      built = true;
      ensureScene(0); // first scene ready for an instant first frame

      if (_capture) {
        // §10: render once now (textures already loaded by this path),
        // render again next tick, then signal ready.
        captureRenderOnce();
        window.requestAnimationFrame(function () {
          captureRenderOnce();
          signalCaptureReady();
        });
      } else {
        _mouse.x = 0;
        _mouse.y = 0;
        document.addEventListener('mousemove', onMouseMove, { passive: true });
        setupNormalScroll();
        _startTime = performance.now();
        _rafId = window.requestAnimationFrame(loop);
      }
    }

    // Preload all 8. Build scenes once everything is in (deterministic capture).
    preloadTextures(function () {
      buildAndStart();
    });

    // Safety net for normal mode: if a texture stalls, still come alive after a
    // short grace so the page is never frozen (capture relies on full load).
    if (!_capture) {
      window.setTimeout(function () {
        if (!built) {
          // Fill any missing textures with fallbacks so build can proceed.
          for (var k = 0; k < SCENES.length; k++) {
            if (!_textures[k]) {
              _textures[k] = makeFallbackTexture();
              _texLoaded[k] = true;
            }
          }
          buildAndStart();
        }
      }, 4000);
    }

    return true;
  }

  /* ----------------------------------------------------------------------
   * dispose() — full teardown of every GL resource + listeners.
   * -------------------------------------------------------------------- */
  function dispose() {
    if (_disposed) { return; }
    _disposed = true;

    if (_rafId) {
      window.cancelAnimationFrame(_rafId);
      _rafId = 0;
    }

    // Listeners
    window.removeEventListener('resize', onResize);
    window.removeEventListener('scroll', onPlainScroll);
    window.removeEventListener('resize', onPlainScroll);
    document.removeEventListener('mousemove', onMouseMove);

    // Lenis
    if (_lenis && typeof _lenis.destroy === 'function') {
      try { _lenis.destroy(); } catch (e) {}
    }
    _lenis = null;

    // Cursor
    if (window.CBCursor && typeof window.CBCursor.destroy === 'function') {
      try { window.CBCursor.destroy(); } catch (e2) {}
    }

    // Scenes
    for (var i = 0; i < _sceneObjs.length; i++) {
      var s = _sceneObjs[i];
      if (s && typeof s.dispose === 'function') {
        try { s.dispose(); } catch (e3) {}
      }
      _sceneObjs[i] = null;
    }

    // Textures
    for (var j = 0; j < _textures.length; j++) {
      if (_textures[j] && typeof _textures[j].dispose === 'function') {
        try { _textures[j].dispose(); } catch (e4) {}
      }
      _textures[j] = null;
    }

    // Engine (owns RenderTargets / compositor / post quad)
    if (_engine && typeof _engine.dispose === 'function') {
      try { _engine.dispose(); } catch (e5) {}
    }
    _engine = null;

    document.documentElement.classList.remove('cb-webgl-on');
    if (document.documentElement.dataset.cbDark) {
      delete document.documentElement.dataset.cbDark;
    }

    _initialized = false;
  }

  /* ----------------------------------------------------------------------
   * Public API — attach exactly ONE global.
   * -------------------------------------------------------------------- */
  var CBWebGL = {
    init: init,
    dispose: dispose,
    // Debugging / verification surface (§3, §4, §10):
    SCENES: SCENES,
    progressToState: progressToState,
    // read-only-ish helpers for harness/debug
    isMobile: isMobile,
    reducedMotion: reducedMotion,
    _diag: function () { return _diag; },
    _getProgress: function () { return _globalProgress; },
    _setProgress: function (p) {
      // Manual override (debug); also used by external harnesses.
      _globalProgress = clamp01(p);
      if (_capture) { captureRenderOnce(); }
    }
  };

  window.CBWebGL = CBWebGL;

})(window, document);

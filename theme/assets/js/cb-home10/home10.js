/* =========================================================================
   home10.js -- Coldwell Banker "THE HOUSE" homepage (Home 10 preview)

   Home 9, filmed instead of modelled.

   This REPLACED Home 9. cb-home9/home9.js has been deleted; the Home 9 page
   template is now an alias that renders this. Two pieces of Home 9 deliberately
   survive because Home 10 is built on them -- cb-home9.css, which styles
   everything inside a panel, and home9-house-scenes.php, which is the content
   this lifts. Deleting either takes Home 10 down with it.

   The WALK is the same walk: the same eight sections, the same hallway between
   rooms, the same content pages. What changes is what the camera is looking at.

   WHAT IS ACTUALLY DIFFERENT
   --------------------------
   Home 9 builds the house at runtime out of boxes, planes and lights, and moves
   a THREE.PerspectiveCamera through it. Every surface is authored, and the
   ceiling being the right white is a tuning problem.

   Home 10 has no geometry, no lights, no renderer and no THREE at all. The house
   is a set of Higgsfield-generated photographs and the moves between them are
   generated video. Scrolling scrubs the video. That means:

     - NO WEBGL. If this file fails, there is nothing to fall back to but the
       flat layout, exactly as in Home 9 -- but there is also no GPU cost, no
       shader compilation and no draw calls. It runs on anything that can decode
       h264.
     - NO PROJECTION MATH. Home 9's whole page-projection layer exists because
       the pages live in a 3D world. Here they are ordinary fixed panels that
       fade in over a video. There is no poseAt(), no billboarding, no upp12.
     - PHOTOGRAPHS, NOT MATERIALS. "The navy is too saturated" is now a prompt
       change, not a re-derivation through the sRGB transfer curve.

   THE CHAIN, AND WHY THE SEAMS ARE INVISIBLE
   ------------------------------------------
   Each transition video is generated with BOTH a start_image and an end_image
   pinned to the stills either side of it:

       TRANS[i] : STILL[i-1]  ------->  STILL[i]
       TRANS[i+1] : STILL[i]  ------->  STILL[i+1]

   so TRANS[i] ENDS on exactly the frame TRANS[i+1] BEGINS on. Section i keeps
   its own video on screen for its whole length -- scrubbing during the walk,
   parked on its last frame during the dwell -- and the handoff to section i+1
   is therefore a crossfade between two identical frames. There is nothing to
   see, which is the point. Generate these with start_image only and the chain
   drifts: every room would arrive somewhere slightly different from where the
   next move departs, and every section boundary would visibly jump.

   SCROLL TRIGGERS THE WALK -- IT DOES NOT SCRUB IT
   ------------------------------------------------
   Reaching a section PLAYS that section's transition at its natural rate, once,
   and then holds on its last frame. Scroll decides WHEN you set off; it does
   not decide where in the clip you are.

   The first cut scrubbed instead: currentTime was assigned every frame from the
   scroll position. That is the Apple-style treatment and it is defensible, but
   it is not what walking through a house feels like. Scrubbing puts the viewer
   in charge of the camera, so the move is only ever as smooth as their trackpad
   -- flick and you teleport, creep and you crawl, stop half way and you are
   standing in a doorway. Playing the clip means the walk always has the pacing
   it was generated with.

   It is also, simply, smoother. Playback is sequential decoding, which is what
   a video decoder is built to do; scrubbing is a seek per frame, which is the
   one thing it is bad at. Nothing here fights the decoder any more:

     - no seek per frame -- one seek to 0 when a walk starts, and that is all
     - no lagged clock needed for the picture, because the picture is no longer
       derived from scroll at all
     - no dependence on an all-intra re-encode (see tools/build-home10.mjs --
       that requirement went away with the scrubbing, and the files roughly
       halved as a result)

   DIRECTION. Going forward you WALK into the room. Going back you are simply
   put in the room you returned to, parked on its final frame. A generated clip
   cannot be played in reverse, and re-playing the forward walk when someone
   scrolls up would show them walking away from where they are going.

   Exposes window.CBHome10.init(opts). No dependencies -- no THREE, no Motion.
   ========================================================================= */
(function (window, document) {
  'use strict';

  if (window.CBHome10) { return; }

  var raf = window.requestAnimationFrame || function (cb) { return setTimeout(function () { cb(); }, 16); };
  var caf = window.cancelAnimationFrame || window.clearTimeout;

  // ---- the walk -----------------------------------------------------------
  // STILL[i] is section i's resting frame. TRANS[i] is the move that ARRIVES at
  // section i -- so TRANS[0] is the intro (coming in the front door and looking
  // down the hall) and TRANS[7] is kitchen -> hearth. Every TRANS[i] is pinned
  // end-to-start against its neighbours; see the header.
  // SEVEN sections, not eight. The old "Value" panel was removed (its Property
  // Watch signup became the pop-up), and _n is min(SECTIONS, panels) -- so with
  // eight rooms listed against seven panels the LAST room would simply never be
  // reached. The kitchen is dropped rather than the hearth: the closing section
  // wants the fireplace, not the worktop. t6-kitchen.mp4 stays in the repo,
  // unused, so restoring a section is a one-line change.
  var SECTIONS = [
    { id: 'arrival',     still: '01-hall.jpg',    trans: 't0-intro.mp4' },
    { id: 'welcome',     still: '02-living.jpg',  trans: 't1-living.mp4' },
    { id: 'listings',    still: '03-gallery.jpg', trans: 't2-gallery.mp4' },
    { id: 'communities', still: '04-study.jpg',   trans: 't3-study.mp4' },
    { id: 'legacy',      still: '05-entry.jpg',   trans: 't4-entry.mp4' },
    { id: 'door',        still: '06-dining.jpg',  trans: 't5-dining.mp4' },
    { id: 'connect',     still: '08-hearth.jpg',  trans: 't7-hearth.mp4' }
  ];

  // How long the panel takes to fade up once the walk has finished, in seconds.
  // There is no longer a "fraction of the section spent walking" -- the walk
  // takes exactly as long as its clip does, whatever the reader is doing with
  // the scrollbar.
  var PANEL_FADE = 0.45;

  // ---- state --------------------------------------------------------------
  var stage = null, pagesEl = [], vids = [], stills = [];
  var _base = '', _imgBase = '', _vidBase = '';
  var _n = 0, _secH = 0, _total = 0;
  var _gRaw = 0, _g = 0;          // section-space position, 0 .. n-1 (+fraction)
  var _rafId = null, _started = false, _initialized = false;
  var _active = -1;
  var _reduced = false;
  var _lastT = 0;
  var _capture = false, _captureG = 0;
  var _lastW = -1, _laidOut = false;
  var _panel = 0;   // eased 0..1 panel visibility for the active section

  function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }
  /** Frame-rate independent lerp -- a fixed per-frame rate chases twice as fast
   *  at 120Hz as at 60Hz, and the symptom reads as "the video lags the scroll"
   *  only on some machines, which is the worst kind of bug report. */
  function lerpK(rate, dt) { return 1 - Math.pow(1 - rate, dt * 60); }

  // ---- build --------------------------------------------------------------
  /**
   * The stage: one <video> per section, stacked, with the section's still as
   * its poster.
   *
   * The poster is doing real work, not decoration. Until a video has decoded
   * its first frame it paints nothing, so without a poster the first paint of
   * the page is a black rectangle. With one, the still is on screen instantly
   * and the video replaces it silently once it is ready -- and if the video
   * never loads at all (404, unsupported codec, data saver) the still simply
   * stays, and the walk degrades into a slideshow of the same eight rooms
   * rather than into nothing.
   */
  function buildStage() {
    stage.innerHTML = '';
    var i, v;
    for (i = 0; i < _n; i++) {
      v = document.createElement('video');
      v.className = 'cb10-frame';
      v.src = _vidBase + SECTIONS[i].trans;
      v.poster = _imgBase + SECTIONS[i].still;
      v.muted = true;
      v.defaultMuted = true;
      v.playsInline = true;
      v.setAttribute('playsinline', '');
      v.setAttribute('webkit-playsinline', '');
      v.setAttribute('aria-hidden', 'true');
      // 'metadata', NOT 'auto'. Eight clips at preload="auto" is roughly 16MB
      // fetched before the reader has scrolled once -- tolerable on a desk,
      // indefensible on a phone on cellular, and the single biggest reason this
      // page used to be gated to desktop. primeAround() promotes just the
      // current clip and the next one to 'auto' as you walk.
      //
      // 'metadata' and not 'none' on purpose: arrived() needs __dur, which only
      // arrives with loadedmetadata. With 'none' the duration is unknown until
      // play() has fetched enough, so the panel would stay hidden for a beat on
      // entering every room. Metadata is a few KB per file (moov is at the
      // front -- see -movflags +faststart in tools/build-home10.mjs).
      v.preload = 'metadata';
      v.loop = false;
      // Never let the browser play these on its own. Every frame shown is one
      // we asked for -- autoplay would race the scrubber and fight it.
      v.autoplay = false;
      v.__ready = false;
      v.__dur = 0;
      (function (vv) {
        vv.addEventListener('loadedmetadata', function () {
          vv.__dur = isFinite(vv.duration) ? vv.duration : 0;
        });
        // canplaythrough, not canplay: canplay fires when the FIRST frame is
        // decodable, which is not the same as being able to seek anywhere in
        // the file without stalling. Scrubbing needs the latter.
        vv.addEventListener('canplaythrough', function () { vv.__ready = true; });
        vv.addEventListener('error', function () { vv.__ready = false; });
      }(v));
      stage.appendChild(v);
      vids.push(v);
    }
  }

  /**
   * Buffer the clip you are in and the one you are walking into next; let the
   * rest sit at 'metadata'.
   *
   * Two ahead rather than one because the walk starts the instant the section
   * boundary is crossed -- priming only on arrival would mean every room began
   * with a stall. Nothing is ever demoted back: a clip you have already watched
   * is already in the HTTP cache, and setting preload back to 'metadata' does
   * not reclaim anything, it just risks a re-fetch when you scroll back up.
   */
  function primeAround(i) {
    for (var k = i; k <= i + 1 && k < _n; k++) {
      var v = vids[k];
      if (v && v.preload !== 'auto') { v.preload = 'auto'; }
    }
  }

  /**
   * Property Watch pop-up: shown once, after the reader gets past slide 3.
   *
   * "After slide 3" is deliberately >= 3 rather than an exact match: a fast
   * flick can jump the active section from 2 to 5 in one frame, and an equality
   * test would miss it entirely and never fire.
   *
   * Suppressed for good once dismissed or submitted, remembered in
   * localStorage. Wrapped in try/catch because Safari's private mode throws on
   * localStorage access -- and a storage failure must not be allowed to block
   * the modal's close button, which would trap the reader behind a scrim.
   */
  var WATCH_KEY = 'cb10_watch_dismissed';
  var _watchEl = null, _watchDone = false, _watchLastFocus = null;

  function watchSeen() {
    try { return window.localStorage.getItem(WATCH_KEY) === '1'; } catch (e) { return false; }
  }
  function watchRemember() {
    try { window.localStorage.setItem(WATCH_KEY, '1'); } catch (e) {}
  }
  function closeWatch() {
    if (!_watchEl) { return; }
    _watchEl.hidden = true;
    watchRemember();
    if (_watchLastFocus && _watchLastFocus.focus) {
      try { _watchLastFocus.focus(); } catch (e) {}
    }
  }
  function openWatch() {
    if (!_watchEl || _watchDone || _capture || watchSeen()) { return; }
    _watchDone = true;
    _watchLastFocus = document.activeElement;
    _watchEl.hidden = false;
    var input = _watchEl.querySelector('input[type="email"]');
    if (input) { try { input.focus(); } catch (e) {} }
  }
  function initWatch() {
    _watchEl = document.getElementById('cb10-watch');
    if (!_watchEl) { return; }
    if (watchSeen()) { _watchDone = true; return; }

    var closers = _watchEl.querySelectorAll('[data-cb10-close]');
    for (var i = 0; i < closers.length; i++) {
      closers[i].addEventListener('click', closeWatch);
    }
    document.addEventListener('keydown', function (e) {
      if (!_watchEl.hidden && (e.key === 'Escape' || e.keyCode === 27)) { closeWatch(); }
    });
    // Submitting counts as answering the ask -- do not show it again either way.
    var form = _watchEl.querySelector('form');
    if (form) { form.addEventListener('submit', function () { watchRemember(); }); }
  }

  /** Section scroll length. Kept in JS rather than CSS so the spacer and the
   *  clock can never disagree -- Home 9's one hard rule about spacers. */
  function layout() {
    var w = window.innerWidth, h = window.innerHeight;

    // MOBILE URL BAR. Scrolling on iOS/Android collapses and expands the address
    // bar, which fires resize with a height change of 60-120px and no width
    // change. Recomputing _secH from that mid-scroll rewrites the spacer height
    // and therefore which section a given scroll position maps to -- the page
    // lurches under the reader's thumb, mid-walk, every time the bar moves.
    //
    // So: only a WIDTH change (or an explicit orientationchange, which calls
    // relayout() directly) is allowed to resize the walk. A height-only change
    // is assumed to be browser chrome and ignored. The cost is that _secH stays
    // keyed to the height at load, which is exactly the stable behaviour wanted.
    if (_laidOut && w === _lastW) { return; }
    _lastW = w;
    _laidOut = true;

    // 1.25 viewports per section, was 1.85. The client asked for less dead
    // vertical scroll between scenes, and this is the only number that controls
    // it: the walk plays at its own pace regardless, so shortening a section
    // only removes the empty scrolling AFTER the room has been arrived at --
    // it does not speed up or truncate the video.
    _secH = Math.max(420, Math.round(h * 1.25));
    _total = _secH * _n;
    var sp = document.getElementById('cb10-spacer');
    if (sp) { sp.style.height = _total + 'px'; }
  }

  /** Force a re-measure regardless of width -- for orientationchange, where the
   *  height genuinely did change and the guard above must not swallow it. */
  function relayout() { _laidOut = false; layout(); }

  // ---- the clock ----------------------------------------------------------
  function readScroll() {
    // Capture mode forces the clock instead of scrolling the document. Home 9
    // does the opposite -- it synthesises a scroll position -- because there the
    // camera derives from scroll and a forced clock would desync the two. Here
    // there is only one number, so forcing it is exact AND it avoids a headless
    // screenshot artifact: a scrolled page renders position:fixed elements at
    // the wrong offset, so scrolling to take the shot moves the very layer the
    // shot is of.
    if (_capture) { _gRaw = _captureG; return; }
    var y = window.pageYOffset || document.documentElement.scrollTop || 0;
    _gRaw = clamp(y / _secH, 0, _n - 0.0001);
  }

  /** Park a clip on its final frame -- the room, arrived at. A hair before the
   *  very end, because seeking exactly to duration is where browsers disagree
   *  most: some clamp, some fire 'ended', some show black. */
  function park(v) {
    if (!v) { return; }
    try { v.pause(); } catch (e) {}
    if (v.__dur) {
      var t = v.__dur - 0.03;
      if (Math.abs(v.currentTime - t) > 0.03) {
        try { v.currentTime = t; } catch (e) {}
      }
    }
    v.__walking = false;
    v.__walkStart = 0;
  }

  /**
   * Arrive at section `i`.
   *
   * Forward -> rewind its clip and PLAY it: the walk into that room.
   * Backward -> put the reader in the room directly, on its last frame.
   *
   * Called only on a CHANGE of section, never per frame. The whole point of
   * playback over scrubbing is that once a walk is running, scroll stops having
   * anything to say about it.
   */
  function enterSection(i, forward) {
    var k, v;
    for (k = 0; k < _n; k++) {
      v = vids[k];
      if (!v) { continue; }
      if (k !== i) {
        // Leave nothing running behind the current frame -- a paused-off video
        // still decodes if it is playing, and eight of them would be eight
        // decoders competing for the same budget.
        if (!v.paused) { try { v.pause(); } catch (e) {} }
        v.classList.remove('is-on');
      } else {
        v.classList.add('is-on');
      }
    }
    _active = i;

    v = vids[i];
    if (!v) { return; }

    // Capture is a still of a ROOM, not of someone mid-stride, so it always
    // arrives rather than walking.
    if (!forward || _capture) { park(v); return; }

    try { v.currentTime = 0; } catch (e) {}
    v.__walking = true;
    v.__walkStart = Date.now();
    var p;
    try { p = v.play(); } catch (e) { p = null; }
    // Autoplay policy allows a muted, playsinline video -- but "allows" is not
    // "guarantees", and a rejected play() would otherwise leave the reader
    // staring at frame 0 of a corridor forever. Failing straight to the arrived
    // frame loses the walk and keeps the page.
    if (p && p.catch) { p.catch(function () { park(v); }); }
  }

  /** True once the current room has been arrived at and the panel may show. */
  function arrived() {
    var v = vids[_active];
    if (!v) { return true; }
    if (!v.__walking) { return true; }

    // STALL GUARD. Everything below assumes the clip actually progresses. On a
    // slow or flaky connection it may not: play() resolves, currentTime creeps
    // or stops, and the reader is left looking at a frozen frame with no text
    // and no way to know anything is wrong. Desktop on office wifi never showed
    // this; a phone on cellular is exactly where it happens.
    //
    // So give the walk its own duration plus a generous grace period in WALL
    // CLOCK time, and if it has not finished by then, call it arrived. The walk
    // is decoration; the content is not, and the content must not be gated
    // behind a download that may never complete.
    if (v.__walkStart) {
      var budget = ((v.__dur || 6) * 1000) + 5000;
      if (Date.now() - v.__walkStart > budget) { return true; }
    }

    if (!v.__dur) { return false; }
    return v.currentTime >= v.__dur - 0.12;
  }

  /**
   * Run the Legacy stat counters up from zero, once, when their panel is first
   * readable.
   *
   * Every other variant ships its own copy of this (home9.js countUp, home8.js,
   * corridor.js, dust.js, fusion.js, office.js) because the markup carries only
   * data-count and something has to animate it. Home 10 lifts Home 9's panels
   * but loads ONLY home10.js -- so until this existed the Legacy page rendered
   * "0 Homes Sold / 0 Expert Agents / 0 Years Serving / 0 Communities Served"
   * and stayed that way. The numbers were correct in the markup the whole time;
   * nothing was reading them.
   *
   * Selector is .cb9-stat__num, not .cb10-*, for the same reason every other
   * rule in cb-home10.css is: the panels are lifted verbatim and keep their
   * cb9- inner classes.
   */
  function countUp(el) {
    if (!el || el.__counted) { return; }
    el.__counted = true;
    var nums = el.querySelectorAll('.cb9-stat__num[data-count], .cb10-stat__num[data-count]');
    for (var i = 0; i < nums.length; i++) {
      (function (n) {
        var target = parseInt(n.getAttribute('data-count'), 10) || 0;
        // A screenshot has no time to animate over, so land on the final value.
        if (_capture || _reduced) { n.textContent = target.toLocaleString(); return; }
        var t0 = 0, dur = 1600;
        (function step(t) {
          if (!t0) { t0 = t; }
          var q = clamp((t - t0) / dur, 0, 1);
          // easeOutCubic -- fast off the mark, settles gently on the number.
          n.textContent = Math.round(target * (1 - Math.pow(1 - q, 3))).toLocaleString();
          if (q < 1) { raf(step); }
        })(0);
      })(nums[i]);
    }
  }

  /** Panels fade in once the walk into their room is DONE, and out again the
   *  instant the next walk begins -- so you are never reading over a moving
   *  picture. Driven by the clip, not by scroll: the text appears when you get
   *  there, however long that took. */
  function updatePages(i, dt) {
    _panel += ((arrived() ? 1 : 0) - _panel) * lerpK(1 - Math.pow(0.001, dt / PANEL_FADE), dt);
    if (_panel > 0.999) { _panel = 1; }
    if (_panel < 0.001) { _panel = 0; }
    for (var k = 0; k < _n; k++) {
      var el = pagesEl[k];
      if (!el) { continue; }
      var on = (k === i) ? _panel : 0;
      el.style.opacity = on;
      el.style.pointerEvents = on > 0.5 ? 'auto' : 'none';
      // inert covers wheel targeting, pointer gating AND tab order in one
      // switch -- without it a hidden panel still takes focus and still eats
      // the scroll gesture when the cursor happens to be over it.
      if (on > 0.5) { el.removeAttribute('inert'); } else { el.setAttribute('inert', ''); }
      el.classList.toggle('is-read', on > 0.5);
      // One-way latch driving the arrival panel's blur-into-frame reveal
      // (cb-home10.css). Deliberately never removed: toggling it would replay
      // the blur every time the reader scrolled back to the top, which reads as
      // a glitch rather than an entrance.
      if (on > 0.5 && !el.classList.contains('is-resolved')) {
        el.classList.add('is-resolved');
      }
      // Counting starts when the panel is actually legible, not when its
      // section becomes active -- otherwise the numbers would finish counting
      // behind a still-transparent panel during the walk in.
      if (on > 0.5) { countUp(el); }
    }
  }

  function frame(now) {
    var t = now || Date.now();
    var dt = _lastT ? Math.min(0.05, (t - _lastT) / 1000) : 0.016;
    _lastT = t;

    readScroll();
    // The clock is still eased, but it now only decides WHICH section you are
    // in -- not where in the clip. Easing it keeps a section from flickering
    // back and forth when the scroll position sits exactly on a boundary.
    _g += (_gRaw - _g) * lerpK(0.16, dt);
    if (Math.abs(_gRaw - _g) < 0.0005) { _g = _gRaw; }

    var i = clamp(Math.floor(_g), 0, _n - 1);
    if (i !== _active) {
      // Any jump forward -- even skipping several rooms at once -- is a walk
      // into wherever you landed. Backward is an arrival, never a walk.
      enterSection(i, i > _active);
      primeAround(i);
      if (i >= 3) { openWatch(); }
      _panel = 0;
    }
    updatePages(i, dt);
    syncNav(i);

    _rafId = raf(frame);
  }

  function syncNav(i) {
    var dots = document.querySelectorAll('[data-cb10-to]');
    for (var k = 0; k < dots.length; k++) {
      dots[k].classList.toggle('is-active', +dots[k].getAttribute('data-cb10-to') === i);
    }
  }

  // ---- init ---------------------------------------------------------------
  function init(opts) {
    if (_initialized) { return true; }
    opts = opts || {};

    try { _reduced = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches); } catch (e) {}

    _base = opts.basePath || '';
    _imgBase = opts.imageBase || (_base ? _base + 'images/home10/' : '');
    _vidBase = opts.videoBase || (_base ? _base + 'video/home10/' : '');

    stage = document.querySelector(opts.stage || '#cb10-stage');
    if (!stage) { return false; }

    pagesEl = [].slice.call(document.querySelectorAll('[data-cb10-page]'));
    _n = Math.min(SECTIONS.length, pagesEl.length || SECTIONS.length);
    if (!_n) { return false; }

    // Reduced motion gets the same eight rooms as plain photographs, stacked and
    // scrolled normally. Not a degraded version of the walk -- no walk at all,
    // which is the honest reading of the preference. The flat CSS is the
    // default and cb10-on is what turns the fixed stage on, so bailing here
    // leaves a perfectly good page rather than a broken one.
    if (_reduced) {
      document.documentElement.classList.add('cb10-static');
      return false;
    }

    buildStage();
    layout();
    document.documentElement.classList.add('cb10-on');
    initWatch();

    window.addEventListener('resize', layout, { passive: true });
    window.addEventListener('orientationchange', relayout, { passive: true });

    var dots = document.querySelectorAll('[data-cb10-to]');
    for (var k = 0; k < dots.length; k++) {
      dots[k].addEventListener('click', function () {
        var to = +this.getAttribute('data-cb10-to');
        // Land in the middle of the section, clear of both boundaries, so the
        // arrival is unambiguous and the walk fires once.
        window.scrollTo({ top: (to + 0.5) * _secH, behavior: 'smooth' });
      });
    }

    // Deterministic capture, as in Home 9: ?cb_g=<section>.<fraction> jumps to
    // an exact point in the walk. Synthesises a SCROLL POSITION rather than
    // forcing the clock, so the picture, the panel fade and the nav all derive
    // from one number and the shot is the same thing a reader would see.
    var g = parseFloat((location.search.match(/[?&]cb_g=([-\d.]+)/) || [])[1]);
    if (!isNaN(g)) {
      _capture = true;
      _captureG = clamp(g, 0, _n - 0.001);
      // Land on the target immediately -- the eased clock would otherwise still
      // be gliding toward it when the screenshot is taken.
      readScroll(); _g = _gRaw;
      document.documentElement.classList.add('cb10-capture');
    }

    _initialized = true;
    start();
    return true;
  }

  function start() {
    if (_started) { return; }
    _started = true;
    _lastT = 0;
    readScroll();
    _g = _gRaw;
    _rafId = raf(frame);
  }

  function stop() {
    _started = false;
    if (_rafId) { caf(_rafId); _rafId = null; }
  }

  window.CBHome10 = { init: init, start: start, stop: stop, sections: SECTIONS };

}(window, document));

/**
 * CB Legacy Luxury — Cinematic Scroll Homepage Controller
 *
 * Runs only when html.cb-cinematic is present (desktop, fine pointer, motion
 * allowed — set synchronously in header.php). In every other case the page is
 * a clean, fully-visible stacked layout and this file only wires the Property
 * Watch form. Any runtime error disables cinematic mode and restores the
 * visible stacked layout, so the homepage can never end up blank.
 */
(function () {
  'use strict';

  var docEl = document.documentElement;
  var stage = document.querySelector('[data-cb-scroll]');
  if (!stage) { return; }

  // Property Watch works in every mode.
  initPropertyWatch();

  var cinematic = docEl.classList.contains('cb-cinematic');
  var hasGSAP = window.gsap && window.ScrollTrigger;

  if (!cinematic || !hasGSAP) {
    docEl.classList.remove('cb-cinematic'); // ensure stacked styling/visibility
    return;
  }

  try {
    runCinematic();
  } catch (err) {
    fallbackToStacked();
    if (window.console) { console.warn('[cb] cinematic homepage disabled:', err); }
  }

  /* ------------------------------------------------------------------ */
  function fallbackToStacked() {
    docEl.classList.remove('cb-cinematic');
    try { if (window.__cbLenis && window.__cbLenis.destroy) { window.__cbLenis.destroy(); } } catch (e) {}
    Array.prototype.forEach.call(document.querySelectorAll('.cb-scene__reveal'), function (el) {
      el.style.opacity = '1';
      el.style.transform = 'none';
      el.style.clipPath = 'none';
    });
  }

  /* ------------------------------------------------------------------ */
  function runCinematic() {
    var gsap = window.gsap;
    var ST = window.ScrollTrigger;
    gsap.registerPlugin(ST);

    // ---- Lenis smooth scroll (document-scroll mode, driven by GSAP ticker) ----
    if (window.Lenis) {
      var lenis = new window.Lenis({ duration: 1.1, smoothWheel: true, wheelMultiplier: 1, touchMultiplier: 1.5 });
      window.__cbLenis = lenis;
      lenis.on('scroll', ST.update);
      gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
      gsap.ticker.lagSmoothing(0);
    }

    var scenes = gsap.utils.toArray('.cb-scene');
    var bgLayers = gsap.utils.toArray('.cb-stage-bg__layer');
    var navDots = gsap.utils.toArray('.cb-scene-nav__dot');
    var sceneNav = document.querySelector('.cb-scene-nav');
    var lightScenes = { 1: true, 2: true, 5: true }; // welcome, listings, communities

    function activateScene(i) {
      for (var b = 0; b < bgLayers.length; b++) {
        var on = (b === i);
        bgLayers[b].classList.toggle('is-active', on);
        var vid = bgLayers[b].querySelector('video');
        if (vid) {
          if (on) { var pr = vid.play(); if (pr && pr.catch) { pr.catch(function () {}); } }
          else { vid.pause(); }
        }
      }
      for (var d = 0; d < navDots.length; d++) { navDots[d].classList.toggle('is-active', d === i); }
      if (sceneNav) { sceneNav.classList.toggle('cb-scene-nav--on-light', !!lightScenes[i]); }
    }

    // Background crossfade + nav sync, one trigger per scene.
    scenes.forEach(function (scene, i) {
      ST.create({
        trigger: scene,
        start: 'top center',
        end: 'bottom center',
        onToggle: function (self) { if (self.isActive) { activateScene(i); } }
      });
    });
    activateScene(0);

    // Generic staggered reveal for every scene (mask-driven titles opt out).
    scenes.forEach(function (scene) {
      var items = scene.querySelectorAll('.cb-scene__reveal:not([data-mask])');
      if (!items.length) { return; }
      gsap.fromTo(items,
        { opacity: 0, y: 38 },
        {
          opacity: 1, y: 0, duration: 0.85, ease: 'power3.out', stagger: 0.1,
          scrollTrigger: { trigger: scene, start: 'top 74%' }
        });
    });

    // Scene-nav click → smooth scroll to that scene.
    navDots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        var idx = parseInt(dot.getAttribute('data-scene-to'), 10);
        var target = scenes[idx];
        if (!target) { return; }
        if (window.__cbLenis) { window.__cbLenis.scrollTo(target); }
        else { target.scrollIntoView({ behavior: 'smooth' }); }
      });
    });

    // Continuous camera push (dolly-in) on each non-pinned scene's background
    // as it scrolls past — constant cinematic movement, on stills or footage.
    var bgPinned = { 0: true, 4: true }; // hero + door drive their own bg motion
    bgLayers.forEach(function (layer, i) {
      if (bgPinned[i] || !scenes[i]) { return; }
      gsap.fromTo(layer, { scale: 1.14 }, {
        scale: 1, ease: 'none',
        scrollTrigger: { trigger: scenes[i], start: 'top bottom', end: 'bottom top', scrub: true }
      });
    });

    // ---- Signature cinematic beats ----
    buildHero(gsap);
    buildDoor(gsap);
    buildCounters(gsap, ST);
    buildValueTitle(gsap);
    buildRiver(gsap);

    refreshOnAssets(ST);
  }

  /* Scene 0 — pinned aerial depth-zoom as you descend from the sky. */
  function buildHero(gsap) {
    var hero = document.querySelector('.cb-scene--hero');
    if (!hero) { return; }
    var arrival = document.querySelector('.cb-stage-bg__layer--arrival');
    var video = hero.querySelector('.cb-hero-video video');
    var inner = hero.querySelector('.cb-scene__inner');
    var cue = document.getElementById('cb-scroll-cue');
    var tl = gsap.timeline({
      scrollTrigger: { trigger: hero, start: 'top top', end: '+=90%', scrub: 0.6, pin: true, anticipatePin: 1 }
    });
    if (arrival) { tl.fromTo(arrival, { scale: 1 }, { scale: 1.28, ease: 'none' }, 0); }
    if (video) { tl.fromTo(video, { scale: 1 }, { scale: 1.18, ease: 'none' }, 0); } // descend into the footage
    if (inner) { tl.to(inner, { yPercent: -12, ease: 'none' }, 0); }
    if (cue) { tl.to(cue, { opacity: 0, ease: 'none' }, 0); }
  }

  /* Scene 4 — pinned threshold: two panels part to reveal the buyer CTA. */
  function buildDoor(gsap) {
    var scene = document.querySelector('.cb-scene--door');
    if (!scene) { return; }
    var l = scene.querySelector('.cb-scene__door-panel--l');
    var r = scene.querySelector('.cb-scene__door-panel--r');
    var tl = gsap.timeline({
      scrollTrigger: { trigger: scene, start: 'top top', end: '+=85%', scrub: 0.5, pin: true, anticipatePin: 1 }
    });
    if (l) { tl.fromTo(l, { xPercent: 0 }, { xPercent: -101, ease: 'power2.inOut' }, 0); }
    if (r) { tl.fromTo(r, { xPercent: 0 }, { xPercent: 101, ease: 'power2.inOut' }, 0); }
  }

  /* Scene 3 — count up the legacy stats once when they scroll into view. */
  function buildCounters(gsap, ST) {
    var nums = document.querySelectorAll('.cb-scene--legacy [data-count]');
    nums.forEach(function (el) {
      var target = parseInt(el.getAttribute('data-count'), 10) || 0;
      var o = { v: 0 };
      ST.create({
        trigger: el, start: 'top 85%', once: true,
        onEnter: function () {
          gsap.to(o, {
            v: target, duration: 2, ease: 'power2.out',
            onUpdate: function () { el.textContent = Math.round(o.v).toLocaleString() + '+'; }
          });
        }
      });
    });
  }

  /* Scene 6 — the "What's my home worth?" headline wipes in left-to-right. */
  function buildValueTitle(gsap) {
    var t = document.querySelector('.cb-scene--value [data-mask]');
    if (!t) { return; }
    gsap.fromTo(t,
      { clipPath: 'inset(0 100% 0 0)', opacity: 1 },
      {
        clipPath: 'inset(0 0% 0 0)', opacity: 1, duration: 1.1, ease: 'power3.out',
        scrollTrigger: { trigger: t, start: 'top 80%' }
      });
  }

  /* Scene 5 — the Concho River lines draw themselves as the scene scrolls. */
  function buildRiver(gsap) {
    var paths = document.querySelectorAll('.cb-scene--communities .cb-river__path');
    if (!paths.length) { return; }
    gsap.fromTo(paths,
      { strokeDashoffset: 1 },
      {
        strokeDashoffset: 0, ease: 'none', stagger: 0.15,
        scrollTrigger: { trigger: '.cb-scene--communities', start: 'top 85%', end: 'center center', scrub: 0.5 }
      });
  }

  /* Recompute trigger positions after late-loading content changes height. */
  function refreshOnAssets(ST) {
    window.addEventListener('load', function () { ST.refresh(); });

    var imgs = document.querySelectorAll('.cb-scene--listings img, .cb-community-card__image');
    var pending = imgs.length;
    if (pending) {
      imgs.forEach(function (img) {
        if (img.complete) { if (--pending === 0) { ST.refresh(); } }
        else {
          img.addEventListener('load', function () { if (--pending === 0) { ST.refresh(); } });
          img.addEventListener('error', function () { if (--pending === 0) { ST.refresh(); } });
        }
      });
    }

    var tt = document.querySelector('.cb-tt-frame');
    if (tt && window.MutationObserver) {
      var mo = new MutationObserver(function () { ST.refresh(); });
      mo.observe(tt, { childList: true, subtree: true });
      setTimeout(function () { mo.disconnect(); }, 15000);
    }
  }

  /* ------------------------------------------------------------------ */
  function initPropertyWatch() {
    var form = document.querySelector('[data-cb-watch]');
    if (!form) { return; }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var field = form.querySelector('input[type="email"]');
      var email = field ? field.value.trim() : '';
      if (!email) { return; }
      try { localStorage.setItem('cb_property_watch', email); } catch (_) {}
      var wrap = form.closest('.cb-watch');
      if (wrap) {
        wrap.classList.add('is-done');
        var done = document.createElement('p');
        done.className = 'cb-watch__desc';
        done.style.marginTop = '0.25rem';
        done.textContent = "You're on the list — we'll email you when new San Angelo listings match.";
        form.parentNode.insertBefore(done, form.nextSibling);
      }
    });
  }
})();

/* =========================================================================
   home8.js -- Coldwell Banker "FLOATING PAGES" homepage (Home 8 preview)

   Forked from cb-corridor/corridor.js (Home 7), which merged Home 2's content
   with Home 5's hallway camera but left the content as flat 2D cards in normal
   document flow. Home 8 adds the layer Home 7's own template docblock promised
   and never built: each of the 8 sections is a PAGE that floats in the corridor
   and zooms as you scroll to it (activetheory.net).

   HOW THE PAGES FLOAT
   -------------------
   The camera never rotates -- it is a pure Z-dolly. A plane whose normal is +Z,
   viewed by an unrotated perspective camera, projects to a rectangle with
   UNIFORM SCALE: no keystone, no shear. So `translate + scale` is not an
   approximation of the correct projection, it IS the correct projection, exactly.
   That is why there is no CSS3DRenderer here: it would only buy rotation (which
   we forswear) and inter-panel z-sorting (moot -- one page is substantial at a
   time). See projectPages().

   Pages ride an AUTHORED depth curve on a camera leash (dCurve), NOT a fixed
   world Z. Pinning page i to W[i].z - D0 sounds tidier but the waypoint gaps are
   5,6,6,3,6,8,8 -- spacing chosen for CORRIDOR pacing -- so page 4 would spawn at
   s=0.80 (no zoom at all) while page 7 spawns at s=0.60, and the camera would fly
   THROUGH the page plane (d -> 0, s -> infinity). An authored curve gives all 8
   pages an identical zoom, a flat s==1 dwell, and an exit we control.

   SCROLL: no wheel listener, no hijack (kept from Home 5/7). Inner scroll absorbs
   the gesture -> document does not scroll -> camera does not advance. The pause
   and the resume are both native. `inert` on every non-reading page is the one
   switch that makes it work: it covers wheel targeting, pointer gating AND tab
   order. Without it a cursor over a distant thumbnail-sized page scrolls content
   nobody can read while the camera sits frozen.

   TRADE-OFF (deliberate): Home 5/7's "tall live content self-paces" is GONE.
   Spacer heights are authored constants with no relationship to content length;
   inner scrollers absorb length instead. In exchange, per-frame progress is pure
   math from cached rects -- ZERO layout reads per frame. Do not "fix" the spacers
   to track content; that would re-couple what this design deliberately separated.

   OWNERSHIP (four layers, one writer per property -- extends Home 7's card split):
     .cb8-page          transform <- PROJECTION (rAF, every frame). Nothing else.
       .cb8-page__float transform <- MOTION (idle float spring)
         .cb8-page__skin  opacity/filter <- projection alpha + Motion
           .cb8-page__scroll  <- THE BROWSER. Nothing animates this. Ever.
             .cb8-page__body  <- Motion may animate CHILDREN (stagger, counters)

   Progressive enhancement: init() adds html.cb8-on only after WebGL starts. The
   CSS polarity is INVERTED from Home 7 -- flat Home 2 layout is the default and
   every floating rule is nested under .cb8-on. Home 7's `transform:none` reduced-
   motion override would leave 8 fixed panels stacked on top of each other; the
   float layout is structurally incompatible with the flat one, so it must be a
   layout switch, not an override.

   Exposes window.CBHome8.init(opts). Requires window.THREE. window.Motion optional.
   ========================================================================= */
(function (window, document) {
  'use strict';

  if (window.CBHome8) { return; }

  var THREE = window.THREE;
  var M = window.Motion || null;
  var raf = window.requestAnimationFrame || function (cb) { return setTimeout(function () { cb(now()); }, 16); };
  var caf = window.cancelAnimationFrame || window.clearTimeout;
  function now() { return (window.performance && performance.now) ? performance.now() : Date.now(); }

  // ---- config -------------------------------------------------------------
  var AMB = 1600;            // ambient (static) dust
  var STRUCT = 1500;         // corridor-structure pool (condenses onto fixtures)
  var BURST = 1000;          // reserved page-burst slots
  var COUNT = AMB + STRUCT + BURST;
  var B0 = AMB + STRUCT;     // first burst index
  var CAMZ = 22;             // nebula camera distance
  var FOV = 58;
  var TAU = Math.PI * 2;
  var TAN_HALF = Math.tan(FOV * Math.PI / 360);   // tan(29deg) = 0.5543091

  // Page depth curve. D0 is BOTH the reading distance and the plane screenToWorld
  // already uses for bursts -- keeping them equal is what makes s == 1 at dwell.
  var D0 = 12, D_FAR = 34, D_NEAR = 4;
  var U_FAR = -1.20, U_IN = -0.35, U_OUT = 0.25, U_GONE = 1.20;
  var PAGE_W_MAX = 1100, PAGE_H_MAX = 720;

  var PAL = [
    [1.00, 1.00, 1.00], [1.00, 1.00, 1.00],
    [0.78, 0.86, 0.96], [0.72, 0.81, 0.92],
    [0.36, 0.56, 0.87], [0.16, 0.45, 1.00]
  ];
  var C_BLUE = [0.13, 0.27, 0.62], C_TIDE = [0.72, 0.81, 0.92], C_BRIGHT = [0.20, 0.48, 1.00];

  // Per-section camera waypoints {x,y,z,p=parallaxScale}. Camera looks toward
  // (z-12) with NO rotation -- "looking around" is faked via x/y offsets.
  var W = [
    { x: 0.0, y: 0.0, z: 2,   p: 0.40 },  // 0 threshold
    { x: -0.6, y: -0.3, z: -3, p: 0.70 }, // 1 reception
    { x: 1.2, y: 0.1, z: -9,  p: 0.95 },  // 2 listings wall
    { x: 0.0, y: 1.0, z: -15, p: 0.85 },  // 3 legacy arch
    { x: 0.0, y: 0.4, z: -18, p: 0.85 },  // 4 buyers beat
    { x: 0.0, y: 1.3, z: -24, p: 0.85 },  // 5 communities table
    { x: 0.8, y: -0.2, z: -32, p: 0.55 }, // 6 valuation desk
    { x: 0.0, y: 0.0, z: -40, p: 0.70 }   // 7 story wall / mark
  ];

  // Resting screen offset per page, in CSS px AT READING DISTANCE. Converted to
  // world units on resize, so the offset scales with s: a page drifts in from
  // near-centre, settles into its offset at s==1, then swings outward as it flies
  // past. Kept small -- these are pages to be read, not decorations.
  var PAGE_OFF = [
    { x:   0, y:  14 },  // 0 arrival
    { x: -46, y:  -8 },  // 1 welcome
    { x:  40, y:  10 },  // 2 listings
    { x: -24, y: -14 },  // 3 legacy
    { x:  32, y:   8 },  // 4 buy
    { x: -42, y: -10 },  // 5 communities
    { x:  46, y:  12 },  // 6 sell
    { x:   0, y:  -8 }   // 7 connect
  ];

  // Idle float params per page (Motion drives these; CSS keyframes are the
  // no-Motion fallback). Negative delays desynchronise the cycle.
  var FL_DUR = [8.5, 10.0, 9.2, 11.5, 7.8, 12.4, 9.8, 10.8];
  var FL_DEL = [0.0, -2.3, -4.1, -1.2, -5.6, -3.0, -6.2, -0.7];
  var FL_Y   = [10, 14, 8, 16, 12, 9, 15, 11];
  var FL_ROT = [0.6, -0.8, 0.5, -0.5, 0.9, -0.7, 0.4, -0.6];

  // ---- state --------------------------------------------------------------
  var canvas, renderer, scene, camera, geom, material, points;
  var pos, color, size, alpha, phase;
  var posAttr, alpAttr;

  var sHx = new Float32Array(STRUCT), sHy = new Float32Array(STRUCT), sHz = new Float32Array(STRUCT);
  var sRx = new Float32Array(STRUCT), sRy = new Float32Array(STRUCT), sRz = new Float32Array(STRUCT);
  var sStag = new Float32Array(STRUCT), sBase = new Float32Array(STRUCT);

  var bMode = new Float32Array(BURST), bAge = new Float32Array(BURST), bLife = new Float32Array(BURST), bA0 = new Float32Array(BURST);
  var bsx = new Float32Array(BURST), bsy = new Float32Array(BURST), bsz = new Float32Array(BURST);
  var btx = new Float32Array(BURST), bty = new Float32Array(BURST), btz = new Float32Array(BURST);
  var bvx = new Float32Array(BURST), bvy = new Float32Array(BURST), bvz = new Float32Array(BURST);
  var bPtr = 0;

  var corridorGroup = null, lineMat = null, _corridorReady = false;
  var signage = [];

  var vw = 0, vh = 0, dpr = 1, halfW = 0, halfH = 0, upp12 = 0;
  var rafId = null, lastT = 0, uTime = 0, started = false, initialized = false;
  var mouseNX = 0, mouseNY = 0, scrollY = 0;
  var oX = 0, oY = 0, oZ = CAMZ, oTX = 0, oTY = 0, oTZ = CAMZ, oP = 1;
  var corridorAssembly = 0, _structDirty = false;
  var _fpsAcc = 0, _fpsN = 0, _degraded = false;
  var _v = null, _dir = null, _out = null;
  var _monoUrl = '', _monoStackUrl = '';

  // pages
  var _pages = [];           // {el, float, skin, scrim, scroll, body, i, state, bodyOn, floatAnim, cardsIn}
  var _secTop = [], _secH = [];
  var _actTop = 0, _actH = 1;
  var _g = 0, _reading = 0;

  // capture mode
  var _capture = false, _captureG = 0, _captureFrames = 0;

  function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }
  function clamp01(v) { return v < 0 ? 0 : (v > 1 ? 1 : v); }
  function rand(a, b) { return a + Math.random() * (b - a); }
  function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }
  function smooth(t) { return t * t * (3 - 2 * t); }
  function band(x, a, b) { return smooth(clamp01((x - a) / (b - a))); }
  // Frame-rate independent lerp. `oX += (t-oX)*0.05` chases 2x faster at 120Hz,
  // and a page projected from a lagging camera reads as "the text looks blurry",
  // not "the timing is wrong" -- you get the bug report in the wrong vocabulary.
  function lerpK(rate, dt) { return 1 - Math.pow(1 - rate, dt * 60); }

  // ---- shaders (points) ---------------------------------------------------
  var VERT = [
    'attribute vec3 aColor;', 'attribute float aSize;', 'attribute float aAlpha;', 'attribute float aPhase;',
    'uniform float uTime;', 'uniform float uPixelRatio;',
    'varying float vAlpha;', 'varying vec3 vColor;',
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
    'varying float vAlpha;', 'varying vec3 vColor;',
    'void main(){',
    '  vec2 uv = gl_PointCoord - 0.5;',
    '  float d = dot(uv, uv);',
    '  if (d > 0.25) discard;',
    '  float a = smoothstep(0.25, 0.0, d);',
    '  gl_FragColor = vec4(vColor, a * vAlpha);',
    '}'
  ].join('\n');

  var LVERT = [
    'attribute vec3 aColor;', 'attribute float aGlow;',
    'uniform float uReveal;',
    'varying float vAlpha;', 'varying vec3 vColor;',
    'void main(){',
    '  vec4 mv = modelViewMatrix * vec4(position, 1.0);',
    '  gl_Position = projectionMatrix * mv;',
    '  float dist = -mv.z;',
    '  float depthFade = clamp((50.0 - dist) / 46.0, 0.0, 1.0);',
    '  vAlpha = uReveal * depthFade * mix(0.40, 0.95, aGlow);',
    '  vColor = aColor;',
    '}'
  ].join('\n');
  var LFRAG = [
    'precision mediump float;',
    'varying float vAlpha;', 'varying vec3 vColor;',
    'void main(){ gl_FragColor = vec4(vColor, vAlpha); }'
  ].join('\n');

  // ---- frustum + ambient --------------------------------------------------
  function computeExtents() {
    halfH = Math.tan((FOV * 0.5) * Math.PI / 180) * CAMZ;
    halfW = halfH * (vw / vh);
    upp12 = (2 * D0 * TAN_HALF) / vh;   // world units per CSS px at reading distance
  }
  function seedAmbient() {
    var i, c;
    for (i = 0; i < AMB; i++) {
      pos[i * 3] = rand(-halfW * 1.3, halfW * 1.3);
      pos[i * 3 + 1] = rand(-halfH * 1.7, halfH * 1.7);
      pos[i * 3 + 2] = (Math.random() < 0.42) ? rand(-46, 6) : rand(-14, 6);
      c = PAL[(Math.random() < 0.16) ? (2 + (Math.random() * 4 | 0)) : (Math.random() < 0.5 ? 0 : 2)];
      color[i * 3] = c[0]; color[i * 3 + 1] = c[1]; color[i * 3 + 2] = c[2];
      size[i] = rand(0.7, 2.1); alpha[i] = rand(0.35, 0.9); phase[i] = Math.random() * TAU;
    }
  }
  function parkStruct() {
    for (var i = 0; i < STRUCT; i++) {
      var k = AMB + i;
      pos[k * 3] = 0; pos[k * 3 + 1] = 0; pos[k * 3 + 2] = -999;
      color[k * 3] = 0.5; color[k * 3 + 1] = 0.7; color[k * 3 + 2] = 1;
      size[k] = 1.2; alpha[k] = 0; phase[k] = Math.random() * TAU;
    }
  }
  function initBurstBuffers() {
    for (var i = 0; i < BURST; i++) {
      var k = B0 + i;
      pos[k * 3] = 0; pos[k * 3 + 1] = 0; pos[k * 3 + 2] = -999;
      color[k * 3] = 1; color[k * 3 + 1] = 1; color[k * 3 + 2] = 1;
      size[k] = 1; alpha[k] = 0; phase[k] = Math.random() * TAU;
      bMode[i] = 0; bLife[i] = 0; bAge[i] = 0;
    }
  }

  // ---- corridor geometry (unchanged from Home 7) ---------------------------
  var EDG = [];
  function E(ax, ay, az, bx, by, bz, c, g) { EDG.push({ a: [ax, ay, az], b: [bx, by, bz], c: c, g: g || 0 }); }
  function boxE(cx, cy, cz, w, h, d, c, g) {
    var x0 = cx - w / 2, x1 = cx + w / 2, y0 = cy - h / 2, y1 = cy + h / 2, z0 = cz - d / 2, z1 = cz + d / 2;
    E(x0, y0, z0, x1, y0, z0, c, g); E(x1, y0, z0, x1, y0, z1, c, g); E(x1, y0, z1, x0, y0, z1, c, g); E(x0, y0, z1, x0, y0, z0, c, g);
    E(x0, y1, z0, x1, y1, z0, c, g); E(x1, y1, z0, x1, y1, z1, c, g); E(x1, y1, z1, x0, y1, z1, c, g); E(x0, y1, z1, x0, y1, z0, c, g);
    E(x0, y0, z0, x0, y1, z0, c, g); E(x1, y0, z0, x1, y1, z0, c, g); E(x1, y0, z1, x1, y1, z1, c, g); E(x0, y0, z1, x0, y1, z1, c, g);
  }
  function quadE(cx, cy, cz, w, h, c, g) {
    var x0 = cx - w / 2, x1 = cx + w / 2, y0 = cy - h / 2, y1 = cy + h / 2;
    E(x0, y0, cz, x1, y0, cz, c, g); E(x1, y0, cz, x1, y1, cz, c, g); E(x1, y1, cz, x0, y1, cz, c, g); E(x0, y1, cz, x0, y0, cz, c, g);
  }
  function quadXZ(cx, y, cz, w, d, c, g) {
    var x0 = cx - w / 2, x1 = cx + w / 2, z0 = cz - d / 2, z1 = cz + d / 2;
    E(x0, y, z0, x1, y, z0, c, g); E(x1, y, z0, x1, y, z1, c, g); E(x1, y, z1, x0, y, z1, c, g); E(x0, y, z1, x0, y, z0, c, g);
  }
  function arcE(cx, cy, cz, r, a0, a1, segs, c, g) {
    var prev = null, i, a, x, y;
    for (i = 0; i <= segs; i++) { a = a0 + (a1 - a0) * i / segs; x = cx + Math.cos(a) * r; y = cy + Math.sin(a) * r; if (prev) { E(prev[0], prev[1], cz, x, y, cz, c, g); } prev = [x, y]; }
  }

  function defineCorridor() {
    EDG.length = 0;
    // floor light-spine + rails (the aisle)
    E(0, -5, 4, 0, -5, -50, C_BRIGHT, 1);
    E(-6, -5, 4, -6, -5, -50, C_TIDE, 0);
    E(6, -5, 4, 6, -5, -50, C_TIDE, 0);
    // 0 threshold portal
    boxE(0, 0, -3, 7, 10, 0.4, C_TIDE, 1);
    E(0, -4.6, -2.8, 0, 4.6, -2.8, C_TIDE, 0);
    quadE(-1.8, 0, -2.9, 3.0, 9, C_TIDE, 0); quadE(1.8, 0, -2.9, 3.0, 9, C_TIDE, 0);
    // 1 reception
    boxE(-1, -3, -9, 7, 1.4, 2, C_TIDE, 1);
    boxE(2.6, -2.7, -10, 2, 1.4, 3, C_TIDE, 0);
    quadE(0, 0, -11, 10, 5, C_BLUE, 0);
    arcE(-3.4, 1.6, -10.6, 0.45, 0, TAU, 18, C_BRIGHT, 0);
    // 2 listings wall (3x2 frames)
    quadE(0, 2, -16, 15, 7, C_BLUE, 0);
    var fx = [-5, 0, 5], fy = [3.6, 0.4], r, cc;
    for (r = 0; r < fx.length; r++) {
      for (cc = 0; cc < fy.length; cc++) {
        quadE(fx[r], fy[cc], -15.8, 3, 2.2, (r === 1 && cc === 0) ? C_BRIGHT : C_TIDE, (r === 1 && cc === 0) ? 1 : 0);
      }
    }
    E(-6.6, -1.7, -15.7, 6.6, -1.7, -15.7, C_BRIGHT, 1);
    // 3 legacy arch (camera passes under) + columns + ceiling band
    arcE(0, 0, -22, 6, 0, Math.PI, 38, C_TIDE, 1);
    var ax = [-6, -2, 2, 6], q;
    for (q = 0; q < ax.length; q++) { boxE(ax[q], 0, -22, 0.6, 9, 0.6, C_TIDE, 0); }
    quadE(0, 5.4, -22, 12, 0.6, C_BLUE, 0);
    // 4 buyers beat -- a gateway frame so the corridor never feels empty
    quadE(0, 0.5, -27, 9, 8, C_BLUE, 0);
    // 5 communities planning table (horizontal lattice + 6 pillars + river)
    quadXZ(0, -3.5, -31, 12, 7, C_TIDE, 1);
    var gi;
    for (gi = 1; gi < 4; gi++) { E(-6 + gi * 3, -3.5, -27.5, -6 + gi * 3, -3.5, -34.5, C_TIDE, 0); }
    for (gi = 1; gi < 3; gi++) { E(-6, -3.5, -27.5 + gi * 2.33, 6, -3.5, -27.5 + gi * 2.33, C_TIDE, 0); }
    var px = [-4.2, -1.5, 1.0, 3.6, -3.0, 4.4], pz = [-29, -30.5, -29.8, -31.8, -33, -32.4], pp;
    for (pp = 0; pp < px.length; pp++) { E(px[pp], -3.5, pz[pp], px[pp], -1.2, pz[pp], C_BRIGHT, (pp % 2) ? 0 : 1); }
    E(-5.5, -3.48, -34, -2, -3.48, -31, C_BRIGHT, 0); E(-2, -3.48, -31, 2, -3.48, -33, C_BRIGHT, 0); E(2, -3.48, -33, 5.5, -3.48, -29.5, C_BRIGHT, 0);
    // 6 valuation desk + angled screen + partition + gauge
    boxE(1.5, -2, -38, 3.6, 1, 1.8, C_TIDE, 1);
    quadE(1.5, -0.7, -37.4, 2, 1.3, C_BRIGHT, 1);
    quadE(1.4, -0.4, -39, 6, 2.6, C_BLUE, 0);
    E(-1.6, -2.4, -37.6, -1.6, 0.2, -37.6, C_BRIGHT, 1);
    // 7 story wall + 3 frames + (monogram quad added as signage)
    quadE(0, 1, -46, 14, 5, C_BLUE, 0);
    var sx = [-4.4, 0, 4.4], sf;
    for (sf = 0; sf < sx.length; sf++) { quadE(sx[sf], 1, -45.8, 3, 2.2, C_TIDE, 0); }
    E(0, -1.6, -45.9, 0, -1.6, -49, C_BRIGHT, 1);
  }

  function buildCorridorFromEdges() {
    var n = EDG.length, lp = new Float32Array(n * 6), lc = new Float32Array(n * 6), lg = new Float32Array(n * 2);
    var i, e;
    for (i = 0; i < n; i++) {
      e = EDG[i];
      lp[i * 6] = e.a[0]; lp[i * 6 + 1] = e.a[1]; lp[i * 6 + 2] = e.a[2];
      lp[i * 6 + 3] = e.b[0]; lp[i * 6 + 4] = e.b[1]; lp[i * 6 + 5] = e.b[2];
      lc[i * 6] = e.c[0]; lc[i * 6 + 1] = e.c[1]; lc[i * 6 + 2] = e.c[2];
      lc[i * 6 + 3] = e.c[0]; lc[i * 6 + 4] = e.c[1]; lc[i * 6 + 5] = e.c[2];
      lg[i * 2] = e.g; lg[i * 2 + 1] = e.g;
    }
    var lgeom = new THREE.BufferGeometry();
    lgeom.setAttribute('position', new THREE.BufferAttribute(lp, 3));
    lgeom.setAttribute('aColor', new THREE.BufferAttribute(lc, 3));
    lgeom.setAttribute('aGlow', new THREE.BufferAttribute(lg, 1));
    lineMat = new THREE.ShaderMaterial({
      uniforms: { uReveal: { value: 0 } }, vertexShader: LVERT, fragmentShader: LFRAG,
      transparent: true, blending: THREE.AdditiveBlending, depthTest: false, depthWrite: false
    });
    var lines = new THREE.LineSegments(lgeom, lineMat);
    lines.frustumCulled = false;
    corridorGroup.add(lines);

    var lens = [], total = 0, dx, dy, dz, L;
    for (i = 0; i < n; i++) {
      e = EDG[i]; dx = e.b[0] - e.a[0]; dy = e.b[1] - e.a[1]; dz = e.b[2] - e.a[2];
      L = Math.sqrt(dx * dx + dy * dy + dz * dz); lens.push(L); total += L;
    }
    var idx = 0;
    for (i = 0; i < n && idx < STRUCT; i++) {
      e = EDG[i];
      var cnt = Math.max(2, Math.round(STRUCT * (lens[i] / total)));
      for (var j = 0; j < cnt && idx < STRUCT; j++) {
        var t = (cnt > 1) ? j / (cnt - 1) : 0.5;
        var hx = e.a[0] + (e.b[0] - e.a[0]) * t;
        var hy = e.a[1] + (e.b[1] - e.a[1]) * t;
        var hz = e.a[2] + (e.b[2] - e.a[2]) * t;
        sHx[idx] = hx; sHy[idx] = hy; sHz[idx] = hz;
        sRx[idx] = rand(-halfW * 1.3, halfW * 1.3);
        sRy[idx] = rand(-halfH * 1.7, halfH * 1.7);
        sRz[idx] = rand(-46, 6);
        sStag[idx] = clamp01((4 - hz) / 50);
        sBase[idx] = rand(0.34, 0.78);
        var k = AMB + idx;
        color[k * 3] = e.c[0] * 0.6 + 0.4; color[k * 3 + 1] = e.c[1] * 0.6 + 0.4; color[k * 3 + 2] = e.c[2] * 0.5 + 0.5;
        size[k] = rand(0.7, 1.7); phase[k] = Math.random() * TAU;
        pos[k * 3] = sRx[idx]; pos[k * 3 + 1] = sRy[idx]; pos[k * 3 + 2] = sRz[idx];
        alpha[k] = sBase[idx] * 0.5;
        idx++;
      }
    }
    for (; idx < STRUCT; idx++) { var kk = AMB + idx; alpha[kk] = 0; pos[kk * 3 + 2] = -999; sStag[idx] = 2; }
  }

  function bakeMonogram(url, x, y, z, h) {
    if (!url) { return; }
    var img = new Image();
    img.onload = function () {
      try {
        var ar = (img.width && img.height) ? img.width / img.height : 1;
        var c = document.createElement('canvas');
        c.width = 512; c.height = Math.max(8, Math.round(512 / ar));
        var g = c.getContext('2d');
        g.drawImage(img, 0, 0, c.width, c.height);
        // Render the OFFICIAL lockup in its own (white) color -- do NOT recolor the
        // mark (BRAND.md 5/9 forbid altering the lockup). AdditiveBlending over the
        // navy field already gives it the glow.
        var tex = new THREE.CanvasTexture(c); tex.needsUpdate = true;
        var mat = new THREE.MeshBasicMaterial({ map: tex, transparent: true, opacity: 0, blending: THREE.AdditiveBlending, depthTest: false, depthWrite: false });
        var mesh = new THREE.Mesh(new THREE.PlaneGeometry(h * ar, h), mat);
        mesh.position.set(x, y, z); mesh.frustumCulled = false;
        corridorGroup.add(mesh);
        signage.push({ mesh: mesh, mat: mat, z: z });
      } catch (e) {}
    };
    img.onerror = function () {};
    img.src = url;
  }

  function buildCorridor() {
    corridorGroup = new THREE.Group();
    defineCorridor();
    buildCorridorFromEdges();
    bakeMonogram(_monoUrl, 0, 0.4, -10.85, 2.6);
    bakeMonogram(_monoStackUrl, 0, 1.2, -45.9, 4.6);
    scene.add(corridorGroup);
    _corridorReady = true;
  }

  function updateStruct() {
    for (var i = 0; i < STRUCT; i++) {
      if (sStag[i] > 1.5) { continue; }
      var k = AMB + i;
      var f = easeOutCubic(clamp01((corridorAssembly - sStag[i] * 0.5) / 0.5));
      pos[k * 3] = sRx[i] + (sHx[i] - sRx[i]) * f;
      pos[k * 3 + 1] = sRy[i] + (sHy[i] - sRy[i]) * f;
      pos[k * 3 + 2] = sRz[i] + (sHz[i] - sRz[i]) * f;
      alpha[k] = sBase[i] * (0.5 + 0.65 * f);
    }
    posAttr.needsUpdate = true; alpAttr.needsUpdate = true;
  }
  function updateSignage() {
    for (var i = 0; i < signage.length; i++) {
      var s = signage[i];
      var on = clamp01(1 - (Math.abs(camera.position.z - s.z) - 4) / 12);
      s.mat.opacity = on * corridorAssembly;
    }
  }

  // ---- screen px -> world (plane D0 in front of the dollying camera) -------
  // Exact inverse of projectPages()'s maths -- it only works because the camera
  // is unrotated. Keep both sides reading from D0/TAN_HALF; two hand-derived
  // inverses drift the first time somebody touches the FOV.
  function screenToWorld(px, py) {
    camera.updateMatrixWorld();
    _v.set((px / vw) * 2 - 1, -((py / vh) * 2 - 1), 0.5).unproject(camera);
    _dir.copy(_v).sub(camera.position).normalize();
    var t = (-D0) / _dir.z;
    _out.copy(camera.position).add(_dir.multiplyScalar(t));
    return _out;
  }

  // ---- bursts -------------------------------------------------------------
  function spawnBurst(rect, mode, isFrame) {
    if (!rect || rect.width <= 0 || rect.height <= 0) { return; }
    var x0 = rect.left, w = rect.width;
    var top = Math.max(rect.top, -60), bot = Math.min(rect.top + rect.height, vh + 60);
    if (bot <= top) { return; }
    var area = w * (bot - top), target = isFrame ? 34 : 50;
    var spacing = Math.max(isFrame ? 46 : 28, Math.sqrt(area / target));
    var cw = screenToWorld(x0 + w / 2, top + (bot - top) / 2), ccx = cw.x, ccy = cw.y;
    var spark = isFrame ? 0.05 : 0.13;
    for (var gy = top; gy < bot; gy += spacing) {
      for (var gx = x0; gx < x0 + w; gx += spacing) {
        var wld = screenToWorld(gx + (Math.random() - 0.5) * spacing, gy + (Math.random() - 0.5) * spacing);
        var i = bPtr; bPtr = (bPtr + 1) % BURST; var k = B0 + i;
        var c = PAL[(Math.random() < spark) ? 5 : (Math.random() < 0.5 ? 0 : 2 + (Math.random() * 2 | 0))];
        color[k * 3] = c[0]; color[k * 3 + 1] = c[1]; color[k * 3 + 2] = c[2];
        size[k] = isFrame ? rand(0.6, 1.6) : rand(0.8, 2.1);
        bA0[i] = rand(0.5, 1.0); bAge[i] = 0;
        if (mode === 'out') {
          bMode[i] = 1; bLife[i] = rand(1.0, 1.9);
          pos[k * 3] = wld.x; pos[k * 3 + 1] = wld.y; pos[k * 3 + 2] = wld.z + rand(-1, 1.5);
          var ang = Math.atan2(wld.y - ccy, wld.x - ccx) + rand(-0.6, 0.6), spd = rand(1.5, 7.0);
          bvx[i] = Math.cos(ang) * spd; bvy[i] = Math.sin(ang) * spd + rand(1.0, 3.5); bvz[i] = rand(-2.5, 3.5);
        } else {
          bMode[i] = 2; bLife[i] = rand(0.55, 1.0);
          btx[i] = wld.x; bty[i] = wld.y; btz[i] = wld.z + rand(-0.8, 0.8);
          var a2 = Math.random() * TAU, rr = rand(2.5, 7.5);
          bsx[i] = wld.x + Math.cos(a2) * rr; bsy[i] = wld.y + Math.sin(a2) * rr; bsz[i] = wld.z + rand(-6, 4);
          pos[k * 3] = bsx[i]; pos[k * 3 + 1] = bsy[i]; pos[k * 3 + 2] = bsz[i];
        }
        alpha[k] = bA0[i];
      }
    }
    posAttr.needsUpdate = true; alpAttr.needsUpdate = true;
  }
  function updateBursts(dt) {
    var any = false;
    for (var i = 0; i < BURST; i++) {
      if (bMode[i] === 0) { continue; }
      bAge[i] += dt; var t = bAge[i] / bLife[i], k = B0 + i;
      if (t >= 1) { bMode[i] = 0; alpha[k] = 0; pos[k * 3 + 2] = -999; any = true; continue; }
      if (bMode[i] === 1) {
        pos[k * 3] += bvx[i] * dt; pos[k * 3 + 1] += bvy[i] * dt; pos[k * 3 + 2] += bvz[i] * dt;
        bvy[i] -= 1.4 * dt; bvx[i] *= (1 - 0.5 * dt); bvz[i] *= (1 - 0.5 * dt);
        alpha[k] = bA0[i] * (1 - t);
      } else {
        var e = easeOutCubic(t);
        pos[k * 3] = bsx[i] + (btx[i] - bsx[i]) * e; pos[k * 3 + 1] = bsy[i] + (bty[i] - bsy[i]) * e; pos[k * 3 + 2] = bsz[i] + (btz[i] - bsz[i]) * e;
        alpha[k] = bA0[i] * (1 - t * t);
      }
      any = true;
    }
    if (any) { posAttr.needsUpdate = true; alpAttr.needsUpdate = true; }
  }

  // ---- page curves --------------------------------------------------------
  function dCurve(u) {
    if (u <= U_IN) {
      var t = clamp01((u - U_FAR) / (U_IN - U_FAR));
      return D_FAR + (D0 - D_FAR) * smooth(t);        // 34 -> 12, eased
    }
    if (u <= U_OUT) { return D0; }                     // flat dwell: s == 1
    var t2 = clamp01((u - U_OUT) / (U_GONE - U_OUT));
    return D0 + (D_NEAR - D0) * (t2 * t2);             // 12 -> 4, accelerating past
  }
  function aCurve(u) {
    return band(u, U_FAR, U_FAR + 0.4) * (1 - band(u, 0.5, 1.15));
  }
  function readAmount(u) {
    return band(u, U_IN - 0.2, U_IN) * (1 - band(u, U_OUT, U_OUT + 0.2));
  }
  function stateFor(u) {
    if (u <= U_FAR || u > U_GONE) { return 'far'; }
    if (u <= U_IN) { return 'approach'; }
    if (u <= U_OUT) { return 'reading'; }
    return 'exiting';
  }

  // ---- geometry cache (authored spacer heights -> zero layout reads/frame) --
  function cacheRects() {
    var sy = window.pageYOffset || 0, i, r;
    _secTop.length = 0; _secH.length = 0;
    for (i = 0; i < _pages.length; i++) {
      r = _pages[i].sec.getBoundingClientRect();
      _secTop.push(r.top + sy); _secH.push(Math.max(1, r.height));
    }
    if (_act) {
      r = _act.getBoundingClientRect();
      _actTop = r.top + sy; _actH = Math.max(1, r.height);
    }
  }
  var _act = null;

  function computeG() {
    var center = scrollY + vh * 0.5;
    var n = _secTop.length, i, top, h, gg = 0;
    if (!n) { return 0; }
    for (i = 0; i < n; i++) {
      top = _secTop[i]; h = _secH[i];
      if (center < top) { gg = i - 0.5; break; }
      if (center < top + h) { gg = i + ((center - top) / h - 0.5); break; }
      if (i === n - 1) { gg = n - 0.5; }
    }
    // g == i at the CENTRE of section i, so page i reads while you are in the
    // middle of its own section. Clamped, so page 0 is present on arrival and
    // page 7 holds at the bottom rather than flying past into nothing.
    return clamp(gg, 0, n - 1);
  }

  function computeCamera() {
    if (!_corridorReady || !_secTop.length) {
      oTX = mouseNX * 2.6; oTY = mouseNY * 1.9; oTZ = CAMZ; oP = 1; return;
    }
    var lo = Math.floor(_g), hi = Math.min(lo + 1, W.length - 1), sp = smooth(_g - lo);
    var a = W[lo] || W[0], b = W[hi] || a;
    var sx = a.x + (b.x - a.x) * sp, sy2 = a.y + (b.y - a.y) * sp;
    var sz = a.z + (b.z - a.z) * sp, spp = a.p + (b.p - a.p) * sp;
    var center = scrollY + vh * 0.5;
    var ap = clamp01((center - _actTop) / _actH);
    var asm = band(ap, 0.0, 0.10) * (1 - band(ap, 0.90, 1.0));
    oTZ = CAMZ + (sz - CAMZ) * asm;
    oTX = sx * asm; oTY = sy2 * asm;
    // Damp parallax + idle drift at the CAMERA while a page is being read. Damping
    // it at the page would make the page a lie (it would stop obeying the
    // projection); damping the camera makes the whole world hold its breath, which
    // is the better beat anyway.
    oP = (1 - (1 - spp) * asm) * (1 - _reading);
    if (Math.abs(asm - corridorAssembly) > 0.0006) { _structDirty = true; }
    corridorAssembly = asm;
  }

  // ---- page state ---------------------------------------------------------
  function fireResize() {
    var ev;
    try { ev = new Event('resize'); }
    catch (e) { ev = document.createEvent('Event'); ev.initEvent('resize', true, false); }
    window.dispatchEvent(ev);
  }

  function revealBody(p) {
    if (p.bodyOn) { return; }
    p.bodyOn = true;
    p.el.classList.add('is-body-on');
    // Card reveal and counters hang off VISIBILITY, not off the 'reading' state.
    // Tying them to 'reading' looks identical while scrolling forwards and is
    // silently broken on arrival: land at g=3.5 (reload mid-page, scroll
    // restoration, a deep link, capture mode) and page 3 is already 'exiting', so
    // 'reading' never fires and every card stays at opacity:0 under a heading
    // that renders fine. Revealing with the body means the page is correct at any
    // entry point, and the stagger now plays as the page zooms in -- which is the
    // beat we wanted anyway.
    staggerCards(p);
    countUp(p);
    // Shortcode JS ([cb_listings], [cb_testimonials]) commonly measures its
    // container on init. Under content-visibility:hidden it measures 0 and stays
    // broken forever, so nudge it once the subtree actually has a box.
    setTimeout(fireResize, 60);
  }
  function hideBody(p) {
    if (!p.bodyOn) { return; }
    p.bodyOn = false;
    p.el.classList.remove('is-body-on');
  }

  function staggerCards(p) {
    if (p.cardsIn) { return; }
    p.cardsIn = true;
    var cards = p.el.querySelectorAll('[data-cb8-card]');
    if (!cards.length) { return; }
    var i;

    // .is-in FIRST: it is the resting state, and under html.cb8-motion the CSS
    // transition is off, so there is no flash and nothing to fight. Motion's
    // inline writes override during playback and CSS holds the end state when it
    // releases -- no commitStyles, no dependence on WAAPI fill behaviour.
    for (i = 0; i < cards.length; i++) { cards[i].classList.add('is-in'); }

    // Capture mode wants the RESTING state, not an animation caught mid-flight.
    // Motion's animate() writes inline opacity:0 on its first tick and advances on
    // its own clock, which --virtual-time-budget does not drive -- so a headless
    // shot would freeze every card at opacity:0 and the page would photograph
    // empty. .is-in above is already the final state; just stop here.
    if (_capture) { return; }

    if (M && M.animate) {
      try {
        M.animate(cards,
          { opacity: [0, 1], transform: ['translateY(22px) scale(.965)', 'none'] },
          { delay: M.stagger ? M.stagger(0.055) : 0, duration: 0.62, easing: [0.22, 0.61, 0.36, 1] }
        );
        return;
      } catch (e) {}
    }
    // No Motion: the CSS transition is live, so stagger with transitionDelay.
    for (i = 0; i < cards.length; i++) { cards[i].style.transitionDelay = (i * 0.055) + 's'; }
  }
  function resetCards(p) {
    if (!p.cardsIn) { return; }
    p.cardsIn = false;
    var cards = p.el.querySelectorAll('[data-cb8-card]');
    for (var i = 0; i < cards.length; i++) {
      cards[i].classList.remove('is-in');
      cards[i].style.opacity = ''; cards[i].style.transform = ''; cards[i].style.transitionDelay = '';
    }
  }

  function countUp(p) {
    var nums = p.el.querySelectorAll('.cb8-stat__num[data-count]');
    for (var i = 0; i < nums.length; i++) {
      (function (n) {
        if (n.__c) { return; } n.__c = true;
        var target = parseInt(n.getAttribute('data-count'), 10) || 0;
        if (_capture) { n.textContent = target.toLocaleString(); return; }
        if (M && M.animate) {
          try {
            M.animate(0, target, {
              duration: 1.6, easing: 'ease-out',
              onUpdate: function (v) { n.textContent = Math.round(v).toLocaleString(); }
            });
            return;
          } catch (e) {}
        }
        var t0 = now(), dur = 1600;
        (function step(t) {
          var q = clamp((t - t0) / dur, 0, 1);
          n.textContent = Math.round(target * easeOutCubic(q)).toLocaleString();
          if (q < 1) { raf(step); }
        })(t0);
      })(nums[i]);
    }
  }

  function startFloat(p) {
    if (p.floatAnim || !M || !M.animate) { return; }
    try {
      p.floatAnim = M.animate(p.floatEl,
        { transform: [
            'translateY(' + (-FL_Y[p.i] * 0.5) + 'px) rotate(' + (-FL_ROT[p.i]) + 'deg)',
            'translateY(' + FL_Y[p.i] + 'px) rotate(' + FL_ROT[p.i] + 'deg)'
          ] },
        { duration: FL_DUR[p.i], repeat: Infinity, direction: 'alternate', easing: 'ease-in-out' }
      );
      // Negative delay equivalent: start each page mid-cycle so nothing syncs.
      if (p.floatAnim && typeof p.floatAnim.currentTime === 'number') {
        p.floatAnim.currentTime = Math.abs(FL_DEL[p.i]) * 1000;
      }
    } catch (e) { p.floatAnim = null; }
  }

  function setState(p, next) {
    if (p.state === next) { return; }
    var prev = p.state;
    p.state = next;
    p.el.setAttribute('data-cb8-state', next);

    // `inert` is the ONE switch: wheel targeting + pointer gating + tab order.
    // Exactly one page is non-inert, so a cursor over a distant page never traps
    // the wheel in an unreadable scroller, and Tab from the header cannot land in
    // page 7 while the camera is at section 0.
    var live = (next === 'reading');
    try { p.el.inert = !live; } catch (e) {}
    if (!live) { p.el.setAttribute('aria-hidden', 'true'); }
    else { p.el.removeAttribute('aria-hidden'); }

    if (next === 'far') {
      hideBody(p); resetCards(p);
      p.el.style.visibility = 'hidden';
    } else {
      p.el.style.visibility = '';
      // Bursts are the one thing that SHOULD stay on the state edge: they are a
      // one-shot flourish tying the particle field to the page, and firing them
      // on a cold arrival would look like a glitch rather than an arrival.
      if (next === 'reading' && !_capture && !document.hidden && prev === 'approach') {
        spawnBurst(p.el.getBoundingClientRect(), 'in', true);
      }
      if (next === 'exiting' && !_capture && !document.hidden && prev === 'reading') {
        spawnBurst(p.el.getBoundingClientRect(), 'out', true);
      }
    }
  }

  // ---- projection ---------------------------------------------------------
  // upp = world units per CSS px at depth d. CSS px throughout: setPixelRatio only
  // changes the drawing buffer, so multiplying by devicePixelRatio here would be a
  // bug. vw/vh come from documentElement.clientWidth/Height, which EXCLUDE the
  // scrollbar -- window.innerWidth would put every page ~15px off-axis from the
  // corridor on Windows, since this page always has a scrollbar.
  function projectPages() {
    var cx = camera.position.x, cy = camera.position.y;
    var i, p, u, d, s, upp, sx, sy, a, blur, scrim;
    for (i = 0; i < _pages.length; i++) {
      p = _pages[i];
      u = _g - i;
      if (u <= U_FAR || u > U_GONE) { setState(p, 'far'); continue; }

      d = dCurve(u);
      s = D0 / d;                                  // == 1 exactly at d == D0
      upp = (2 * d * TAN_HALF) / vh;
      sx = (p.wx - cx) / upp;                      // CSS px from viewport centre
      sy = -(p.wy - cy) / upp;
      a = aCurve(u);

      setState(p, stateFor(u));

      // Subpixel on purpose -- rounding jitters visibly during the fly-past.
      p.el.style.transform = 'translate3d(' + sx + 'px,' + sy + 'px,0) scale(' + s + ')';
      p.el.style.zIndex = String(1000 - Math.round(d * 10));
      p.skin.style.opacity = String(a);

      // Chrome stops updating raster scale for will-change:transform layers, so a
      // page that entered at s=0.35 with it pinned would rasterise its text at
      // 0.35x and STAY soft at s==1. Drop the hint while dwelling: nothing is
      // moving, so the one-time repaint is invisible and re-rasterises at 1:1.
      p.el.style.willChange = (p.state === 'reading') ? 'auto' : 'transform';

      // Depth of field. Opacity + a static scrim carry the long approach (blur on
      // a live-DOM subtree forces its own render surface); animated blur is
      // reserved for the short exit, quantised so the radius does not re-rasterise
      // every frame.
      if (s > 1.05) {
        blur = Math.min(5, (s - 1) * 2.5);
        blur = Math.round(blur * 2) / 2;
        p.skin.style.filter = blur > 0.4 ? ('blur(' + blur + 'px) brightness(1.15)') : '';
      } else {
        p.skin.style.filter = '';
      }
      if (p.scrim) {
        scrim = clamp01((1 - s) * 1.15);
        p.scrim.style.opacity = String(scrim);
      }

      // LOD: flip the live body in at s ~= 0.6, well before anybody reads, so the
      // relayout hitch lands during fast camera motion instead of at the moment
      // the page settles. content-visibility:auto cannot work here -- a fixed page
      // always intersects the viewport, so it would never kick in.
      if (s > 0.6) { revealBody(p); }
    }
  }

  // ---- loop ---------------------------------------------------------------
  function frame(t) {
    if (!_capture) { rafId = raf(frame); }
    var dt = lastT ? (t - lastT) / 1000 : 0.016; lastT = t;
    if (dt > 0.05) { dt = 0.05; }   // 20fps floor: stops tab-restore explosions

    // Read scroll FIRST, before any writes this frame -- no forced layout.
    if (_capture) {
      // scrollY was synthesised once from the target g (see captureScrollFor), so
      // computeG() runs normally here. Overriding _g directly instead would leave
      // the corridor's assembly envelope reading the REAL scroll position -- at
      // scrollY=0 that is asm~0.4, i.e. the shot shows a 40%-assembled corridor
      // while claiming to be mid-hallway. Everything must come off one number.
      uTime = 2.0; mouseNX = 0; mouseNY = 0;
    } else {
      uTime += dt;
      scrollY = window.pageYOffset || 0;
    }
    _g = computeG();
    material.uniforms.uTime.value = uTime;

    // readingness drives the parallax damp; compute before computeCamera().
    var rd = 0, i;
    for (i = 0; i < _pages.length; i++) { rd = Math.max(rd, readAmount(_g - i)); }
    _reading = rd;

    computeCamera();

    if (!_degraded && corridorAssembly > 0.05 && !_capture) {
      _fpsAcc += dt; _fpsN++;
      if (_fpsN >= 90) {
        if ((_fpsAcc / _fpsN) > 0.024 && dpr > 1.0) { dpr = 1.0; renderer.setPixelRatio(dpr); material.uniforms.uPixelRatio.value = dpr; _degraded = true; }
        else { _fpsAcc = 0; _fpsN = 0; }
      }
    }

    var driftX = Math.sin(uTime * 0.13) * 1.1, driftY = Math.cos(uTime * 0.10) * 0.8;
    if (_capture) {
      oX = oTX; oY = oTY; oZ = oTZ; driftX = 0; driftY = 0;
    } else {
      var k = lerpK(0.05, dt);
      oX += (oTX - oX) * k; oY += (oTY - oY) * k; oZ += (oTZ - oZ) * k;
    }
    var px = (mouseNX * 2.6 + driftX) * oP, py = (mouseNY * 1.9 + driftY) * oP;
    camera.position.set(oX + px, oY + py, oZ);

    if (_corridorReady) {
      if (_structDirty) { updateStruct(); _structDirty = false; }
      if (corridorAssembly > 0.001 || lineMat.uniforms.uReveal.value > 0.001) {
        lineMat.uniforms.uReveal.value += (corridorAssembly - lineMat.uniforms.uReveal.value) * (_capture ? 1 : lerpK(0.12, dt));
        updateSignage();
      }
    }
    updateBursts(dt);

    // Pages MUST project from the same camera state, in the same frame, as the
    // render. One frame of slip during a fly-past is 10-20px of visible offset
    // against the wireframe. Never a second rAF.
    projectPages();
    syncNav();
    renderer.render(scene, camera);
  }
  function start() { if (!started) { started = true; lastT = 0; rafId = raf(frame); } }
  function stop() { if (started) { started = false; caf(rafId); rafId = null; } }

  function sizePages() {
    var w = Math.min(PAGE_W_MAX, Math.round(vw * 0.84));
    var h = Math.min(PAGE_H_MAX, Math.round(vh * 0.80));
    for (var i = 0; i < _pages.length; i++) {
      _pages[i].el.style.width = w + 'px';
      _pages[i].el.style.height = h + 'px';
      // Resting offset authored in CSS px at reading distance -> world units.
      _pages[i].wx = W[i].x + PAGE_OFF[i].x * upp12;
      _pages[i].wy = W[i].y - PAGE_OFF[i].y * upp12;
    }
  }

  function resize() {
    // clientWidth/clientHeight exclude the scrollbar; setSize(.., true) writes the
    // canvas's CSS box to match, so the projection and the render agree exactly.
    vw = document.documentElement.clientWidth || 1;
    vh = document.documentElement.clientHeight || 1;
    dpr = Math.min(window.devicePixelRatio || 1, 1.25);
    renderer.setPixelRatio(dpr); renderer.setSize(vw, vh, true);
    camera.aspect = vw / vh; camera.updateProjectionMatrix();
    material.uniforms.uPixelRatio.value = dpr;
    computeExtents(); sizePages(); cacheRects();
  }

  function observeNav() {
    var dots = document.querySelectorAll('.cb8-nav__dot');
    for (var k = 0; k < dots.length; k++) {
      dots[k].addEventListener('click', function () {
        var t = document.querySelector('[data-cb8-section="' + this.getAttribute('data-cb8-to') + '"]');
        if (t) { t.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
      });
    }
    _dots = dots;
  }
  var _dots = [], _navIdx = -1;
  // Driven from the rAF off `g`, not from the scroll event: the scroll listener
  // never fires on load or in capture mode, which left the rail stuck on
  // "Arrival" no matter where the camera actually was. Guarded on the rounded
  // index so it writes only on an actual section change, not every frame.
  function syncNav() {
    if (!_dots.length) { return; }
    var act = Math.round(_g);
    if (act === _navIdx) { return; }
    _navIdx = act;
    for (var d = 0; d < _dots.length; d++) {
      _dots[d].classList.toggle('is-active', parseInt(_dots[d].getAttribute('data-cb8-to'), 10) === act);
    }
  }

  function collectPages() {
    var els = document.querySelectorAll('[data-cb8-page]');
    var i, el, sec;
    for (i = 0; i < els.length; i++) {
      el = els[i];
      sec = document.querySelector('[data-cb8-section="' + el.getAttribute('data-cb8-page') + '"]');
      if (!sec) { continue; }
      _pages.push({
        i: i,
        el: el,
        sec: sec,
        floatEl: el.querySelector('.cb8-page__float'),
        skin: el.querySelector('.cb8-page__skin') || el,
        scrim: el.querySelector('.cb8-page__scrim'),
        scroll: el.querySelector('.cb8-page__scroll'),
        body: el.querySelector('.cb8-page__body'),
        state: '', bodyOn: false, cardsIn: false, floatAnim: null,
        wx: 0, wy: 0
      });
    }
  }

  function parseCapture() {
    var q = {}, s = (location.search || '').replace(/^\?/, ''), parts = s.split('&'), i, kv;
    for (i = 0; i < parts.length; i++) { kv = parts[i].split('='); if (kv[0]) { q[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1] || ''); } }
    if (q.cb_capture === '1' || q.cb_capture === 'true') {
      _capture = true;
      var prog = parseFloat(q.cb_progress);
      if (isNaN(prog)) { prog = 0; }
      _captureG = clamp(prog, 0, 1) * Math.max(0, _pages.length - 1);
    }
  }

  /**
   * Inverse of computeG(): the scroll position at which computeG() returns G.
   * Capture mode synthesises scrollY from this instead of forcing _g, so the
   * camera dolly, the assembly envelope and the page curves all derive from one
   * consistent number -- which is the whole point of a deterministic shot.
   */
  function captureScrollFor(G) {
    var n = _secTop.length;
    if (!n) { return 0; }
    var i = Math.round(clamp(G, 0, n - 1));
    var p = clamp01(G - i + 0.5);   // g == i at the centre of section i
    return _secTop[i] + p * _secH[i] - vh * 0.5;
  }

  // ---- init ---------------------------------------------------------------
  function init(opts) {
    if (initialized) { return true; }
    opts = opts || {};
    if (!THREE) { return false; }
    try { if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return false; } } catch (e) {}
    _monoUrl = opts.monogram || ''; _monoStackUrl = opts.monogramStacked || opts.monogram || '';

    var sel = opts.canvas || '#cb8-canvas';
    canvas = (typeof sel === 'string') ? document.querySelector(sel) : sel;
    if (!canvas) { return false; }
    try { renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: false, powerPreference: 'high-performance' }); }
    catch (e) { renderer = null; }
    if (!renderer) { return false; }

    try {
      _v = new THREE.Vector3(); _dir = new THREE.Vector3(); _out = new THREE.Vector3();
      renderer.setClearColor(0x000000, 0);
      vw = document.documentElement.clientWidth || 1;
      vh = document.documentElement.clientHeight || 1;
      dpr = Math.min(window.devicePixelRatio || 1, 1.25);

      scene = new THREE.Scene();
      camera = new THREE.PerspectiveCamera(FOV, vw / vh, 0.1, 160);
      camera.position.set(0, 0, CAMZ);
      computeExtents();

      pos = new Float32Array(COUNT * 3); color = new Float32Array(COUNT * 3);
      size = new Float32Array(COUNT); alpha = new Float32Array(COUNT); phase = new Float32Array(COUNT);
      seedAmbient(); parkStruct(); initBurstBuffers();

      geom = new THREE.BufferGeometry();
      posAttr = new THREE.BufferAttribute(pos, 3); alpAttr = new THREE.BufferAttribute(alpha, 1);
      posAttr.setUsage(THREE.DynamicDrawUsage); alpAttr.setUsage(THREE.DynamicDrawUsage);
      geom.setAttribute('position', posAttr);
      geom.setAttribute('aColor', new THREE.BufferAttribute(color, 3));
      geom.setAttribute('aSize', new THREE.BufferAttribute(size, 1));
      geom.setAttribute('aAlpha', alpAttr);
      geom.setAttribute('aPhase', new THREE.BufferAttribute(phase, 1));
      material = new THREE.ShaderMaterial({
        uniforms: { uTime: { value: 0 }, uPixelRatio: { value: dpr } },
        vertexShader: VERT, fragmentShader: FRAG, transparent: true, blending: THREE.AdditiveBlending, depthTest: false, depthWrite: false
      });
      points = new THREE.Points(geom, material); points.frustumCulled = false; scene.add(points);

      renderer.setPixelRatio(dpr); renderer.setSize(vw, vh, true);

      collectPages();
      _act = document.querySelector('.cb8-scenes') || document.body;
      parseCapture();

      // Only now does the flat Home 2 layout become the floating one.
      document.documentElement.classList.add('cb8-on');
      // Tells the CSS that Motion owns the card reveal + the idle float, so it
      // stands down (drops the transition, drops the keyframe fallback).
      if (M && M.animate) { document.documentElement.classList.add('cb8-motion'); }

      sizePages(); cacheRects();

      try { buildCorridor(); }
      catch (eo) { _corridorReady = false; if (window.console) { console.warn('[cb8] corridor build failed; running nebula+pages only.', eo); } }

      observeNav();
      for (var i = 0; i < _pages.length; i++) { startFloat(_pages[i]); }
      if (window.CBCursor && window.CBCursor.init && !_capture) { try { window.CBCursor.init(); } catch (e2) {} }

      scrollY = _capture ? captureScrollFor(_captureG) : (window.pageYOffset || 0);
      _g = computeG();

      // Spacer heights are authored constants, so a ResizeObserver on the scenes
      // container is enough to keep the cache honest -- no per-frame rect reads.
      if (window.ResizeObserver) {
        var ro = new ResizeObserver(function () { cacheRects(); });
        try { ro.observe(_act); } catch (e4) {}
      }

      if (!_capture) {
        window.addEventListener('scroll', function () { scrollY = window.pageYOffset || 0; }, { passive: true });
        window.addEventListener('mousemove', function (e) { mouseNX = (e.clientX / vw) * 2 - 1; mouseNY = -((e.clientY / vh) * 2 - 1); }, { passive: true });
        var rt; window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(resize, 180); }, { passive: true });
        document.addEventListener('visibilitychange', function () { if (document.hidden) { stop(); } else { start(); } });

        // Keyboard: with focus on <body>, Space/PageDown scrolls the DOCUMENT and
        // the page flies past mid-sentence. Native chaining cannot save that --
        // focus the scroller so the keys target it and chain from ITS end.
        document.addEventListener('keydown', function (e) {
          var k = e.keyCode;
          if (k !== 32 && k !== 33 && k !== 34) { return; }
          if (document.activeElement && document.activeElement !== document.body) { return; }
          for (var n = 0; n < _pages.length; n++) {
            if (_pages[n].state === 'reading' && _pages[n].scroll) {
              try { _pages[n].scroll.focus({ preventScroll: true }); } catch (e5) { _pages[n].scroll.focus(); }
              break;
            }
          }
        }, true);

        // Property Watch is decorative on the preview -- stop the native reload and
        // confirm inline instead of silently navigating to ?email=...
        document.addEventListener('submit', function (e) {
          var f = e.target;
          if (f && f.matches && f.matches('form[data-cb-watch]')) {
            e.preventDefault();
            if (f.parentNode) {
              var note = document.createElement('p');
              note.className = 'cb8-watch__note';
              note.style.opacity = '1';
              note.textContent = "Thanks -- you're on the Property Watch list. We'll be in touch.";
              f.parentNode.replaceChild(note, f);
            }
          }
        }, false);
      }

      if (_capture) {
        // Deterministic single-shot for headless screenshots: time frozen, mouse
        // zeroed, camera snapped (no lerp), scroll ignored.
        _captureFrames = 0;
        var shot = function () {
          frame(now());
          _captureFrames++;
          if (_captureFrames < 3) { raf(shot); }
          else { window.__cbReady = true; document.title = 'CB_READY'; }
        };
        raf(shot);
      } else {
        start();
      }
      initialized = true; return true;
    } catch (err) {
      document.documentElement.classList.remove('cb8-on');
      document.documentElement.classList.remove('cb8-motion');
      stop();
      if (renderer && renderer.dispose) { try { renderer.dispose(); } catch (e3) {} }
      if (window.console) { console.warn('[cb8] init failed; using flat fallback.', err); }
      return false;
    }
  }

  window.CBHome8 = { init: init };

})(window, document);

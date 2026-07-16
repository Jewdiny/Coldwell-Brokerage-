/* =========================================================================
   home8.js -- Coldwell Banker "FLOATING PAGES" homepage (Home 8 preview)

   Forked from cb-corridor/corridor.js (Home 7), which merged Home 2's content
   with Home 5's hallway camera but left the content as flat 2D cards in normal
   document flow. Home 8 adds the layer Home 7's own template docblock promised
   and never built: each of the 8 sections is a PAGE that floats in the corridor
   and zooms as you scroll to it (activetheory.net).

   THE WALK
   --------
   A hallway with rooms opening off alternating sides. You travel the hallway, stop
   at a door, TURN to face the room -- its page hangs deep inside, small -- walk in
   so the page zooms up to reading size, dwell, back out, turn back, carry on to
   the next door. Section 0 is the threshold: you arrive IN the hallway, so it has
   no room (side:0 collapses the turn and walk to no-ops, one code path for all).

   THE CAMERA ROTATES. Earlier builds of this file swore it never would, and used
   that to justify having no CSS3DRenderer. The justification survives, but the
   reason changed:

     A plane that always FACES the camera -- a billboard -- projects to a rectangle
     with uniform scale at ANY camera rotation. No keystone, no shear. So
     `translate + scale` is not an approximation of the correct projection, it IS
     the correct projection, exactly, even at yaw -90.

   Pages are therefore world-anchored (each lives in its room) but camera-facing.
   Give a page a fixed orientation instead and this whole approach ends: you would
   need a real matrix3d / CSS3DRenderer pipeline the moment yaw leaves zero.

   Because the camera physically stops D0 in front of each page, d == D0 and s == 1
   at dwell BY CONSTRUCTION -- no authored depth curve, and no per-section zoom
   inconsistency to correct for. The zoom is simply what walking into a room does.

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

  // D0 is BOTH the reading distance and the plane screenToWorld uses for bursts.
  // A page sits exactly D0 in front of its room's dwell pose, so s == D0/d == 1
  // there, with no authored curve needed -- the geometry guarantees it.
  var D0 = 12;
  var PAGE_W_MAX = 1100, PAGE_H_MAX = 720;

  // ---- the walkthrough ----------------------------------------------------
  // A hallway with rooms opening off alternating sides. You walk the hallway, stop
  // at a door, TURN to face the room (its page is deep inside, small), walk in
  // (the page zooms to reading size), dwell, back out, turn back, carry on.
  //
  // THE CAMERA NOW ROTATES, which the previous build's header declared it never
  // would -- that was the stated reason there is no CSS3DRenderer here. The
  // reasoning survives, but only because the pages are BILLBOARDS: world-anchored
  // in their room, always facing the camera. A camera-facing plane projects to a
  // rectangle with uniform scale at ANY camera rotation -- no keystone, no shear.
  // Anchor a page to a fixed orientation instead and this all ends: you would need
  // a real matrix3d/CSS3DRenderer pipeline the moment the yaw leaves zero.
  var HALL_X = 6, HALL_Y = 5, HALL_Z0 = 6, HALL_Z1 = -64;
  var ROOM_D = 20;      // how far a room reaches past its doorway
  var ROOM_H = 7;       // half-depth of a room along the hallway
  var ROOM_IN = 8;      // how far into the room the camera stands at dwell

  // side: +1 room on +X, -1 room on -X, 0 = no room (stay in the hallway).
  // Section 0 is the threshold -- you arrive IN the hallway, so it has no room and
  // its page hangs straight down the corridor. side:0 makes the turn/walk lerps
  // no-ops, so one code path covers it.
  var ROOM = [
    { z: 0,   side: 0,  p: 0.40, theme: 'threshold' },
    { z: -8,  side: 1,  p: 0.70, theme: 'reception' },
    { z: -16, side: -1, p: 0.95, theme: 'listings' },
    { z: -24, side: 1,  p: 0.85, theme: 'legacy' },
    { z: -32, side: -1, p: 0.85, theme: 'door' },
    { z: -40, side: 1,  p: 0.85, theme: 'communities' },
    { z: -48, side: -1, p: 0.55, theme: 'value' },
    { z: -56, side: 1,  p: 0.70, theme: 'story' }
  ];

  // Schedule in u = g - i. Budget per section sums to 1.0:
  //   0.42..0.58  hallway travel to the next door   (shared between i and i+1)
  //   0.30..0.42  turn at the doorway               (page swings into view, small)
  //   0.16..0.30  walk in / out                     (page zooms 0.6x -> 1x)
  //  -0.16..0.16  IN THE ROOM -- flat, so d == D0 and s == 1 for the whole dwell
  // The flat middle is what lets the inner scroller absorb reading time without
  // the camera creeping: the pose is literally constant across it.
  var U_TURN = 0.42, U_WALK = 0.30, U_DWELL = 0.16;
  var U_FAR = -0.45, U_GONE = 0.45;      // outside this, the page does not exist
  var U_IN = -U_DWELL, U_OUT = U_DWELL;  // reading window == the flat pose window
  // Live body comes in before the turn completes, so the relayout hitch lands
  // while the camera is still swinging rather than as the page settles.
  var U_BODY = -0.40;

  var PAL = [
    [1.00, 1.00, 1.00], [1.00, 1.00, 1.00],
    [0.78, 0.86, 0.96], [0.72, 0.81, 0.92],
    [0.36, 0.56, 0.87], [0.16, 0.45, 1.00]
  ];
  var C_BLUE = [0.13, 0.27, 0.62], C_TIDE = [0.72, 0.81, 0.92], C_BRIGHT = [0.20, 0.48, 1.00];

  // Resting screen offset per page, in CSS px AT READING DISTANCE. Baked into the
  // page's world anchor on resize (see sizePages), so it is a real position in the
  // room rather than a screen nudge. Kept small -- these are pages to be read.
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

  // Camera basis from a yaw. Three's camera looks down -Z at yaw 0.
  function fwdX(y) { return -Math.sin(y); }
  function fwdZ(y) { return -Math.cos(y); }
  function rgtX(y) { return Math.cos(y); }
  function rgtZ(y) { return -Math.sin(y); }
  function yawFor(side) { return side > 0 ? -Math.PI / 2 : (side < 0 ? Math.PI / 2 : 0); }

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
  var _v = null, _dir = null, _out = null, _pv = null, _mwi = null, _fwd = null;
  var _monoUrl = '', _monoStackUrl = '';

  // pages
  var _pages = [];
  var _secTop = [], _secH = [];
  var _actTop = 0, _actH = 1;

  // ONE smoothed clock. _gRaw tracks scroll exactly; _g is the lagged value that
  // EVERYTHING downstream reads -- camera waypoints, page depth, opacity, nav.
  //
  // It used to be two clocks: the camera took its target from the raw g and then
  // lerped its POSITION toward it, while the pages took their depth from the raw g
  // directly. So page scale responded instantly to scroll while the corridor
  // dollied in ~0.33s behind it -- the zoom led the hallway, and on a fast scroll
  // you could see the page racing the world it is supposed to be inside.
  // Lagging the clock instead of the position keeps them exactly in step.
  var _gRaw = 0, _g = 0, _reading = 0;
  // Smoothed pointer. Raw mouse deltas went straight into the camera, so a jittery
  // trackpad became jittery parallax on every page at once.
  var _mx = 0, _my = 0;

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
      // Ambient dust has to fill the whole walkthrough now, not just the aisle:
      // the rooms reach out to x = +/-26, and once you turn into one you are
      // looking along X, where the old +/-halfW seeding left a visibly empty void.
      pos[i * 3] = (Math.random() < 0.5) ? rand(-halfW * 1.3, halfW * 1.3) : rand(-26, 26);
      pos[i * 3 + 1] = rand(-halfH * 1.7, halfH * 1.7);
      pos[i * 3 + 2] = (Math.random() < 0.6) ? rand(HALL_Z1, HALL_Z0) : rand(-14, 6);
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
  // Rooms open sideways off the hallway, so their walls and fixtures face along X.
  // Everything the corridor build had was drawn in the X-Y plane at a fixed Z --
  // useless once the camera is looking down the X axis. These are the Y-Z twins.
  function quadYZ(x, cy, cz, h, d, c, g) {
    var y0 = cy - h / 2, y1 = cy + h / 2, z0 = cz - d / 2, z1 = cz + d / 2;
    E(x, y0, z0, x, y0, z1, c, g); E(x, y0, z1, x, y1, z1, c, g); E(x, y1, z1, x, y1, z0, c, g); E(x, y1, z0, x, y0, z0, c, g);
  }
  function arcYZ(x, cy, cz, r, a0, a1, segs, c, g) {
    var prev = null, i, a, y, z;
    for (i = 0; i <= segs; i++) {
      a = a0 + (a1 - a0) * i / segs;
      z = cz + Math.cos(a) * r; y = cy + Math.sin(a) * r;
      if (prev) { E(x, prev[0], prev[1], x, y, z, c, g); }
      prev = [y, z];
    }
  }

  /** One room, opening off the hallway at x = side*HALL_X, centred on z = R.z. */
  function defineRoom(R) {
    var s = R.side, z = R.z;
    var xIn = s * HALL_X;                 // doorway plane
    var xFar = s * (HALL_X + ROOM_D);     // far wall, the one you face
    var z0 = z - ROOM_H, z1 = z + ROOM_H;

    // Shell: far wall, floor, ceiling, corner posts. Wireframe only -- the STRUCT
    // particles condense onto these edges, so the room literally forms from dust.
    quadYZ(xFar, 0, z, 2 * HALL_Y, 2 * ROOM_H, C_BLUE, 0);
    E(xIn, -HALL_Y, z0, xFar, -HALL_Y, z0, C_TIDE, 0); E(xIn, -HALL_Y, z1, xFar, -HALL_Y, z1, C_TIDE, 0);
    E(xIn, HALL_Y, z0, xFar, HALL_Y, z0, C_TIDE, 0);   E(xIn, HALL_Y, z1, xFar, HALL_Y, z1, C_TIDE, 0);
    E(xFar, -HALL_Y, z0, xFar, HALL_Y, z0, C_TIDE, 0); E(xFar, -HALL_Y, z1, xFar, HALL_Y, z1, C_TIDE, 0);
    // Floor light-line running into the room -- it reads as the path you walk.
    E(xIn, -HALL_Y + 0.02, z, xFar, -HALL_Y + 0.02, z, C_BRIGHT, 1);

    // Doorway: posts + lintel in the hallway wall. This is what you turn to face.
    E(xIn, -HALL_Y, z - 3, xIn, HALL_Y - 1, z - 3, C_TIDE, 1);
    E(xIn, -HALL_Y, z + 3, xIn, HALL_Y - 1, z + 3, C_TIDE, 1);
    E(xIn, HALL_Y - 1, z - 3, xIn, HALL_Y - 1, z + 3, C_TIDE, 1);

    var i, fy, fz;
    switch (R.theme) {
      case 'reception':
        boxE(s * 16, -3, z, 6, 1.4, 2.4, C_TIDE, 1);            // desk
        arcYZ(xFar - 0.2, 1.4, z, 2.2, 0, TAU, 22, C_BRIGHT, 0); // ring on the wall
        break;
      case 'listings':                                           // 3x2 frame wall
        fz = [-4.4, 0, 4.4]; fy = [2.4, -1.2];
        for (i = 0; i < 3; i++) {
          quadYZ(xFar - 0.2, fy[0], z + fz[i], 2.6, 3.4, (i === 1) ? C_BRIGHT : C_TIDE, (i === 1) ? 1 : 0);
          quadYZ(xFar - 0.2, fy[1], z + fz[i], 2.6, 3.4, C_TIDE, 0);
        }
        E(xIn + 1, -1.9, z, xFar - 0.4, -1.9, z, C_BRIGHT, 1);
        break;
      case 'legacy':                                             // arch you face
        arcYZ(s * 18, -1, z, 5, 0, Math.PI, 34, C_TIDE, 1);
        for (i = -1; i <= 1; i += 2) { boxE(s * 18, 0, z + i * 5, 0.5, 9, 0.5, C_TIDE, 0); }
        quadYZ(xFar - 0.2, 1, z, 4, 9, C_BLUE, 0);
        break;
      case 'door':                                               // a literal door
        quadYZ(xFar - 0.2, -0.6, z, 7.6, 3.6, C_BRIGHT, 1);
        quadYZ(xFar - 0.2, -0.6, z, 8.6, 4.8, C_TIDE, 0);
        E(xFar - 0.3, -0.6, z + 1.3, xFar - 0.3, -0.6, z + 1.5, C_BRIGHT, 1);  // handle
        break;
      case 'communities':                                        // planning table
        quadXZ(s * 16, -3.4, z, 9, 8, C_TIDE, 1);
        for (i = 1; i < 4; i++) { E(s * (11 + i * 2.5), -3.4, z - 4, s * (11 + i * 2.5), -3.4, z + 4, C_TIDE, 0); }
        for (i = 1; i < 4; i++) { E(s * 11.5, -3.4, z - 4 + i * 2, s * 20.5, -3.4, z - 4 + i * 2, C_TIDE, 0); }
        var px = [13, 15.5, 18, 19.5], pz = [-2.2, 1.4, -0.6, 2.6];
        for (i = 0; i < px.length; i++) { E(s * px[i], -3.4, z + pz[i], s * px[i], -1.4, z + pz[i], C_BRIGHT, (i % 2) ? 0 : 1); }
        break;
      case 'value':                                              // valuation desk
        boxE(s * 17, -2.4, z, 5, 1, 2.6, C_TIDE, 1);
        quadYZ(s * 17.4, -0.7, z, 2.2, 3.2, C_BRIGHT, 1);
        quadYZ(xFar - 0.2, 0.4, z, 5.2, 8, C_BLUE, 0);
        break;
      case 'story':                                              // story wall
        fz = [-4.6, 0, 4.6];
        for (i = 0; i < 3; i++) { quadYZ(xFar - 0.2, 1.2, z + fz[i], 3, 3.4, C_TIDE, 0); }
        E(xIn + 1, -2.2, z, xFar - 0.4, -2.2, z, C_BRIGHT, 1);
        break;
    }
  }

  function defineWorld() {
    EDG.length = 0;
    var i, z;

    // ---- the hallway --------------------------------------------------------
    // Floor light-spine + four long rails. This is the thing you travel down and
    // the thing the rooms hang off, so it runs the full length.
    E(0, -HALL_Y, HALL_Z0, 0, -HALL_Y, HALL_Z1, C_BRIGHT, 1);
    E(-HALL_X, -HALL_Y, HALL_Z0, -HALL_X, -HALL_Y, HALL_Z1, C_TIDE, 0);
    E(HALL_X, -HALL_Y, HALL_Z0, HALL_X, -HALL_Y, HALL_Z1, C_TIDE, 0);
    E(-HALL_X, HALL_Y, HALL_Z0, -HALL_X, HALL_Y, HALL_Z1, C_TIDE, 0);
    E(HALL_X, HALL_Y, HALL_Z0, HALL_X, HALL_Y, HALL_Z1, C_TIDE, 0);
    // Ribs every 8 units so travelling down the hallway reads as motion.
    for (z = HALL_Z0; z >= HALL_Z1; z -= 8) {
      E(-HALL_X, -HALL_Y, z, -HALL_X, HALL_Y, z, C_TIDE, 0);
      E(HALL_X, -HALL_Y, z, HALL_X, HALL_Y, z, C_TIDE, 0);
      E(-HALL_X, HALL_Y, z, HALL_X, HALL_Y, z, C_BLUE, 0);
    }

    // ---- the threshold ------------------------------------------------------
    boxE(0, 0, ROOM[0].z + 3, 2 * HALL_X + 1, 2 * HALL_Y, 0.4, C_TIDE, 1);
    quadE(-1.8, 0, ROOM[0].z + 2.9, 3.0, 9, C_TIDE, 0);
    quadE(1.8, 0, ROOM[0].z + 2.9, 3.0, 9, C_TIDE, 0);

    // ---- the rooms ----------------------------------------------------------
    for (i = 0; i < ROOM.length; i++) { if (ROOM[i].side !== 0) { defineRoom(ROOM[i]); } }
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
        sRx[idx] = rand(-26, 26);
        sRy[idx] = rand(-halfH * 1.7, halfH * 1.7);
        sRz[idx] = rand(HALL_Z1, HALL_Z0);
        // Condense front-to-back along the hallway, so the world builds itself
        // ahead of you as you walk rather than all at once.
        sStag[idx] = clamp01((HALL_Z0 - hz) / (HALL_Z0 - HALL_Z1));
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

  function bakeMonogram(url, x, y, z, h, yaw) {
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
        mesh.position.set(x, y, z);
        mesh.rotation.y = yaw || 0;   // a plane on a room's far wall faces along X
        mesh.frustumCulled = false;
        corridorGroup.add(mesh);
        // Proximity is measured from the mark's own position now, not from a z
        // difference -- rooms are off to the side, so z alone says nothing.
        signage.push({ mesh: mesh, mat: mat, pos: new THREE.Vector3(x, y, z) });
      } catch (e) {}
    };
    img.onerror = function () {};
    img.src = url;
  }

  function buildCorridor() {
    corridorGroup = new THREE.Group();
    defineWorld();
    buildCorridorFromEdges();
    // Signage now hangs on room walls rather than across the hallway. It has to
    // face the room's camera, so it is rotated to match the room's yaw.
    bakeMonogram(_monoUrl, ROOM[1].side * (HALL_X + ROOM_D - 0.4), 1.4, ROOM[1].z, 2.6, yawFor(ROOM[1].side));
    bakeMonogram(_monoStackUrl, ROOM[7].side * (HALL_X + ROOM_D - 0.4), -0.6, ROOM[7].z, 4.2, yawFor(ROOM[7].side));
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
      var on = clamp01(1 - (camera.position.distanceTo(s.pos) - 10) / 14);
      // Recede while a page is being read. The reception mark sits at z=-10.85 and
      // the camera parks at z=-9, so it blooms to full size across the middle of
      // the screen -- straight behind the text. Home 7 could let it blaze because
      // its cards were opaque boxes over it; Home 8's pages are transparent, so it
      // reads through the copy. Same instinct as damping the parallax: the world
      // gets out of the way while you read.
      s.mat.opacity = on * corridorAssembly * (1 - _reading * 0.72);
    }
  }

  // ---- screen px -> world (plane D0 in front of the dollying camera) -------
  // Exact inverse of projectPages(): the point on the plane D0 in front of the
  // camera that a screen pixel falls on.
  //
  // The old version divided by _dir.z, which silently assumed the camera looks
  // down -Z. That held for the corridor and is wrong the moment the camera turns
  // into a room: at yaw -90 the view axis is +X, _dir.z goes through zero, and
  // every card's burst would fire off toward infinity. Projecting onto the camera's
  // own forward axis is the general form and reduces to the old one at yaw 0.
  function screenToWorld(px, py) {
    camera.updateMatrixWorld();
    camera.getWorldDirection(_fwd);
    _v.set((px / vw) * 2 - 1, -((py / vh) * 2 - 1), 0.5).unproject(camera);
    _dir.copy(_v).sub(camera.position).normalize();
    var denom = _dir.dot(_fwd);
    if (Math.abs(denom) < 1e-4) { return _out.copy(camera.position); }
    _out.copy(camera.position).add(_dir.multiplyScalar(D0 / denom));
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
  /**
   * The walk, as a pure function of the master clock.
   *
   *   |u| < 0.16          in the room, pose CONSTANT (this is what makes s == 1
   *                       hold across the whole dwell, so an inner scroller can
   *                       absorb reading time without the camera creeping)
   *   0.16 .. 0.30        walking in / backing out through the doorway
   *   0.30 .. 0.42        turning at the doorway, still in the hallway
   *   0.42 .. 0.58        travelling the hallway to the next door
   *
   * Continuity is by construction: at u = +0.42 you are at room i's door facing
   * down the hallway, and that is exactly where room i+1's u = -0.42 starts.
   * Rooms with side:0 (the threshold) collapse the turn and walk to no-ops.
   */
  function poseAt(g, out) {
    var n = ROOM.length;
    g = clamp(g, 0, n - 1);
    var i = Math.round(g), u = g - i, R = ROOM[i];

    // Between two doors: pure hallway.
    if (u <= -U_TURN || u >= U_TURN) {
      var a, b, t;
      if (u >= U_TURN) { a = i; b = Math.min(i + 1, n - 1); t = (u - U_TURN) / (1 - 2 * U_TURN); }
      else { a = Math.max(i - 1, 0); b = i; t = (u + (1 - U_TURN)) / (1 - 2 * U_TURN); }
      var za = ROOM[a].z, zb = ROOM[b].z;
      out.x = 0; out.y = 0; out.z = za + (zb - za) * smooth(clamp01(t)); out.yaw = 0;
      out.p = ROOM[a].p + (ROOM[b].p - ROOM[a].p) * clamp01(t);
      return out;
    }

    out.y = 0; out.p = R.p;
    if (R.side === 0) { out.x = 0; out.z = R.z; out.yaw = 0; return out; }

    var yaw = yawFor(R.side), au = Math.abs(u);
    if (au >= U_WALK) {                       // turning at the doorway
      out.x = 0; out.z = R.z;
      out.yaw = yaw * smooth(clamp01(1 - (au - U_WALK) / (U_TURN - U_WALK)));
    } else if (au >= U_DWELL) {               // walking in / backing out
      out.x = R.side * ROOM_IN * smooth(clamp01(1 - (au - U_DWELL) / (U_WALK - U_DWELL)));
      out.z = R.z; out.yaw = yaw;
    } else {                                  // in the room -- FLAT
      out.x = R.side * ROOM_IN; out.z = R.z; out.yaw = yaw;
    }
    return out;
  }

  /**
   * Opacity. The two windows are COMPLEMENTARY, and that is the whole point:
   * consecutive pages sit exactly 1.0 apart in g, so page i's fade-out
   * ([0.25, 0.75]) lines up with page i+1's fade-in (the same window shifted by
   * -1, i.e. [-0.75, -0.25]). They cross at g = i+0.5, one going up as the other
   * goes down.
   *
   * Get this wrong and you get mush. It first faded out over [0.5, 1.15] while the
   * next page finished fading IN at -0.8 -- so from g=i+0.2 to g=i+0.5 BOTH pages
   * were at full opacity, and you could read the previous page's cards straight
   * through the current one. The wide 0.35x..3x zoom hid it (the old page was
   * obviously huge and leaving); at 0.75x..1.4x the two pages are near enough the
   * same size that they simply collide.
   *
   * Both windows are also clear of the dwell ([U_IN, U_OUT] = [-0.35, 0.25]), so a
   * page being read is always at a solid opacity: 1.
   */
  function aCurve(u) {
    return band(u, -0.32, -0.18) * (1 - band(u, 0.18, 0.32));
  }
  function readAmount(u) {
    return band(u, U_IN - 0.05, U_IN) * (1 - band(u, U_OUT, U_OUT + 0.05));
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

  var _pose = { x: 0, y: 0, z: 0, yaw: 0, p: 1 };
  var _yawT = 0;

  function computeCamera() {
    if (!_corridorReady || !_secTop.length) {
      oTX = mouseNX * 2.6; oTY = mouseNY * 1.9; oTZ = CAMZ; _yawT = 0; oP = 1; return;
    }
    poseAt(_g, _pose);
    var center = scrollY + vh * 0.5;
    var ap = clamp01((center - _actTop) / _actH);
    // The assembly envelope condenses the WORLD out of the dust and dissolves it
    // again at the very end. It no longer touches the camera.
    //
    // It used to blend the camera itself from a free-floating nebula pose (0,0,22)
    // into the walk, which broke both ends of the page once pages became
    // world-anchored rather than camera-leashed:
    //   - the intro ramp was still climbing through section 0's dwell, so the hero
    //     page sat at s=0.76 and never reached 1:1;
    //   - the tail dissolve starts at 90%, but the last room's dwell is at 94% --
    //     so it hauled the camera ~26 units back up the hallway and 34 degrees off
    //     axis, and the final page rendered small, off-centre, with the WRONG
    //     room's furniture behind it.
    // The camera now simply walks; the world forms around it. That is also the
    // better beat -- you stand at the threshold and the hallway assembles.
    var asm = band(ap, 0.0, 0.10) * (1 - band(ap, 0.96, 1.0));
    oTZ = _pose.z; oTX = _pose.x; oTY = _pose.y;
    _yawT = _pose.yaw;
    // Damp parallax + idle drift at the CAMERA while a page is being read. Damping
    // it at the page would make the page a lie (it would stop obeying the
    // projection); damping the camera makes the whole world hold its breath, which
    // is the better beat anyway.
    oP = _pose.p * (1 - _reading);
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

  /**
   * Fade each image up as it decodes, rather than letting it pop in at full
   * strength mid-approach. Images inside .cb8-page__body only start fetching when
   * content-visibility releases them, so on a page like Communities six webp files
   * land at once, several frames apart -- unmanaged, that is six separate pops
   * over the top of a page that is already moving.
   *
   * `error` marks it loaded too: a 404 should leave a gap, not a permanently
   * invisible element that still occupies its grid cell.
   */
  function onImgIn() { this.classList.add('is-loaded'); }
  function watchImages(root) {
    var imgs = root.querySelectorAll('img'), i, im;
    for (i = 0; i < imgs.length; i++) {
      im = imgs[i];
      if (im.__cb8w) { continue; }
      im.__cb8w = true;
      // Capture wants the resting state: a CSS transition does not advance under
      // --virtual-time-budget, so a fading image would photograph at opacity 0.
      if (_capture || (im.complete && im.naturalWidth > 0)) { im.classList.add('is-loaded'); continue; }
      im.addEventListener('load', onImgIn);
      im.addEventListener('error', onImgIn);
    }
  }

  function revealBody(p) {
    if (p.bodyOn) { return; }
    p.bodyOn = true;
    p.el.classList.add('is-body-on');
    // Body images do not exist to the loader until content-visibility releases
    // them, so they must be watched here, not at init.
    watchImages(p.el);
    // Card reveal and counters hang off VISIBILITY, not off the 'reading' state.
    // Tying them to 'reading' looks identical while scrolling forwards and is
    // silently broken on arrival: land at g=3.5 (reload mid-page, scroll
    // restoration, a deep link, capture mode) and page 3 is already 'exiting', so
    // 'reading' never fires and every card stays at opacity:0 under a heading
    // that renders fine. Revealing with the body means the page is correct at any
    // entry point, and the cards form as the page zooms in -- which is the beat
    // we wanted anyway.
    formCards(p);
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

  // ---- form from dust / crumble (Home 7's card behaviour, page-driven) -----
  // Home 7 drove these off an IntersectionObserver, which cannot work here: every
  // page is position:fixed and therefore always intersecting, so IO would form all
  // 8 pages' cards at once, on load. The page state machine is the arbiter
  // instead -- same look, one clock, no event-ordering races.
  //
  // The transform lives on .cb8-card and the idle float on .cb8-card__inner, so
  // the one-shot reveal and the infinite float never fight over `transform`.
  function staggerFor(i) { return clamp(i * 90, 0, 420); }

  function formCards(p) {
    if (p.cardsIn) { return; }
    p.cardsIn = true;
    var cards = p.el.querySelectorAll('[data-cb8-card]');
    if (!cards.length) { return; }
    var i, delay, el, isFrame;

    // Capture mode wants the RESTING state, not an animation caught mid-flight:
    // transitions do not advance under --virtual-time-budget, so a headless shot
    // would freeze every card at opacity:0 and photograph an empty page.
    if (_capture) {
      for (i = 0; i < cards.length; i++) { cards[i].classList.add('is-formed'); cards[i].style.transitionDelay = ''; }
      return;
    }

    for (i = 0; i < cards.length; i++) {
      el = cards[i];
      delay = staggerFor(i);
      isFrame = el.hasAttribute('data-cb8-frame');
      el.classList.remove('is-crumbling');
      el.classList.add('is-forming');
      el.style.transitionDelay = (delay / 1000) + 's';
      (function (node, d, frame) {
        raf(function () { node.classList.add('is-formed'); });
        setTimeout(function () {
          if (!document.hidden) { spawnBurst(node.getBoundingClientRect(), 'in', frame); }
        }, d);
        setTimeout(function () { node.classList.remove('is-forming'); }, d + 950);
      })(el, delay, isFrame);
    }
    startCardFloats(p);
  }

  function crumbleCards(p) {
    if (!p.cardsIn) { return; }
    p.cardsIn = false;
    var cards = p.el.querySelectorAll('[data-cb8-card]');
    var i, delay, el, isFrame;
    for (i = 0; i < cards.length; i++) {
      el = cards[i];
      delay = staggerFor(i);
      isFrame = el.hasAttribute('data-cb8-frame');
      el.classList.remove('is-formed');
      el.classList.add('is-crumbling');
      el.style.transitionDelay = (delay / 1000) + 's';
      if (!_capture) {
        (function (node, d, frame) {
          setTimeout(function () {
            if (!document.hidden) { spawnBurst(node.getBoundingClientRect(), 'out', frame); }
          }, d);
        })(el, delay, isFrame);
      }
    }
  }

  function resetCards(p) {
    p.cardsIn = false;
    var cards = p.el.querySelectorAll('[data-cb8-card]');
    for (var i = 0; i < cards.length; i++) {
      cards[i].classList.remove('is-formed');
      cards[i].classList.remove('is-forming');
      cards[i].classList.remove('is-crumbling');
      cards[i].style.transitionDelay = '';
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

  /**
   * Per-card idle float, Home 7's signature. It lives on .cb8-card__inner while
   * form/crumble owns .cb8-card's transform -- the same two-element split Home 7
   * used, for the same reason.
   *
   * The bob composes multiplicatively with the page's projected scale, so a 12px
   * float at s=0.75 lands as 9px on screen: a page further down the corridor
   * wobbles less, for free.
   *
   * CSS keyframes (cb-home8.css) are the no-Motion fallback and look near enough
   * identical; html.cb8-motion switches them off so the two never double up.
   */
  function startCardFloats(p) {
    if (p.floatsOn || _capture) { return; }
    p.floatsOn = true;
    if (!M || !M.animate) { return; }   // CSS keyframes carry it
    var inners = p.el.querySelectorAll('[data-cb8-card] > .cb8-card__inner');
    for (var i = 0; i < inners.length; i++) {
      // Frames hold live content (MLS grid, rotator) -- Home 7 exempts them from
      // the float so a scrollable table is not drifting under the cursor.
      if (inners[i].parentNode.hasAttribute('data-cb8-frame')) { continue; }
      (function (node, k) {
        try {
          var a = M.animate(node,
            { transform: [
                'translateY(' + (-FL_Y[k] * 0.5) + 'px) rotate(' + (-FL_ROT[k]) + 'deg)',
                'translateY(' + FL_Y[k] + 'px) rotate(' + FL_ROT[k] + 'deg)'
              ] },
            { duration: FL_DUR[k], repeat: Infinity, direction: 'alternate', easing: 'ease-in-out' }
          );
          // Negative-delay equivalent: start each card mid-cycle so a grid of them
          // never pulses in unison.
          if (a && typeof a.currentTime === 'number') { a.currentTime = Math.abs(FL_DEL[k]) * 1000; }
        } catch (e) {}
      })(inners[i], i % FL_DUR.length);
    }
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
      // Cards crumble back into the dust as the page leaves, then re-form on the
      // way back in. Only from 'reading' -> 'exiting': crumbling a page that was
      // still approaching would fire on a cold arrival and read as a glitch.
      if (next === 'exiting' && prev === 'reading') { crumbleCards(p); }
      if (next === 'reading' && prev === 'exiting') { p.cardsIn = false; formCards(p); }
    }
  }

  // ---- projection ---------------------------------------------------------
  // upp = world units per CSS px at depth d. CSS px throughout: setPixelRatio only
  // changes the drawing buffer, so multiplying by devicePixelRatio here would be a
  // bug. vw/vh come from documentElement.clientWidth/Height, which EXCLUDE the
  // scrollbar -- window.innerWidth would put every page ~15px off-axis from the
  // corridor on Windows, since this page always has a scrollbar.
  function projectPages() {
    // Camera space, not world space. The old code differenced world coordinates
    // directly, which only worked because the camera could not rotate. Going
    // through matrixWorldInverse is the same maths for an unrotated camera and the
    // correct maths for a yawing one. matrixWorldInverse is normally refreshed by
    // renderer.render(), i.e. AFTER this runs -- so refresh it here or the pages
    // project from last frame's camera and lag the world by a frame.
    camera.updateMatrixWorld();
    _mwi.copy(camera.matrixWorld).invert();

    var i, p, u, d, s, upp, sx, sy, a;
    for (i = 0; i < _pages.length; i++) {
      p = _pages[i];
      u = _g - i;
      if (u <= U_FAR || u > U_GONE) { setState(p, 'far'); continue; }

      a = aCurve(u);
      _pv.copy(p.world).applyMatrix4(_mwi);
      d = -_pv.z;                                  // depth along the view axis

      // A page lives in its room, so while you are out in the hallway it is off to
      // one side -- d passes through zero and s = D0/d explodes. aCurve is already
      // zero there, but a NaN/Infinity transform still poisons the element, so
      // this guard is load-bearing rather than defensive.
      if (d < 2 || a < 0.004) { setState(p, 'far'); continue; }

      s = D0 / d;                                  // == 1 exactly at d == D0
      upp = (2 * d * TAN_HALF) / vh;
      sx = _pv.x / upp;                            // CSS px from viewport centre
      sy = -_pv.y / upp;

      setState(p, stateFor(u));

      // Every write below is guarded against its last value. transform genuinely
      // changes each frame; zIndex, opacity and will-change almost never do, and
      // re-assigning them 60x/sec was pure style-recalc churn on 8 pages carrying
      // live DOM. Fixed to 2dp / 4dp: finer than a pixel, coarse enough that a
      // resting page stops writing at all. NOT rounded to whole pixels -- that
      // jitters visibly as a page drifts.
      var tf = 'translate3d(' + sx.toFixed(2) + 'px,' + sy.toFixed(2) + 'px,0) scale(' + s.toFixed(4) + ')';
      if (p._tf !== tf) { p.el.style.transform = tf; p._tf = tf; }

      var z = 1000 - Math.round(d * 10);
      if (p._z !== z) { p.el.style.zIndex = String(z); p._z = z; }

      var ao = a.toFixed(3);
      if (p._op !== ao) { p.skin.style.opacity = ao; p._op = ao; }

      // Chrome stops updating raster scale for will-change:transform layers, so a
      // page that entered at 0.75x with it pinned would rasterise its text at
      // 0.75x and STAY soft at s==1. Drop the hint while dwelling: nothing is
      // moving, so the one-time repaint is invisible and re-rasterises at 1:1.
      var wc = (p.state === 'reading') ? 'auto' : 'transform';
      if (p._wc !== wc) { p.el.style.willChange = wc; p._wc = wc; }

      // Depth of field is opacity ALONE. The exit used to add a blur, quantised to
      // 0.5px so the radius would not re-rasterise a live-DOM subtree every frame
      // -- but once the zoom narrowed to 1.4x that blur peaks at ~2px, so the
      // quantisation that made it affordable also made it visibly step through
      // four values on the way out. Un-quantised it is a filter re-raster per
      // frame on the biggest subtree on the page. Neither is worth 2px of blur:
      // opacity already sells the depth, and it composites for free.

      // LOD: flip the live body in partway through the approach, well before
      // anybody reads, so the relayout hitch lands during fast camera motion
      // rather than as the page settles. content-visibility:auto cannot work here
      // -- a fixed page always intersects the viewport, so it would never engage.
      if (u > U_BODY) { revealBody(p); }
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
    _gRaw = computeG();

    // Lag the CLOCK, not the camera's position -- see _g's declaration. Capture
    // snaps it: a deterministic shot must not depend on how many frames it ran.
    if (_capture) {
      _g = _gRaw; _mx = 0; _my = 0;
    } else {
      _g += (_gRaw - _g) * lerpK(0.05, dt);
      var km = lerpK(0.14, dt);
      _mx += (mouseNX - _mx) * km;
      _my += (mouseNY - _my) * km;
    }
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
    if (_capture) { driftX = 0; driftY = 0; }
    // No second lerp. The weight lives in _g now, and lerping here as well would
    // put the camera a further ~0.33s behind the pages that are projected FROM it
    // -- which reads as blur, not as lag.
    oX = oTX; oY = oTY; oZ = oTZ;
    camera.rotation.y = _yawT;
    // Parallax rides the camera's OWN right axis, not world X. Once the camera can
    // turn, a world-X nudge would slide you sideways in the hallway but forwards
    // and backwards inside a room -- mouse-driven dolly, which looks like a bug.
    var px = (_mx * 2.6 + driftX) * oP, py = (_my * 1.9 + driftY) * oP;
    camera.position.set(oX + rgtX(_yawT) * px, oY + py, oZ + rgtZ(_yawT) * px);

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

  /**
   * Anchor each page in its room, D0 in front of that room's dwell pose.
   *
   * This is why no dCurve is needed any more. The old build authored the depth
   * because the camera flew straight past a page pinned to a waypoint, and the
   * corridor's waypoint gaps (5,6,6,3,6,8,8) gave every section a different zoom.
   * Now the camera physically stops in front of the page, so d == D0 and s == 1 at
   * dwell by construction, and the zoom is just what walking into a room does.
   *
   * Recomputed on resize because the resting offset is authored in CSS px at
   * reading distance, and px -> world depends on viewport height.
   */
  function sizePages() {
    var w = Math.min(PAGE_W_MAX, Math.round(vw * 0.84));
    var h = Math.min(PAGE_H_MAX, Math.round(vh * 0.80));
    for (var i = 0; i < _pages.length; i++) {
      var p = _pages[i], R = ROOM[i];
      p.el.style.width = w + 'px';
      p.el.style.height = h + 'px';

      var yaw = yawFor(R.side);
      var cxr = R.side * ROOM_IN, czr = R.z;          // the dwell pose
      var ox = PAGE_OFF[i].x * upp12, oy = PAGE_OFF[i].y * upp12;
      p.world.set(
        cxr + fwdX(yaw) * D0 + rgtX(yaw) * ox,
        -oy,                                          // screen y is down, world y is up
        czr + fwdZ(yaw) * D0 + rgtZ(yaw) * ox
      );
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
        skin: el.querySelector('.cb8-page__skin') || el,
        scroll: el.querySelector('.cb8-page__scroll'),
        body: el.querySelector('.cb8-page__body'),
        state: '', bodyOn: false, cardsIn: false, floatsOn: false,
        world: new THREE.Vector3()
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
      _pv = new THREE.Vector3(); _fwd = new THREE.Vector3(); _mwi = new THREE.Matrix4();
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
      // Kills the image fade transitions -- see watchImages().
      if (_capture) { document.documentElement.classList.add('cb8-capture'); }
      // Tells the CSS that Motion owns the card reveal + the idle float, so it
      // stands down (drops the transition, drops the keyframe fallback).
      if (M && M.animate) { document.documentElement.classList.add('cb8-motion'); }

      sizePages(); cacheRects();

      try { buildCorridor(); }
      catch (eo) { _corridorReady = false; if (window.console) { console.warn('[cb8] corridor build failed; running nebula+pages only.', eo); } }

      observeNav();
      // Plates live outside .cb8-page__body, so they start fetching immediately --
      // watch them now. Body images are picked up in revealBody().
      for (var pi = 0; pi < _pages.length; pi++) { watchImages(_pages[pi].el); }
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

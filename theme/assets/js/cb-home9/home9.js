/* =========================================================================
   home9.js -- Coldwell Banker "THE HOUSE" homepage (Home 9 preview)

   Home 8, walked through an actual home instead of a blueprint.

   Home 8 (cb-home8/home8.js) is UNCHANGED and still ships -- this is a sibling,
   not a replacement. Everything about the WALK is identical and deliberately so:
   the same 8 rooms off the same hallway, the same overlapping-arc camera, the same
   world-anchored billboard pages, the same flat dwells. If you fix a bug in one
   file's poseAt/projectPages, fix it in the other; harness/path-check.js runs
   against either.

   WHAT IS ACTUALLY DIFFERENT
   --------------------------
   Home 8's world is a wireframe: additive LineSegments, and a pool of particles
   that condenses out of dust onto those edges. It reads as a schematic of a house.
   Home 9 is the house: solid walls, oak floors, plaster, wainscot, crown moulding,
   rugs, furniture, and lamps that actually light the room.

   That means the rendering model changes completely, and three of Home 8's central
   ideas simply do not survive the translation:

     - NO LINE LAYER. There are no edges to draw; there are surfaces.
     - NO STRUCT PARTICLES. "The room condenses out of dust" is a blueprint
       metaphor. A home does not assemble itself in front of you -- it is already
       there, and you are the one arriving. The particle pool is now just motes in
       the lamplight, and the burst pool that ties cards to the world.
     - LIT, NOT ADDITIVE. Home 8 is additive blending over a navy field, which is
       why it glows. Lambert surfaces + warm lights + fog is what makes a room feel
       like a room; additive would make it feel like a hologram of one.

   Home 2's eight scene plates hang as framed ART on each room's far wall, rather
   than sitting behind the page as atmosphere. Photographs on the walls of a house
   is what those images are FOR; the same asset, used as the thing it depicts.

   THE CAMERA ROTATES. Earlier builds of this engine swore it never would, and used
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
     .cb9-page          transform <- PROJECTION (rAF, every frame). Nothing else.
       .cb9-page__float transform <- MOTION (idle float spring)
         .cb9-page__skin  opacity/filter <- projection alpha + Motion
           .cb9-page__scroll  <- THE BROWSER. Nothing animates this. Ever.
             .cb9-page__body  <- Motion may animate CHILDREN (stagger, counters)

   Progressive enhancement: init() adds html.cb9-on only after WebGL starts. The
   CSS polarity is INVERTED from Home 7 -- flat Home 2 layout is the default and
   every floating rule is nested under .cb9-on. Home 7's `transform:none` reduced-
   motion override would leave 8 fixed panels stacked on top of each other; the
   float layout is structurally incompatible with the flat one, so it must be a
   layout switch, not an override.

   Exposes window.CBHome9.init(opts). Requires window.THREE. window.Motion optional.
   ========================================================================= */
(function (window, document) {
  'use strict';

  if (window.CBHome9) { return; }

  var THREE = window.THREE;
  var M = window.Motion || null;
  var raf = window.requestAnimationFrame || function (cb) { return setTimeout(function () { cb(now()); }, 16); };
  var caf = window.cancelAnimationFrame || window.clearTimeout;
  function now() { return (window.performance && performance.now) ? performance.now() : Date.now(); }

  // ---- config -------------------------------------------------------------
  var AMB = 150;             // a FEW dust motes in the light (Home 8 needs 1600 --
                             // it has a whole nebula to fill; a house has rooms).
                             // Was 700, which over navy walls read as a starfield --
                             // additive points on a dark field are stars, not dust,
                             // and a daylit house cannot have a night sky on its walls.
  // Home 8's STRUCT pool -- 1500 particles that condense out of dust onto the
  // wireframe -- does not exist here. A house does not assemble itself in front of
  // you; it is already there and you are the one arriving. The pool is gone rather
  // than zeroed, so the buffers are ~40% smaller too.
  var STRUCT = 0;
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
  var WALL_T = 0.5;     // wall thickness -- what gives a doorway its reveal
  var ROOM_D = 20;      // how far a room reaches past its doorway
  var ROOM_H = 7;       // half-depth of a room along the hallway
  var ROOM_IN = 8;      // how far into the room the camera stands at dwell

  // side: +1 room on +X, -1 room on -X, 0 = no room (stay in the hallway).
  // Section 0 is the threshold -- you arrive IN the hallway, so it has no room and
  // its page hangs straight down the corridor. side:0 makes the turn/walk lerps
  // no-ops, so one code path covers it.
  // Home 8's rooms were abstractions -- a "listings wall", a "valuation desk".
  // These are rooms in a house. Each still carries the same section, but it is
  // furnished as somewhere a person would actually stand.
  var ROOM = [
    { z: 0,   side: 0,  p: 0.40, theme: 'foyer',   art: '01-arrival.jpg' },
    { z: -8,  side: 1,  p: 0.70, theme: 'living',  art: '02-welcome.jpg' },
    { z: -16, side: -1, p: 0.95, theme: 'gallery', art: '03-listings.jpg' },
    { z: -24, side: 1,  p: 0.85, theme: 'study',   art: '04-legacy.jpg' },
    { z: -32, side: -1, p: 0.85, theme: 'entry',   art: '05-door.jpg' },
    { z: -40, side: 1,  p: 0.85, theme: 'dining',  art: '06-communities-lake.webp' },
    { z: -48, side: -1, p: 0.55, theme: 'kitchen', art: '07-value.jpg' },
    { z: -56, side: 1,  p: 0.70, theme: 'hearth',  art: '08-connect.jpg', artY: 3.5, artW: 4.6, artH: 2.4 }
  ];

  // Only two states are authored: standing in a room (flat), and the arc between
  // two rooms. |u| <= U_DWELL is the flat one -- the pose is literally constant
  // across it, which is what makes d == D0 and s == 1 hold for the whole dwell and
  // lets an inner scroller absorb reading time without the camera creeping.
  //
  // Everything else is ONE overlapping arc; see poseAt(). The first cut chopped it
  // into four separate smoothstepped legs (back out | travel | turn | walk in) and
  // the camera came to a dead stop at every boundary, because smoothstep's
  // velocity is zero at both ends. Six full stops per room. Overlapping the legs
  // is what makes it a walk instead of a sequence of moves.
  var U_DWELL = 0.16;
  var U_FAR = -0.45, U_GONE = 0.45;      // outside this, the page does not exist
  var U_IN = -U_DWELL, U_OUT = U_DWELL;  // reading window == the flat pose window
  // Live body comes in before the turn completes, so the relayout hitch lands
  // while the camera is still swinging rather than as the page settles.
  var U_BODY = -0.40;

  // Motes in lamplight, not stars in a nebula. Warm whites and brass rather than
  // Home 8's tide/bright-blue field -- dust catching the light in a lived-in room.
  var PAL = [
    [1.00, 0.96, 0.88], [1.00, 0.93, 0.80],
    [0.98, 0.86, 0.66], [0.90, 0.76, 0.54],
    [0.79, 0.66, 0.30], [1.00, 0.84, 0.52]
  ];

  // ---- palette: the house IS the brand -------------------------------------
  // The first pass dressed the house in generic warm neutrals -- oatmeal plaster,
  // taupe linen -- and it could have been any nice house. It read as luxurious and
  // as nobody in particular. These are BRAND.md's actual values, and the house is
  // now built out of them, so the brand is the architecture rather than a logo
  // stuck on it.
  //
  // BRAND.md's own rule is the composition: "CB Blue + lots of white +
  // Midnight/Slate for depth. Use Bright Blue + Celestial sparingly as energy
  // accents." So: Icy Blue walls (lots of white), CB Blue on the one wall you
  // stand and face, Mist wainscot, Slate stone, and gold -- which harness/
  // palette.php proves is CB Blue's exact complement, to 2.6 degrees -- on
  // everything that glows.
  var M_ICY = 0xf0f5fb;      // BRAND.md Icy Blue    -- "off-white sections"
  var M_GLACIER = 0xdae1e8;  // BRAND.md Glacier     -- "very light section backgrounds"
  var M_MIST = 0xbecad7;     // BRAND.md Mist        -- "light backgrounds, hover states"
  var M_TIDE = 0xb8cfea;     // BRAND.md Tide        -- "soft blue accents"
  var M_SMOKY = 0x58718d;    // BRAND.md Smoky Gray  -- "secondary surfaces"
  var M_NAVY = 0x012169;     // BRAND.md CB Blue     -- the signature
  var M_SLATE = 0x1b3c55;    // BRAND.md Slate       -- "mid-tone navy"
  var M_MIDNIGHT = 0x0a1730; // BRAND.md Midnight    -- "navy depth"
  // The wall navy. The TARGET -- the colour the walls should read as on screen --
  // is #063970. This material value is deliberately brighter, because the plaster
  // map and the room light multiply the colour DOWN before it reaches the eye:
  // painting the literal #063970 sampled near-black. Tuned against capture pixel-
  // samples under the current (linear) tone mapping until a representative lit wall
  // reads ~rgb(8,57,112) -- i.e. #063970. Retune if the tone mapping, the plaster
  // map, or the room lighting changes.
  var M_WALLNAVY = 0x0e7dea;
  var M_TRIM = 0xffffff;     // BRAND.md White       -- "whitespace-first foundation"
  var M_BRASS = 0xc9a84c;    // CONTRACT.md gold: CB Blue's complement
  var M_CREAM = 0xf0ebe0;    // CONTRACT.md cream
  // Floors stay wood: BRAND.md has no opinion on oak, and a blue floor would be a
  // costume rather than a home. The warmth of the timber is what keeps a
  // white-and-navy interior from going clinical.
  var M_WALNUT = 0x4a3222, M_OAK = 0x8a6440;
  var M_LINEN = M_GLACIER, M_GLASS = 0x9fb6c9;
  var LAMP_HEX = 0xffe9c4;   // every warm light and every glowing shade

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

  var bMode = new Float32Array(BURST), bAge = new Float32Array(BURST), bLife = new Float32Array(BURST), bA0 = new Float32Array(BURST);
  var bsx = new Float32Array(BURST), bsy = new Float32Array(BURST), bsz = new Float32Array(BURST);
  var btx = new Float32Array(BURST), bty = new Float32Array(BURST), btz = new Float32Array(BURST);
  var bvx = new Float32Array(BURST), bvy = new Float32Array(BURST), bvz = new Float32Array(BURST);
  var bPtr = 0;

  var corridorGroup = null, _corridorReady = false;
  var signage = [];

  var vw = 0, vh = 0, dpr = 1, halfW = 0, halfH = 0, upp12 = 0;
  var rafId = null, lastT = 0, uTime = 0, started = false, initialized = false;
  var mouseNX = 0, mouseNY = 0, scrollY = 0;
  var oX = 0, oY = 0, oZ = CAMZ, oTX = 0, oTY = 0, oTZ = CAMZ, oP = 1;
  var corridorAssembly = 0;
  var _artBase = '';   // where the framed scene plates are loaded from
  var _texBase = '';   // where the generated material samples live
  var _texLoader = null;
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
  // Smootherstep. Smoothstep's VELOCITY is zero at both ends, but its
  // ACCELERATION jumps (2nd derivative is 6 at t=0), so every segment boundary in
  // the walk lands a small jerk. This one zeroes the first AND second derivatives
  // at both ends -- C2 instead of C1 -- so the camera changes pace without ever
  // snapping into it. Same cost, strictly smoother; band() is the only consumer.
  function smoother(t) { return t * t * t * (t * (t * 6 - 15) + 10); }
  function band(x, a, b) { return smoother(clamp01((x - a) / (b - a))); }
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
    // 120.0 -> 38.0. Home 8's motes are stars in a void, metres wide and meant to
    // be seen. Dust in a lit room is nearly invisible until it crosses a lamp --
    // at the old size these fell through the hallway like snow.
    '  gl_PointSize = aSize * uPixelRatio * (38.0 / max(dist, 1.0));',
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

  // Home 8's LVERT/LFRAG (the additive wireframe line layer) are gone. There are no
  // edges in a house -- only surfaces, and surfaces need light, not glow.

  // ---- frustum + ambient --------------------------------------------------
  function computeExtents() {
    halfH = Math.tan((FOV * 0.5) * Math.PI / 180) * CAMZ;
    halfW = halfH * (vw / vh);
    upp12 = (2 * D0 * TAN_HALF) / vh;   // world units per CSS px at reading distance
  }
  function seedAmbient() {
    var i, c;
    for (i = 0; i < AMB; i++) {
      // Motes hang INSIDE the house -- within the walls, above the floor -- not in
      // an open void. Home 8 seeded a nebula because there was no interior to be
      // inside of; here anything outside the shell is behind a wall and invisible,
      // so seeding it just wastes particles.
      pos[i * 3] = rand(-(HALL_X + ROOM_D - 1), HALL_X + ROOM_D - 1);
      pos[i * 3 + 1] = rand(-HALL_Y + 0.5, HALL_Y - 0.5);
      pos[i * 3 + 2] = rand(HALL_Z1 + 2, HALL_Z0 - 1);
      c = PAL[(Math.random() < 0.22) ? (2 + (Math.random() * 4 | 0)) : (Math.random() < 0.5 ? 0 : 1)];
      color[i * 3] = c[0]; color[i * 3 + 1] = c[1]; color[i * 3 + 2] = c[2];
      // Small and faint: dust caught in a shaft of light, not stars. Dropped well
      // below the old 0.18-0.5 so an additive mote adds only a whisper over navy.
      size[i] = rand(0.4, 1.0); alpha[i] = rand(0.05, 0.13); phase[i] = Math.random() * TAU;
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

  // ---- the house ----------------------------------------------------------
  // Home 8 built edges; this builds rooms. Materials and geometries are made once
  // and shared across all eight rooms -- ~150 meshes but only a dozen materials,
  // so the cost is in draw calls, not in shader churn.
  var MAT = {}, GEO = {}, houseGroup = null;
  var _lampLight = null, _handLight = null, _lampPts = [];
  // Textures that are still loading. Capture mode waits on this: it renders a
  // fixed handful of frames and stops, so a ~500kB scene plate that decodes on
  // frame 8 is simply never drawn and the shot comes back with empty frames on the
  // walls. Home 8 got away with it because its only async texture was an 8kB SVG.
  var _pendingTex = 0;

  /**
   * Procedural canvas textures.
   *
   * The repo ships no texture assets and has no build step, so oak grain and rug
   * pile are drawn at runtime: a few milliseconds once, and the file stays as
   * self-contained as everything else vendored in this theme. It is also why the
   * wood reads as wood at all -- a flat brown Lambert surface looks like plastic,
   * and no amount of warm lighting rescues it.
   */
  function texWood() {
    var c = document.createElement('canvas'); c.width = 256; c.height = 256;
    var g = c.getContext('2d'), i, y, xx, yy;
    // Light oak, not the near-black walnut this started as. Once the texture is
    // correctly decoded as sRGB, #5b3d28 lands at ~0.10 linear -- multiply that by
    // a 0.26 ambient and the floor is black no matter how the lamps are tuned. The
    // fix is the material, not the lighting: a real floor is simply lighter than
    // the furniture standing on it.
    g.fillStyle = '#9c6f45'; g.fillRect(0, 0, 256, 256);
    for (i = 0; i < 16; i++) {                       // board seams
      y = i * 16;
      g.fillStyle = 'rgba(255,226,178,0.10)'; g.fillRect(0, y + 1, 256, 14);
      g.fillStyle = 'rgba(48,26,10,0.5)'; g.fillRect(0, y, 256, 1);
    }
    for (i = 0; i < 260; i++) {                      // grain
      yy = rand(0, 256); xx = rand(0, 256);
      g.strokeStyle = 'rgba(70,40,16,' + rand(0.05, 0.16).toFixed(3) + ')';
      g.lineWidth = rand(0.5, 1.4);
      g.beginPath(); g.moveTo(xx, yy);
      g.bezierCurveTo(xx + 26, yy + rand(-1.5, 1.5), xx + 52, yy + rand(-1.5, 1.5), xx + 84, yy);
      g.stroke();
    }
    var t = new THREE.CanvasTexture(c);
    t.wrapS = t.wrapT = THREE.RepeatWrapping;
    return sRGB(t);
  }
  /**
   * Tag a texture as sRGB.
   *
   * This is not a nicety -- it is why the first pass looked like flat beige
   * cardboard. Three r152+ enables colour management and renders to sRGB, but a
   * texture defaults to NO colour space, i.e. it is taken as already-linear. A
   * canvas drawn with '#5b3d28' is sRGB data, so leaving it untagged skips the
   * decode: mid-tones lift, contrast collapses, and every surface converges on the
   * same washed value no matter what the lights do.
   */
  function sRGB(t) {
    if (THREE.SRGBColorSpace) { t.colorSpace = THREE.SRGBColorSpace; }
    return t;
  }

  function texRug() {
    var c = document.createElement('canvas'); c.width = 128; c.height = 128;
    var g = c.getContext('2d'), i;
    // CB Blue ground, cream border, gold keyline -- the brand's own three values,
    // in the one object you look straight down at in every room.
    g.fillStyle = '#012169'; g.fillRect(0, 0, 128, 128);
    g.strokeStyle = 'rgba(240,235,224,0.42)'; g.lineWidth = 3;
    g.strokeRect(9, 9, 110, 110); g.strokeRect(19, 19, 90, 90);
    g.strokeStyle = 'rgba(201,168,76,0.65)'; g.lineWidth = 2;
    g.strokeRect(14, 14, 100, 100);
    for (i = 0; i < 900; i++) {                      // pile
      g.fillStyle = 'rgba(0,0,0,' + rand(0.02, 0.09).toFixed(3) + ')';
      g.fillRect(rand(0, 128), rand(0, 128), 2, 2);
    }
    return sRGB(new THREE.CanvasTexture(c));
  }

  /** The soft pool of light a wall fitting throws on its own wall. */
  function texGlow() {
    var c = document.createElement('canvas'); c.width = 128; c.height = 128;
    var g = c.getContext('2d');
    var grd = g.createRadialGradient(64, 64, 2, 64, 64, 62);
    grd.addColorStop(0, 'rgba(255,233,196,0.85)');
    grd.addColorStop(0.45, 'rgba(255,214,150,0.30)');
    grd.addColorStop(1, 'rgba(255,200,120,0)');
    g.fillStyle = grd; g.fillRect(0, 0, 128, 128);
    return new THREE.CanvasTexture(c);
  }

  /** What is on the far side of a window: a bright bar of daylit sky, cool at the
   *  top and paling toward the horizon. Used unlit and fog-exempt so the pane
   *  glows like an actual opening onto the outdoors rather than a painted panel. */
  function texSky() {
    var c = document.createElement('canvas'); c.width = 96; c.height = 160;
    var g = c.getContext('2d'), TAU = Math.PI * 2;
    var grd = g.createLinearGradient(0, 0, 0, 160);
    grd.addColorStop(0.00, '#5f9fe0');   // deeper blue overhead
    grd.addColorStop(0.50, '#a9d2f2');
    grd.addColorStop(1.00, '#d6e8f6');   // pale at the horizon, but NOT washed white
    g.fillStyle = grd; g.fillRect(0, 0, 96, 160);
    // Clouds as clusters of feathered radial puffs rather than hard ellipses, so
    // their edges melt into the sky instead of reading as pasted-on stamps.
    function puff(cx, cy, spread, a) {
      for (var i = 0; i < 6; i++) {
        var px = cx + (Math.random() - 0.5) * spread * 1.6;
        var py = cy + (Math.random() - 0.5) * spread * 0.7;
        var r = spread * (0.45 + Math.random() * 0.6);
        var rg = g.createRadialGradient(px, py, 0, px, py, r);
        rg.addColorStop(0, 'rgba(255,255,255,' + a + ')');
        rg.addColorStop(1, 'rgba(255,255,255,0)');
        g.fillStyle = rg;
        g.beginPath(); g.arc(px, py, r, 0, TAU); g.fill();
      }
    }
    puff(30, 50, 15, 0.5); puff(66, 92, 18, 0.42); puff(20, 116, 12, 0.32);
    return sRGB(new THREE.CanvasTexture(c));
  }

  /** A soft contact shadow to sit under furniture. Nothing grounds an object like
   *  a shadow, and with no shadow maps this is what stops the sofa looking as if it
   *  is hovering half an inch above the floor. */
  function texShadow() {
    var c = document.createElement('canvas'); c.width = 128; c.height = 128;
    var g = c.getContext('2d');
    var grd = g.createRadialGradient(64, 64, 4, 64, 64, 62);
    grd.addColorStop(0, 'rgba(0,0,0,0.72)');
    grd.addColorStop(0.55, 'rgba(0,0,0,0.34)');
    grd.addColorStop(1, 'rgba(0,0,0,0)');
    g.fillStyle = grd; g.fillRect(0, 0, 128, 128);
    return new THREE.CanvasTexture(c);   // alpha only -- no colour to decode
  }
  function texPlaster() {
    var c = document.createElement('canvas'); c.width = 128; c.height = 128;
    var g = c.getContext('2d'), i;
    g.fillStyle = '#F0F5FB'; g.fillRect(0, 0, 128, 128);   // BRAND.md Icy Blue
    for (i = 0; i < 2400; i++) {                     // tooth, so it is not vinyl
      // The tooth is Smoky Gray, not brown: speckling a brand off-white with warm
      // grit is what quietly turned Icy Blue into oatmeal in the first pass.
      g.fillStyle = 'rgba(88,113,141,' + rand(0.02, 0.07).toFixed(3) + ')';
      g.fillRect(rand(0, 128), rand(0, 128), 1, 1);
    }
    var t = new THREE.CanvasTexture(c);
    t.wrapS = t.wrapT = THREE.RepeatWrapping;
    return sRGB(t);
  }

  /**
   * MeshStandardMaterial, not Lambert.
   *
   * Lambert has no specular term at all -- every surface is perfectly matte, which
   * is why the first pass read as cardboard regardless of the lighting. Polished
   * oak, stone counters and a lacquered desk are defined by their sheen; without a
   * roughness response there is nothing to tell them apart from paper.
   *
   * metalness stays at 0 or near it throughout, deliberately: there is no envMap
   * here, and a metal with nothing to reflect renders black.
   */
  function surf(o) {
    o.metalness = (o.metalness === undefined) ? 0 : o.metalness;
    return new THREE.MeshStandardMaterial(o);
  }

  /**
   * A photographic material sample (Higgsfield-generated, in assets/images/
   * textures/), loaded async and repeated across the surface.
   *
   * Returns null when there is no texture base -- the flat fallback, no-JS, or a
   * missing file -- and every caller falls back to the procedural canvas texture
   * or a flat colour. So the generated look is a pure enhancement: nothing depends
   * on it. Hooks _pendingTex so capture waits for the maps to decode, the same as
   * the framed art. Tinting is left to the material's `color`: the plaster sample
   * is deliberately near-neutral so a `color` of Icy Blue / Mist / CB Blue paints
   * the same wall three brand shades without three textures.
   */
  function photoTex(file, rx, ry) {
    if (!_texBase) { return null; }
    if (!_texLoader) { _texLoader = new THREE.TextureLoader(); }
    _pendingTex++;
    var done = function () { _pendingTex--; };
    var t = _texLoader.load(_texBase + file, done, undefined, done);
    if (THREE.SRGBColorSpace) { t.colorSpace = THREE.SRGBColorSpace; }
    t.wrapS = t.wrapT = THREE.RepeatWrapping;
    t.repeat.set(rx, ry);
    // Anisotropy matters most on the hall floor, seen at a grazing angle down its
    // whole length -- without it the boards smear to mush in the distance.
    try { if (renderer) { t.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy()); } } catch (e) {}
    return t;
  }

  function buildMaterials() {
    // Photographic samples where they exist, procedural fall-backs where they do
    // not. The generated oak, plaster and velvet replace the three surfaces the
    // eye actually lands on -- floor, walls, upholstery -- which is where "boxes
    // with flat colours" most gave itself away.
    var oakRoom = photoTex('oak.jpg', 3, 4.5);
    var oakHall = photoTex('oak.jpg', 2, 22);
    var plasterW = photoTex('plaster.jpg', 3, 2);
    var plasterA = photoTex('plaster.jpg', 3, 2);
    var velvetT = photoTex('velvet.jpg', 2.4, 2.4);

    if (oakRoom) { MAT.floor = surf({ map: oakRoom, roughness: 0.4 }); }
    else { var wood = texWood(); wood.repeat.set(6, 10); MAT.floor = surf({ map: wood, roughness: 0.42 }); }

    if (oakHall) { MAT.hallFloor = surf({ map: oakHall, roughness: 0.4 }); }
    else { var hw = texWood(); hw.repeat.set(3, 30); MAT.hallFloor = surf({ map: hw, roughness: 0.42 }); }

    // The plaster sample is neutral, so `color` does the branding. The walls are
    // now navy throughout -- every plane the same M_WALLNAVY as the accent wall, so
    // there is no longer a "light" wall and a "navy" wall, just one navy field
    // wrapping the room and the hall, set off by white trim. (Was Icy Blue; the
    // brief now wants every wall navy.) One photographic texture, still no seams.
    MAT.wall = surf({ map: plasterW, color: M_WALLNAVY, roughness: 0.94 });
    MAT.ceil = surf({ color: M_TRIM, roughness: 0.95 });
    MAT.trim = surf({ color: M_TRIM, roughness: 0.55 });   // painted joinery has a slight sheen
    MAT.wains = surf({ map: plasterW, color: M_MIST, roughness: 0.7 });
    // Navy below the chair rail -- the wainscot/dado, in both the rooms and the
    // hallway. It is painted the SAME M_WALLNAVY as the walls above it: with every
    // wall now navy, a wainscot in raw CB Blue read as a near-black band under the
    // lighter field, exactly the "reads as black" problem the lift was meant to
    // cure. Matched, the whole wall is one navy, articulated only by white trim.
    MAT.wainsNavy = surf({ map: plasterA, color: M_WALLNAVY, roughness: 0.94 });   // matched to MAT.wall: one uniform navy top to bottom, no shinier dado band
    // The one wall you stand and face, in the signature colour. BRAND.md's rule is
    // "CB Blue + lots of white" -- so CB Blue is the accent the white is there to
    // set off, not the wallpaper. Every room has exactly one. Painted M_WALLNAVY,
    // not raw CB Blue: at this fill level the true #012169 reads as black, so the
    // wall is lifted just far enough to be seen as the navy it is meant to be.
    MAT.accent = surf({ map: plasterA, color: M_WALLNAVY, roughness: 0.94 });   // matched to MAT.wall's roughness so no single wall reads as a different shade
    if (!plasterW) { MAT.wall.map = MAT.wains.map = texPlaster(); }   // procedural fallback keeps the tooth
    var walnutT = photoTex('walnut.jpg', 1.6, 1.6);
    MAT.walnut = walnutT ? surf({ map: walnutT, roughness: 0.4 }) : surf({ color: M_WALNUT, roughness: 0.38 });
    MAT.oak = surf({ color: M_OAK, roughness: 0.5 });
    // Oak with the floor's actual grain, for furniture asked to match the wood
    // floor rather than read as dark walnut. Same sample as the floor at a tighter,
    // furniture-scale repeat; falls back to the flat oak colour if the photo is
    // absent, so it still "matches the oak" either way.
    var oakFurn = photoTex('oak.jpg', 1.6, 2.6);
    MAT.oakWood = oakFurn ? surf({ map: oakFurn, roughness: 0.44 }) : surf({ color: M_OAK, roughness: 0.44 });
    // Navy velvet upholstery, tinted DOWN to CB Blue. The raw sample is a brightish
    // royal navy; multiplying by a mid periwinkle darkens it toward the signature
    // #012169 so the upholstery, the accent walls and the rug all read as the one
    // brand blue rather than three different navies. (Multiply can only darken, so
    // the tint pulls a bright navy toward the dark CB Blue, not the reverse.)
    if (velvetT) { MAT.navy = surf({ map: velvetT, color: 0x3a5aa6, roughness: 0.6 }); }
    else { MAT.navy = surf({ color: M_NAVY, roughness: 0.85 }); }
    MAT.slate = surf({ color: M_SLATE, roughness: 0.28, metalness: 0.08 });  // stone
    MAT.linen = surf({ color: M_LINEN, roughness: 0.85 });
    MAT.cream = surf({ color: M_CREAM, roughness: 0.8 });
    MAT.rug = surf({ map: texRug(), roughness: 0.98 });    // wool reflects nothing
    // ONLY the shade is unlit. That distinction is the whole reason the sconces
    // looked like they were floating, and no amount of moving them fixed it:
    // MAT.brass was MeshBasicMaterial, so every piece of metal in the house --
    // backplates, arms, picture frames, handles, lamp columns -- took NO shading
    // whatsoever. Unlit metal has no depth and no relationship to the surface
    // behind it; it renders as a flat gold cutout, i.e. a sticker. The geometry
    // was already touching the wall. The material was what made it read as pasted
    // on.
    //
    // A lampshade genuinely IS emitting, so it stays unlit -- shade it and the
    // light source looks switched off. Brass is not a light; it is a thing the
    // light falls on.
    //
    // metalness stays low: there is no envMap, and a mirror with nothing to
    // reflect renders black. The emissive keeps it warm in the dark corners
    // rather than going muddy where the lamps do not reach.
    MAT.brass = surf({ color: M_BRASS, roughness: 0.3, metalness: 0.2, emissive: 0x140d02 });
    MAT.shade = new THREE.MeshBasicMaterial({ color: LAMP_HEX });
    // The pool a fitting throws on the wall it is mounted to. Nothing says
    // "attached" like the wall lighting up around it.
    MAT.glow = new THREE.MeshBasicMaterial({
      map: texGlow(), transparent: true, blending: THREE.AdditiveBlending,
      depthWrite: false, opacity: 0.5
    });
    MAT.glass = surf({ color: M_GLASS, roughness: 0.1, transparent: true, opacity: 0.35 });
    // Daylight seen THROUGH a window. Unlit (it is the source, not a lit surface)
    // and fog:false so distance across the room cannot mute it toward Midnight --
    // a window that dims with depth stops reading as an opening onto outside.
    MAT.sky = new THREE.MeshBasicMaterial({ map: texSky(), fog: false });
    MAT.shadow = new THREE.MeshBasicMaterial({
      map: texShadow(), transparent: true, opacity: 0.9, depthWrite: false
    });

    GEO.box = new THREE.BoxGeometry(1, 1, 1);
    GEO.plane = new THREE.PlaneGeometry(1, 1);
    GEO.cyl = new THREE.CylinderGeometry(1, 1, 1, 12);
  }

  /**
   * A wall sconce: backplate, arm, tapered shade.
   *
   * The old one was two boxes placed for a wall that no longer exists. When the
   * walls gained thickness (WALL_T), the hallway face moved from x=6.0 in to 5.75
   * -- and the brass backplate, still sitting at 5.82, ended up INSIDE the wall.
   * All that survived was the glowing shade, stuck to nothing, which is why they
   * read as cards floating in mid-air.
   *
   * Everything is now measured from the wall FACE, so it cannot drift again if the
   * wall changes: backplate straddling the face (half buried, so no coplanar
   * z-fight), an arm reaching out of it, and the shade on the end of the arm.
   */
  function sconce(ss, z) {
    var face = ss * (HALL_X - WALL_T / 2);   // the plaster you mount it on
    var out = function (d) { return face - ss * d; };   // d units into the hallway

    // The light it throws on its own wall. This does more for "mounted" than any
    // amount of bracketry: a fitting that is ON lights the plaster around it, and
    // the eye reads that pool as contact. Sits just proud of the face, additive.
    var pool = new THREE.Mesh(GEO.plane, MAT.glow);
    pool.position.set(out(0.012), 1.75, z);
    pool.scale.set(2.6, 3.4, 1);
    pool.rotation.y = ss > 0 ? -Math.PI / 2 : Math.PI / 2;
    pool.renderOrder = 1;
    houseGroup.add(pool);

    box(MAT.brass, out(0.02), 1.25, z, 0.12, 1.05, 0.4);   // backplate, on the wall
    box(MAT.brass, out(0.06), 1.78, z, 0.08, 0.1, 0.48);   // its cap
    box(MAT.brass, out(0.16), 1.62, z, 0.3, 0.07, 0.07);   // arm reaching out
    cyl(MAT.brass, out(0.29), 1.5, z, 0.035, 0.3);         // riser to the shade

    var shade = new THREE.Mesh(new THREE.CylinderGeometry(0.19, 0.3, 0.42, 14, 1, true), MAT.shade);
    shade.position.set(out(0.29), 1.86, z);
    houseGroup.add(shade);

    _lampPts.push(new THREE.Vector3(out(0.42), 1.86, z));
  }

  /**
   * A recessed ceiling downlight, flush with the ceiling: a white trim ring set
   * into the plaster with a glowing lens, and the light itself hanging just below
   * so it washes DOWN the hall rather than out from a wall.
   */
  function ceilingLight(cx, cz) {
    var yc = HALL_Y;
    // Trim ring flush with the ceiling. A short cylinder, its underside just proud
    // of the plaster so it reads as set INTO it, not stuck under it.
    cyl(MAT.trim, cx, yc - 0.03, cz, 0.4, 0.06);
    // The lit lens, recessed a touch above the ring.
    cyl(MAT.shade, cx, yc - 0.09, cz, 0.3, 0.05);
    // A soft downward pool on the ceiling around it, so the fitting reads as ON.
    var pool = new THREE.Mesh(GEO.plane, MAT.glow);
    pool.position.set(cx, yc - 0.11, cz);
    pool.scale.set(2.2, 2.2, 1);
    pool.rotation.x = Math.PI / 2;   // faces down
    pool.renderOrder = 1;
    houseGroup.add(pool);
    // Light hangs below the ceiling so it actually lights the hall from above.
    _lampPts.push(new THREE.Vector3(cx, yc - 0.6, cz));
  }

  /**
   * A floor lamp: it brings its own stand, so it needs no table and cannot end up
   * hovering over one that is not there.
   */
  function floorLamp(cx, cz) {
    shadowPad(cx, cz, 1.7, 1.7);
    cyl(MAT.brass, cx, -HALL_Y + 0.05, cz, 0.34, 0.1);     // weighted base, ON the floor
    cyl(MAT.brass, cx, -HALL_Y + 1.75, cz, 0.05, 3.3);     // column
    var shade = new THREE.Mesh(new THREE.CylinderGeometry(0.44, 0.6, 0.72, 16, 1, true), MAT.shade);
    shade.position.set(cx, -HALL_Y + 3.75, cz);
    houseGroup.add(shade);
    _lampPts.push(new THREE.Vector3(cx, -HALL_Y + 3.65, cz));
  }

  /** Contact shadow on the floor, just above it. */
  function shadowPad(cx, cz, w, d) {
    var m = new THREE.Mesh(GEO.plane, MAT.shadow);
    m.position.set(cx, -HALL_Y + 0.04, cz);
    m.rotation.x = -Math.PI / 2;
    m.scale.set(w, d, 1);
    m.renderOrder = 1;
    houseGroup.add(m);
    return m;
  }

  /**
   * A rug laid ON the oak, not painted into it.
   *
   * A flat plane at floor level reads as an inlay in the boards; a rug is an object
   * resting on top of them. So the rug is a thin slab whose edge stands a few
   * millimetres proud of the floor -- you can see it has a thickness sitting on the
   * wood -- over a soft contact shadow a little larger than the rug, so it reads as
   * resting on the hardwood and casting a shadow rather than being part of it. The
   * oak is left generously visible around every rug for the same reason: you only
   * read "rug ON a floor" if you can see the floor it is on.
   */
  function rugOnFloor(cx, cz, w, d) {
    var sh = new THREE.Mesh(GEO.plane, MAT.shadow);
    sh.position.set(cx, -HALL_Y + 0.02, cz);
    sh.rotation.x = -Math.PI / 2;
    sh.scale.set(w + 1.1, d + 1.1, 1);
    sh.renderOrder = 1;
    houseGroup.add(sh);
    box(MAT.rug, cx, -HALL_Y + 0.055, cz, w, 0.07, d);   // slab; the edge shows proud of the boards
  }

  /** box(material, centre, size) -- everything in the house is a box or a plane. */
  function box(mat, cx, cy, cz, w, h, d) {
    var m = new THREE.Mesh(GEO.box, mat);
    m.position.set(cx, cy, cz); m.scale.set(w, h, d);
    houseGroup.add(m); return m;
  }
  /** plane facing an axis. dir: 'up'|'down'|'+x'|'-x'|'+z'|'-z' */
  function plane(mat, cx, cy, cz, w, h, dir) {
    var m = new THREE.Mesh(GEO.plane, mat);
    m.position.set(cx, cy, cz); m.scale.set(w, h, 1);
    if (dir === 'up') { m.rotation.x = -Math.PI / 2; }
    else if (dir === 'down') { m.rotation.x = Math.PI / 2; }
    else if (dir === '+x') { m.rotation.y = Math.PI / 2; }
    else if (dir === '-x') { m.rotation.y = -Math.PI / 2; }
    else if (dir === '+z') { /* faces +z */ }
    else if (dir === '-z') { m.rotation.y = Math.PI; }
    houseGroup.add(m); return m;
  }
  function cyl(mat, cx, cy, cz, r, h) {
    var m = new THREE.Mesh(GEO.cyl, mat);
    m.position.set(cx, cy, cz); m.scale.set(r, h, r);
    houseGroup.add(m); return m;
  }

  /** A table lamp: brass stem, glowing shade, and a registered light position.
   *  The shade is unlit geometry; the actual PointLight is roved to the nearest
   *  of these each frame (see updateLights) so the light COUNT never changes --
   *  Three recompiles every material when it does, which would hitch mid-walk. */
  /**
   * A lamp. `cy` is the shade; the fitting reaches from there to whatever holds it
   * up, and THAT is the part that has to be true.
   *
   * `standsOn` is the Y of the surface it sits on -- a tabletop, or the floor. The
   * stem is drawn to reach it, however far that is, instead of being a fixed 1.0
   * long and hoping. Every table lamp in the house was floating because of that
   * assumption: the living-room lamp hung 1.8 units over bare floor, the entry
   * lamp 2.4, and the study lamp was 0.6 UNDER its own desktop, buried in the desk.
   *
   * `pendant: true` hangs it from the ceiling instead, with the stem drawn to the
   * actual ceiling height -- the kitchen's pendants were hanging from stems that
   * stopped 2.1 units short of it, attached to nothing at all.
   */
  function lamp(cx, cy, cz, standsOn, pendant) {
    var shadeTop = cy + 0.6, shadeBot = cy;
    if (pendant) {
      cyl(MAT.brass, cx, (HALL_Y + shadeTop) / 2, cz, 0.04, HALL_Y - shadeTop);
    } else {
      var base = (standsOn === undefined) ? -HALL_Y : standsOn;
      cyl(MAT.brass, cx, (base + shadeBot) / 2, cz, 0.06, Math.max(0.05, shadeBot - base));
      cyl(MAT.brass, cx, base + 0.03, cz, 0.28, 0.06);   // foot, ON the surface
    }
    var shade = new THREE.Mesh(new THREE.CylinderGeometry(0.34, 0.5, 0.6, 14, 1, true), MAT.shade);
    shade.position.set(cx, cy + 0.3, cz);
    houseGroup.add(shade);
    _lampPts.push(new THREE.Vector3(cx, cy + 0.2, cz));
  }

  /** Wainscot + baseboard + crown along a wall run. This trim is most of why the
   *  rooms read as "house" rather than "box with a wood floor". */
  function trimRun(axis, fixed, from, to, sign, mat) {
    mat = mat || MAT.wains;
    var mid = (from + to) / 2, len = Math.abs(to - from);
    var off = 0.06 * sign;
    if (axis === 'x') {   // wall lies in the Y-Z plane at x = fixed
      box(mat, fixed + off, -HALL_Y + 1.4, mid, 0.12, 2.8, len);
      box(MAT.trim, fixed + off * 1.6, -HALL_Y + 0.18, mid, 0.2, 0.36, len);
      box(MAT.trim, fixed + off * 1.6, -HALL_Y + 2.86, mid, 0.2, 0.12, len);
      box(MAT.trim, fixed + off * 1.6, HALL_Y - 0.16, mid, 0.2, 0.32, len);
    } else {              // wall lies in the X-Y plane at z = fixed
      box(mat, mid, -HALL_Y + 1.4, fixed + off, len, 2.8, 0.12);
      box(MAT.trim, mid, -HALL_Y + 0.18, fixed + off * 1.6, len, 0.36, 0.2);
      box(MAT.trim, mid, -HALL_Y + 2.86, fixed + off * 1.6, len, 0.12, 0.2);
      box(MAT.trim, mid, HALL_Y - 0.16, fixed + off * 1.6, len, 0.32, 0.2);
    }
  }

  /** Home 2's scene plate, framed and hung on the room's far wall.
   *  In Home 8 these sat behind the page as atmosphere; on a wall, in a frame, is
   *  what a photograph of a place is actually for. */
  function hangArt(R, basePath) {
    if (!basePath || !R.art) { return; }
    var s = R.side, xw = s * (HALL_X + ROOM_D);
    // Per-room, because the far wall is not always empty. Defaults suit a bare
    // wall; a room with something large already on it says so. The alternative --
    // assuming every wall is clear -- is what put a door through the middle of the
    // entry room's picture and a fireplace through the bottom of the hearth's.
    var w = R.artW || 6.4, h = R.artH || 4.2;
    var ay = (R.artY === undefined) ? 1.1 : R.artY;
    // Depth order matters and is easy to get wrong: the camera stands at x = s*8
    // looking outward, so CLOSER means further from the far wall. The frame is a
    // solid box, not an outline -- the first pass centred it at xFar-0.16 with a
    // 0.12 thickness (spanning 25.78..25.90) and then put the picture at 25.79,
    // i.e. sealed inside the frame. It rendered as an empty gold rectangle, and
    // nothing errored, because nothing was wrong except the arithmetic.
    var xFrame = xw - s * 0.06;      // sits against the wall
    var xArt = xw - s * 0.14;        // clear of the frame's near face, toward you
    box(MAT.brass, xFrame, ay, R.z, 0.10, h + 0.44, w + 0.44);       // frame
    box(MAT.cream, xFrame - s * 0.02, ay, R.z, 0.08, h + 0.18, w + 0.18);   // mount
    var img = new Image();
    _pendingTex++;
    img.onload = function () {
      try {
        var tex = new THREE.Texture(img); tex.needsUpdate = true;
        if (THREE.SRGBColorSpace) { tex.colorSpace = THREE.SRGBColorSpace; }
        var m = new THREE.Mesh(GEO.plane, new THREE.MeshLambertMaterial({ map: tex }));
        m.position.set(xArt, ay, R.z);
        m.scale.set(w, h, 1);
        m.rotation.y = s > 0 ? -Math.PI / 2 : Math.PI / 2;
        if (houseGroup) { houseGroup.add(m); }
      } catch (e) {
        if (window.console) { console.warn('[cb9] could not hang art: ' + R.art, e); }
      }
      _pendingTex--;
    };
    img.onerror = function () {
      _pendingTex--;
      if (window.console) { console.warn('[cb9] art missing: ' + basePath + R.art); }
    };
    img.src = basePath + R.art;
  }

  /** A raised-panel outline on a Y-Z wall (a room's far wall), stood slightly
   *  proud of the plaster. Four thin sticks of trim -- the cheapest possible
   *  joinery, and the difference between a wall and a flat colour. */
  function panelYZ(x, cy, cz, h, d, s) {
    var o = -s * 0.05, t = 0.12;
    box(MAT.trim, x + o, cy + h / 2, cz, 0.09, t, d + t);
    box(MAT.trim, x + o, cy - h / 2, cz, 0.09, t, d + t);
    box(MAT.trim, x + o, cy, cz - d / 2, 0.09, h, t);
    box(MAT.trim, x + o, cy, cz + d / 2, 0.09, h, t);
    box(MAT.wains, x + o * 0.5, cy, cz, 0.04, h, d);   // the panel field itself
  }

  /**
   * A window where panelYZ would otherwise hang a blind panel.
   *
   * Same footprint as a panel so it drops straight into the far-wall layout, but
   * instead of a field of wainscot it is a white frame standing proud of the wall,
   * a cross of glazing bars, and behind them a pane of daylit sky. The pane alone
   * only LOOKS like a window; the room is actually lit from the opening by a light
   * placed just inside it (see the gallery case), which is what the brief means by
   * "natural light coming from the windows".
   */
  function windowYZ(x, cy, cz, h, d, s) {
    var xFrame = x - s * 0.08;   // frame stands proud of the wall, into the room
    var xGlass = x - s * 0.03;   // glass set behind the frame, just ahead of the wall
    var t = 0.16;
    box(MAT.trim, xFrame, cy + h / 2, cz, 0.14, t, d + t);        // head
    box(MAT.trim, xFrame, cy - h / 2, cz, 0.14, t + 0.12, d + t); // sill, a touch deeper
    box(MAT.trim, xFrame, cy, cz - d / 2, 0.14, h, t);           // jamb
    box(MAT.trim, xFrame, cy, cz + d / 2, 0.14, h, t);           // jamb
    box(MAT.trim, xFrame, cy, cz, 0.11, h, 0.08);               // vertical glazing bar
    box(MAT.trim, xFrame, cy, cz, 0.11, 0.08, d);              // horizontal glazing bar
    plane(MAT.sky, xGlass, cy, cz, d, h, s > 0 ? '-x' : '+x');   // the daylight itself
  }

  /**
   * A real entrance, not a hole.
   *
   * The wall has thickness (WALL_T), so an opening in it is a short tunnel: it
   * needs lining. Without the reveal you see the wall's zero-width edge and the
   * whole thing reads as paper -- which is exactly what it did. What sells a
   * doorway is, in order: the reveal you can see the depth of, the architrave
   * standing proud on BOTH faces, a threshold underfoot, and doors that are
   * actually open.
   */
  function entrance(R) {
    var s = R.side, z = R.z;
    var xw = s * HALL_X;                    // wall centre-line
    var half = WALL_T / 2;
    var W = 6, H = 8.6, top = -HALL_Y + H;  // opening: 6 wide, to y = 3.6

    // Reveal: line the inside faces of the opening.
    box(MAT.trim, xw, -HALL_Y + H / 2, z - W / 2 + 0.08, WALL_T, H, 0.16);
    box(MAT.trim, xw, -HALL_Y + H / 2, z + W / 2 - 0.08, WALL_T, H, 0.16);
    box(MAT.trim, xw, top - 0.08, z, WALL_T, 0.16, W);

    // Architrave on both faces. You see the hallway side on approach and the room
    // side once you are through; casing only one face looks like a stage flat.
    //
    // CASING_D is the whole point: it must stand PROUD of the wainscot, not level
    // with it. Both were previously a 0.12 slab hung on the same wall face, so
    // they occupied identical depth -- and where a casing leg crossed the wainscot
    // the two coplanar surfaces fought for the same z, which is what flickered.
    // Real casing is applied ON the wall and stands off it; nothing else in the
    // house is allowed to share its plane.
    var CASING_D = 0.22;                        // wainscot is 0.12 -- deeper, on purpose
    var faces = [xw - s * (half + CASING_D / 2), xw + s * (half + CASING_D / 2)];
    for (var f = 0; f < 2; f++) {
      var xf = faces[f];
      box(MAT.trim, xf, -HALL_Y + H / 2 + 0.1, z - W / 2 - 0.16, CASING_D, H + 0.2, 0.44);
      box(MAT.trim, xf, -HALL_Y + H / 2 + 0.1, z + W / 2 + 0.16, CASING_D, H + 0.2, 0.44);
      box(MAT.trim, xf, top + 0.12, z, CASING_D, 0.44, W + 1.1);
    }

    box(MAT.oak, xw, -HALL_Y + 0.035, z, WALL_T + 0.3, 0.07, W);   // threshold

    // Two leaves, swung fully into the room. A door standing open is the single
    // clearest signal that a room is somewhere you may go.
    for (var i = -1; i <= 1; i += 2) {
      var zj = z + i * (W / 2 - 0.08);
      var xl = xw + s * (half + 1.5);
      box(MAT.wains, xl, -HALL_Y + 4.2, zj - i * 0.09, 2.9, 8.2, 0.16);   // leaf -- light blue-grey (Mist), white casing left to frame it
      box(MAT.wains, xl, -HALL_Y + 6.0, zj - i * 0.17, 2.1, 2.9, 0.04);   // upper panel -- same Mist as the leaf, so each door is one uniform colour
      box(MAT.wains, xl, -HALL_Y + 2.2, zj - i * 0.17, 2.1, 2.9, 0.04);   // lower panel -- ditto
      cyl(MAT.brass, xw + s * (half + 2.7), -HALL_Y + 4.2, zj - i * 0.2, 0.09, 0.28);  // handle
    }
  }

  /**
   * An OPEN bookcase, not a solid slab with lines on it.
   *
   * The old one was a solid oak box with four thin trim slats laid on its front
   * face -- and the slats' back plane sat exactly on the box's front plane, so the
   * two coplanar surfaces z-fought and shimmered as the camera moved in and out of
   * the room. That is the "glitching shelf". A bookcase is a hollow carcass: a
   * back, two sides, a top and bottom, real shelves recessed INSIDE (touching
   * nothing coplanar), and books on them. Nothing here shares a plane with
   * anything else, so nothing fights.
   */
  function bookcase(s, cx, cz) {
    var byc = -1.4, BD = 0.9, BH = 7.2, BW = 3.4;
    var bTop = byc + BH / 2, bBot = byc - BH / 2;
    box(MAT.oak, cx + s * (BD / 2 - 0.05), byc, cz, 0.1, BH, BW);        // back panel
    box(MAT.oak, cx, byc, cz - BW / 2 + 0.06, BD, BH, 0.12);            // side
    box(MAT.oak, cx, byc, cz + BW / 2 - 0.06, BD, BH, 0.12);            // side
    box(MAT.oak, cx, bTop - 0.07, cz, BD, 0.14, BW);                   // top
    box(MAT.oak, cx, bBot + 0.07, cz, BD, 0.14, BW);                   // bottom (plinth)

    // Four compartments; a shelf caps the lower three. Each surface holds books.
    var surfaces = [bBot + 0.14], k, sy;
    for (k = 1; k <= 3; k++) {
      sy = bBot + k * (BH / 4);
      box(MAT.oak, cx, sy, cz, BD - 0.16, 0.1, BW - 0.24);             // recessed shelf
      surfaces.push(sy + 0.05);
    }

    // Books: a row standing on each surface. Coloured in the brand palette so the
    // case reads as full without becoming a rainbow.
    var bmats = [MAT.navy, MAT.walnut, MAT.slate, MAT.wains, MAT.cream, MAT.oak];
    var m = 0, si, zz, h, w;
    for (si = 0; si < surfaces.length; si++) {
      zz = cz - BW / 2 + 0.35;
      while (zz < cz + BW / 2 - 0.3) {
        w = rand(0.12, 0.24);
        h = rand(0.95, 1.5);
        box(bmats[(m++) % bmats.length], cx - s * 0.04, surfaces[si] + h / 2, zz + w / 2, BD - 0.28, h, w);
        zz += w + rand(0.0, 0.05);
      }
    }
  }

  function buildRoom(R, basePath) {
    var s = R.side, z = R.z;
    var xIn = s * HALL_X, xFar = s * (HALL_X + ROOM_D);
    var xMid = s * (HALL_X + ROOM_D / 2);
    var z0 = z - ROOM_H, z1 = z + ROOM_H;

    plane(MAT.floor, xMid, -HALL_Y, z, ROOM_D, 2 * ROOM_H, 'up');
    plane(MAT.ceil, xMid, HALL_Y, z, ROOM_D, 2 * ROOM_H, 'down');
    // All three room walls navy now (MAT.accent and the lifted MAT.wall are the
    // same navy) -- there is no longer a single accent wall; the whole room is navy.
    plane(MAT.accent, xFar, 0, z, 2 * ROOM_H, 2 * HALL_Y, s > 0 ? '-x' : '+x');
    plane(MAT.accent, xMid, 0, z0, ROOM_D, 2 * HALL_Y, '+z');
    plane(MAT.wall, xMid, 0, z1, ROOM_D, 2 * HALL_Y, '-z');

    // Navy wainscot to match the navy walls above it, so the whole wall reads as
    // one colour broken only by the white chair rail, baseboard and crown.
    trimRun('x', xFar, z0, z1, s > 0 ? -1 : 1, MAT.wainsNavy);
    trimRun('z', z0, xIn, xFar, 1, MAT.wainsNavy);
    trimRun('z', z1, xIn, xFar, -1, MAT.wainsNavy);

    // A picture rail and, flanking the art, two windows on the wall you stand and
    // face. A single flat plane of plaster is what made this read as a box with a
    // floor -- the mouldings are what say "room". Windows flank the art rather than
    // sit behind it: the picture is up to 6.4 wide and would swallow a centre
    // opening whole.
    //
    // Every room is daylit, so every room gets real windows here in place of blind
    // panels (panelYZ, kept for reference). The pane is only the visible source;
    // the light that actually reaches the room is the pair of points placed just
    // inside the openings below. Because the rooms alternate sides of the hall --
    // their far walls are ~52 units apart in x -- a room's window light never
    // meaningfully bleeds into its neighbour's, so each room owns its own daylight.
    windowYZ(xFar, 0.7, z - 5.1, 4.4, 2.9, s);
    windowYZ(xFar, 0.7, z + 5.1, 4.4, 2.9, s);
    box(MAT.trim, xFar - s * 0.05, 3.5, z, 0.1, 0.16, 2 * ROOM_H);   // picture rail
    // Brighter and longer-reaching than the first pass, with a gentler decay, so the
    // daylight actually FILLS the room instead of pooling on the far wall and leaving
    // the furniture to crush to black (the review's "bright window, dusk interior").
    var wi, winL;
    for (wi = -1; wi <= 1; wi += 2) {
      winL = new THREE.PointLight(0xd8e8ff, 3.6, 24, 1.8);   // cool daylight
      winL.position.set(xFar - s * 2.6, 0.9, z + wi * 5.1);   // just inside each opening
      scene.add(winL);
    }

    // NOTE: the doorway wall is NOT built here. buildHall() already walls this
    // side of the hallway everywhere except the openings, at this exact x -- the
    // room used to draw its own pieces on top, which put two coincident surfaces
    // at x = s*HALL_X and left them z-fighting. The room only dresses the opening.
    entrance(R);

    rugOnFloor(xMid + s * 1.5, z, 9, 7);

    var i, fz;
    switch (R.theme) {
      case 'living':
        shadowPad(s * 19, z, 5.6, 8.4); shadowPad(s * 15.5, z, 4, 5.4);
        box(MAT.navy, s * 19, -3.9, z, 3.4, 1.5, 6.2);             // sofa
        box(MAT.navy, s * 20.4, -2.8, z, 0.6, 2.2, 6.2);           // back
        for (i = -1; i <= 1; i += 2) { box(MAT.navy, s * 19, -2.9, z + i * 2.9, 3.4, 1.1, 0.5); }
        box(MAT.walnut, s * 15.5, -4.4, z, 2.2, 0.24, 3.6);        // coffee table
        for (i = 0; i < 4; i++) { box(MAT.walnut, s * (14.6 + (i % 2) * 1.8), -4.75, z + (i < 2 ? -1.4 : 1.4), 0.16, 0.7, 0.16); }
        floorLamp(s * 21.4, z - 4.4);   // brings its own stand
        break;
      case 'gallery':                                              // console + vases
        shadowPad(s * 21, z, 3.2, 8.6);
        box(MAT.walnut, s * 21, -3.6, z, 1.1, 2.2, 7);   // console top at y = -2.5
        for (i = -1; i <= 1; i++) { cyl(MAT.glass, s * 21, -2.1, z + i * 2.2, 0.26, 0.8); }
        // The console runs z-3.5..z+3.5; the lamp was at z+4.6, a full unit off the
        // end and floating. Brought back onto the top, near the far end, clear of
        // the vase at z+2.2. (Daylight is added for every room up in buildRoom.)
        lamp(s * 21, -1.4, z + 3.0, -2.5);   // console top, near the far end
        break;
      case 'study':
        shadowPad(s * 19, z, 4.4, 6.6); shadowPad(s * 21.4, z - 4.6, 2.6, 5);
        box(MAT.oakWood, s * 19, -3.7, z, 2.6, 0.26, 5);          // desk: oak, to match the floor
        for (i = 0; i < 4; i++) { box(MAT.oakWood, s * (18 + (i % 2) * 2), -4.4, z + (i < 2 ? -2.2 : 2.2), 0.2, 1.4, 0.2); }
        box(MAT.slate, s * 19, -3.2, z, 1.4, 0.9, 2.2);            // chair back
        bookcase(s, s * 21.4, z - 4.6);
        lamp(s * 19.6, -2.7, z + 2, -3.57);   // desk top
        break;
      case 'entry':
        // The front door USED to stand here, dead centre of the far wall -- which
        // is exactly where hangArt() hangs the scene plate. The door is 3.6 wide,
        // the picture 6.4, and the door sits nearer the camera, so it covered the
        // middle and left the photograph showing as two slivers down either side.
        // It read as two black rectangles because it WAS one picture with a door
        // in front of it.
        //
        // The door is gone rather than moved: this room already has a real
        // entrance -- the hallway doorway with both leaves standing open, built by
        // entrance() -- so "open the door to San Angelo living" is still literally
        // what you just walked through to get here. A second door on the far wall
        // was always redundant, and it was costing us the picture.
        shadowPad(s * 17, z + 4.4, 3, 4);
        shadowPad(s * 21, z, 2.6, 7);
        box(MAT.walnut, s * 21, -4.05, z, 1.1, 1.9, 6);            // console under the art
        box(MAT.brass, s * 20.4, -3.6, z - 1.4, 0.06, 0.5, 0.06);  // a pair of candlesticks
        box(MAT.brass, s * 20.4, -3.5, z + 1.4, 0.06, 0.7, 0.06);
        box(MAT.walnut, s * 17, -4.4, z + 4.4, 1.2, 1.2, 2.2);     // bench
        shadowPad(s * 21, z - 4.6, 2, 3);
        box(MAT.walnut, s * 21, -4.1, z - 4.6, 1.1, 1.8, 2.4);     // top at y = -3.2
        lamp(s * 21, -2.2, z - 4.6, -3.2);
        break;
      case 'dining':
        shadowPad(s * 18, z, 6.6, 9.4);
        box(MAT.walnut, s * 18, -3.4, z, 3.4, 0.26, 7);            // table
        for (i = 0; i < 4; i++) { box(MAT.walnut, s * (16.8 + (i % 2) * 2.4), -4.2, z + (i < 2 ? -2.8 : 2.8), 0.22, 1.6, 0.22); }
        for (i = -1; i <= 1; i += 2) {                             // chairs
          box(MAT.linen, s * (18 + i * 2.6), -4.1, z, 0.9, 1.1, 1.1);
          box(MAT.linen, s * (18 + i * 3.05), -3.1, z, 0.16, 1.9, 1.1);
        }

        lamp(s * 18, 1.4, z, null, true);   // pendant: stem drawn to the real ceiling
        break;
      case 'kitchen':
        shadowPad(s * 20.6, z, 3.4, 10); shadowPad(s * 16.6, z, 4, 6.6);
        box(MAT.trim, s * 20.6, -3.4, z, 2.2, 3.2, 9);             // run of cabinets
        box(MAT.slate, s * 20.6, -1.75, z, 2.4, 0.18, 9.2);        // stone counter
        box(MAT.oak, s * 16.6, -3.9, z, 2.6, 2.2, 5);              // island
        box(MAT.slate, s * 16.6, -2.75, z, 2.9, 0.2, 5.3);

        // Raised from y=-0.2 to y=1.7: the pendants hung on a ~5-unit stem with
        // the shades down at mid-height, which read as too low over the island.
        // Higher up, the drop is short and they sit where kitchen pendants belong.
        for (i = -1; i <= 1; i += 2) { lamp(s * 16.6, 1.7, z + i * 1.4, null, true); }
        break;
      case 'hearth':                                               // fireplace
        shadowPad(s * 21, z, 3, 7.6); shadowPad(s * 16.6, z - 2.2, 4, 4); shadowPad(s * 16.6, z + 2.2, 4, 4);
        box(MAT.trim, s * 21, -1.6, z, 1.2, 6.8, 6);
        box(MAT.slate, s * 20.6, -3.4, z, 0.6, 3.2, 3.4);
        box(MAT.shade, s * 20.4, -3.9, z, 0.2, 1.4, 2.6);          // the fire itself
        box(MAT.walnut, s * 21, 1.9, z, 1.6, 0.34, 6.6);           // mantel
        box(MAT.navy, s * 16.6, -3.9, z - 2.2, 2.6, 1.5, 2.6);     // armchairs
        box(MAT.navy, s * 16.6, -3.9, z + 2.2, 2.6, 1.5, 2.6);
        shadowPad(s * 18.4, z, 2, 2);
        box(MAT.walnut, s * 18.4, -4.2, z, 1.2, 1.6, 1.2);         // top at y = -3.4
        lamp(s * 18.4, -2.4, z, -3.4);
        break;
      default:                                                     // foyer-ish
        box(MAT.walnut, s * 21, -3.6, z, 1, 2.2, 6);
        lamp(s * 21, -1.4, z, -2.5);   // console top
        break;
    }
  }

  function buildHall() {
    var zMid = (HALL_Z0 + HALL_Z1) / 2, zLen = HALL_Z0 - HALL_Z1, i, z;

    plane(MAT.hallFloor, 0, -HALL_Y, zMid, 2 * HALL_X, zLen, 'up');
    plane(MAT.ceil, 0, HALL_Y, zMid, 2 * HALL_X, zLen, 'down');
    rugOnFloor(0, zMid, 3.4, zLen - 4);   // runner
    plane(MAT.wall, 0, 0, HALL_Z1, 2 * HALL_X, 2 * HALL_Y, '+z');   // far end
    // The crown returns across the back wall, tying the two side runs together at
    // the end of the hall instead of dying into the corners -- and it caps the wall
    // the logo hangs on. Same profile as trimRun's crown, run along x and stood
    // proud of the wall into the hall.
    box(MAT.trim, 0, HALL_Y - 0.16, HALL_Z1 + 0.16, 2 * HALL_X, 0.32, 0.2);
    // The Coldwell Banker lockup, centred on the back wall as the corridor's focal
    // terminus: the official white stacked monogram (BRAND.md forbids recolouring
    // it), hung as a plaque via the same depth-tested, proximity-lit path as the
    // hearth mark. On the live same-origin page it renders; under file:// the SVG
    // taints the canvas and bakeMonogram swallows it (see the harness header) --
    // exactly as the existing mark already behaves.
    bakeMonogram(_monoStackUrl, 0, 0.7, HALL_Z1 + 0.22, 3.8, 0);

    // The hallway walls exist only where a room does NOT open. Rather than
    // punching holes, each side is drawn as the gaps between its doorways --
    // which also means a side with no rooms is simply one long wall.
    var sides = [-1, 1], sIdx, doors, prev, k;
    for (sIdx = 0; sIdx < 2; sIdx++) {
      var s = sides[sIdx];
      doors = [];
      for (i = 0; i < ROOM.length; i++) { if (ROOM[i].side === s) { doors.push(ROOM[i].z); } }
      doors.sort(function (a, b) { return b - a; });
      prev = HALL_Z0;
      for (k = 0; k < doors.length; k++) {
        z = doors[k];
        if (prev - (z + 3) > 0.1) {
          // A BOX, not a plane. A wall with no thickness gives its doorways no
          // reveal -- you see a zero-width edge and walk through a hole in paper.
          // The 0.5 of depth here is the entire reason entrance() has something
          // to line.
          box(MAT.wall, s * HALL_X, 0, (prev + z + 3) / 2, WALL_T, 2 * HALL_Y, prev - (z + 3));
          trimRun('x', s * HALL_X - s * (WALL_T / 2), z + 3, prev, s > 0 ? -1 : 1, MAT.wainsNavy);
        }
        // Over-door panel, so the opening reads as a doorway and not a missing wall.
        box(MAT.wall, s * HALL_X, 4.3, z, WALL_T, 1.4, 6);
        // Crown continues unbroken across the opening. The wall segments on either
        // side each carry their own crown (via trimRun), but nothing bridged the
        // 6-wide doorway gap until here -- same profile, height and depth as the
        // segment crown, so the run reads as continuous the length of the hall.
        box(MAT.trim, s * HALL_X - s * (WALL_T / 2) + 0.06 * (s > 0 ? -1 : 1) * 1.6, HALL_Y - 0.16, z, 0.2, 0.32, 6);
        prev = z - 3;
      }
      if (prev - HALL_Z1 > 0.1) {
        box(MAT.wall, s * HALL_X, 0, (prev + HALL_Z1) / 2, WALL_T, 2 * HALL_Y, prev - HALL_Z1);
        trimRun('x', s * HALL_X - s * (WALL_T / 2), HALL_Z1, prev, s > 0 ? -1 : 1, MAT.wainsNavy);
      }
    }

    // Recessed downlights flush in the ceiling, down the centre of the hall --
    // replacing the wall sconces. A hallway is lit from above; wall fittings this
    // regularly spaced read more like a hotel corridor than a home.
    for (z = HALL_Z0 - 3; z > HALL_Z1 + 3; z -= 6) {
      ceilingLight(0, z);
    }
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
        // mark (BRAND.md 5/9 forbid altering the lockup).
        //
        // Home 8 blended it additively so it bloomed across the navy field. On a
        // plaster wall that would read as a projection, not an object -- and with
        // depthTest off it would shine straight through the walls of the rooms
        // between you and it. Here it is a plaque: normally blended, depth-tested,
        // hung on the hearth-room wall.
        var tex = new THREE.CanvasTexture(c); tex.needsUpdate = true;
        var mat = new THREE.MeshBasicMaterial({ map: tex, transparent: true, opacity: 0, depthTest: true, depthWrite: false });
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
    houseGroup = new THREE.Group();
    corridorGroup = houseGroup;   // the rest of the engine still calls it that
    _lampPts.length = 0;
    buildMaterials();
    buildHall();
    var i;
    for (i = 0; i < ROOM.length; i++) { if (ROOM[i].side !== 0) { buildRoom(ROOM[i], _artBase); } }
    for (i = 0; i < ROOM.length; i++) { if (ROOM[i].side !== 0) { hangArt(ROOM[i], _artBase); } }

    // Lighting. The count is FIXED once, here at build time -- Three rebuilds every
    // material's shader when the light count changes, so nothing may add or remove a
    // light once the walk is running. (One roving PointLight still follows the
    // nearest lamp rather than one-light-per-lamp, for the same reason; the per-room
    // window daylights, added in buildRoom above, are likewise all placed before the
    // first frame.)
    //
    // The house is DAYLIT now: every room has real windows and a cool point just
    // inside each, so the fill can come up from the old dusk level. It is still
    // deliberately cool -- daylight is -- and still shy of flat: the first pass ran
    // ambient at 0.62 and the house came out uniformly beige, every surface the same
    // value, which is what makes cheap 3D look cheap. The warm lamps still pool
    // against the cool daylight; that FILL-cool / LAMP-warm split is what keeps a
    // navy-and-white house from going either clinical or muddy. A warm ambient over
    // Icy Blue walls renders them oatmeal, which is how the brand colour quietly
    // disappeared once before -- so the fill stays blue.
    scene.add(new THREE.AmbientLight(0xdfe9f7, 0.56));
    // Ground colour is the oak bouncing back up -- warm, and not black: light
    // hitting a timber floor returns onto everything above it. Setting it
    // near-black (as this first did) is physically wrong and makes the underside of
    // every object read as a hole.
    scene.add(new THREE.HemisphereLight(0xeef4fd, 0x6b4a2a, 0.70));
    // The sun: one near-neutral directional from high and to the front, for the
    // overall daytime gradient across every surface. Neutral rather than the old
    // cool blue -- a blue sun over navy walls only deepened the dusk read the review
    // flagged; a white sun keeps the daylight honest without warming the navy to
    // mud. It cannot favour the far wall of every room (rooms alternate sides), so
    // it stays a near-overhead wash and lets the per-room window points do the
    // actual "light coming IN from the window".
    var sun = new THREE.DirectionalLight(0xf5f2ea, 0.50);
    sun.position.set(8, 34, 10);   // direction = origin - position: down, slightly -x/-z
    scene.add(sun);
    _lampLight = new THREE.PointLight(LAMP_HEX, 3.2, 26, 2);
    scene.add(_lampLight);
    // A second light rides just behind the camera. Without it you cast no presence
    // into the room you walk into and it reads as a diorama you are outside of.
    _handLight = new THREE.PointLight(0xffe3c0, 1.1, 20, 2);
    scene.add(_handLight);

    // Fog still hides the far end of the hallway and keeps the house feeling deep
    // rather than finite, but eased for daytime: the near plane is pushed back off
    // the room content so daylit far walls are not muddied toward the depth colour,
    // and the colour itself is lifted a little off pure Midnight so the fade reads
    // as cool haze rather than a wall of black at the end of the corridor.
    scene.fog = new THREE.Fog(0x14243d, 15, 60);

    // The brand mark, hung as a small plaque rather than blazing across the hall.
    bakeMonogram(_monoStackUrl, ROOM[7].side * (HALL_X + ROOM_D - 0.34), -2.6, ROOM[7].z + 4.6, 1.6, yawFor(ROOM[7].side));

    scene.add(houseGroup);
    _corridorReady = true;
  }

  function updateLights() {
    if (!_lampLight) { return; }
    var best = null, bd = 1e9, i, d;
    for (i = 0; i < _lampPts.length; i++) {
      d = camera.position.distanceToSquared(_lampPts[i]);
      if (d < bd) { bd = d; best = _lampPts[i]; }
    }
    if (best) { _lampLight.position.copy(best); }
    _handLight.position.set(
      camera.position.x - fwdX(_yawT) * 1.5,
      camera.position.y + 1.2,
      camera.position.z - fwdZ(_yawT) * 1.5
    );
  }

  function updateSignage() {
    for (var i = 0; i < signage.length; i++) {
      var s = signage[i];
      var on = clamp01(1 - (camera.position.distanceTo(s.pos) - 10) / 14);
      // Recede while a page is being read -- same instinct as damping the
      // parallax: the house gets out of the way while you read.
      s.mat.opacity = on * (1 - _reading * 0.72);
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
  function roomPose(R, out) {
    out.x = R.side * ROOM_IN; out.y = 0; out.z = R.z; out.yaw = yawFor(R.side); out.p = R.p;
    return out;
  }

  function poseAt(g, out) {
    var n = ROOM.length;
    g = clamp(g, 0, n - 1);
    if (n < 2) { return roomPose(ROOM[0], out); }

    var i = Math.floor(g);
    if (i > n - 2) { i = n - 2; }
    var t = g - i;                                   // 0..1 across segment i -> i+1

    if (t <= U_DWELL) { return roomPose(ROOM[i], out); }          // standing, room i
    if (t >= 1 - U_DWELL) { return roomPose(ROOM[i + 1], out); }  // standing, room i+1

    // ---- the arc between two rooms -----------------------------------------
    // Five overlapping ramps, not four sequential moves. The overlaps are the
    // whole point: you unwind your shoulders while still backing out (xOut/yawOut
    // overlap), the hallway glide starts before the turn finishes, and you begin
    // turning toward the next door while still gliding, then step through it
    // (zT/yawIn/xIn overlap). Read the windows below as "what is happening at
    // once", not "what happens next".
    //
    // Continuity is free: at w=0 every ramp reads exactly room i's pose and at
    // w=1 exactly room i+1's, and band() has zero velocity AND acceleration at
    // both ends -- so the arc leaves and rejoins the flat dwells without a seam.
    var A = ROOM[i], B = ROOM[i + 1];
    var w = (t - U_DWELL) / (1 - 2 * U_DWELL);

    // The hallway glide normally waits for you to clear the doorway (w=0.18) and
    // stops before you step into the next one (w=0.82). A side:0 room has no
    // doorway to clear, so those waits become dead scroll where NOTHING moves --
    // literally zero camera speed. That is exactly what happened leaving the
    // threshold: ~14% of the section where you scrolled and the world sat still.
    // With no room to back out of, the glide simply starts at once.
    var zT = band(w, A.side === 0 ? 0.00 : 0.18, B.side === 0 ? 1.00 : 0.82);

    // Window WIDTH is pace: each ramp covers a fixed distance (8 units, or a 90
    // degree sweep), so a narrow window means that leg happens fast. They are
    // sized to keep the legs at comparable speed. The first cut turned inside 0.28
    // while the glide got 0.60, so the camera whipped through 90 degrees, crawled
    // down the hallway, then whipped again -- an 8x speed swing per room, which
    // reads as exactly the unevenness it was supposed to remove.
    //
    // A side:0 neighbour has no doorway, so its back-out (or step-in) leg does not
    // exist and that window is free. Spend it on the turn: starting earlier makes
    // the sweep wider and gentler, and -- the reason this is not cosmetic -- keeps
    // it overlapping the glide. Leaving the threshold there is no back-out to
    // carry the early motion, so with the default 0.58 start the glide had already
    // decayed before the turn began and the camera all but stopped between them.
    var xOut = 1 - band(w, 0.00, 0.30);                          // back out through the doorway
    var yawOut = 1 - band(w, 0.06, B.side === 0 ? 0.66 : 0.42);  // ...turning as you go
    var yawIn = band(w, A.side === 0 ? 0.34 : 0.58, 0.94);       // ...turning toward the next door early
    var xIn = band(w, 0.70, 1.00);                               // step in

    out.x = A.side * ROOM_IN * xOut + B.side * ROOM_IN * xIn;
    out.y = 0;
    out.z = A.z + (B.z - A.z) * zT;
    out.yaw = yawFor(A.side) * yawOut + yawFor(B.side) * yawIn;
    out.p = A.p + (B.p - A.p) * w;
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
    // Kept only as the adaptive-quality gate ("are we actually in the house yet").
    // In Home 8 this also drove the dust condensing onto the wireframe; here the
    // house is simply built, so there is nothing to assemble.
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
   * strength mid-approach. Images inside .cb9-page__body only start fetching when
   * content-visibility releases them, so on a page like Communities six webp files
   * land at once, several frames apart -- unmanaged, that is six separate pops
   * over the top of a page that is already moving.
   *
   * `error` marks it loaded too: a 404 should leave a gap, not a permanently
   * invisible element that still occupies its grid cell.
   */
  function markLoaded(im) { im.classList.add('is-loaded'); }
  function onImgIn() { markLoaded(this); }
  /**
   * Fade each image up as it decodes -- and NEVER leave one invisible.
   *
   * The image starts at opacity:0 and only becomes visible when .is-loaded is
   * added. If that one signal is missed, the image stays at opacity:0 forever --
   * which is exactly the blog-image bug in the Connect room. The miss is a classic
   * CACHED-image race: on a warm cache (e.g. after reloading the page a few times)
   * a body image, released by content-visibility, can finish loading in the gap
   * between the `im.complete` check below reading false and addEventListener
   * running, so its `load` fires before the listener exists. is-loaded is never
   * set; the image never appears. Intermittent, cache-dependent, invisible in
   * capture mode (which forces is-loaded) -- everything the report describes.
   *
   * Four independent ways an image ends up marked, so no single miss can strand it:
   *   1. already complete when we look;
   *   2. the load/error listener;
   *   3. a re-check AFTER attaching, which closes the cache race;
   *   4. decode() + a 4s safety net, so even a dropped event cannot leave it dark.
   * The guard is is-loaded itself, not a one-shot flag, so re-scanning is cheap
   * and idempotent (see setState -> 'reading').
   */
  function watchImages(root) {
    var imgs = root.querySelectorAll('img'), i, im;
    for (i = 0; i < imgs.length; i++) {
      im = imgs[i];
      if (im.classList.contains('is-loaded')) { continue; }
      // Capture wants the resting state: a CSS transition does not advance under
      // --virtual-time-budget, so a fading image would photograph at opacity 0.
      if (_capture || (im.complete && im.naturalWidth > 0)) { markLoaded(im); continue; }
      if (im.__cb9w) { continue; }
      im.__cb9w = true;
      im.addEventListener('load', onImgIn);
      im.addEventListener('error', onImgIn);   // a 404 leaves a gap, not a dark cell
      // The image can finish between the complete-check above and here; re-check.
      if (im.complete && im.naturalWidth > 0) { markLoaded(im); continue; }
      if (im.decode) { im.decode().then((function (x) { return function () { markLoaded(x); }; })(im), function () {}); }
      (function (x) { setTimeout(function () { markLoaded(x); }, 4000); })(im);
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
  // The transform lives on .cb9-card and the idle float on .cb9-card__inner, so
  // the one-shot reveal and the infinite float never fight over `transform`.
  function staggerFor(i) { return clamp(i * 90, 0, 420); }

  function formCards(p) {
    if (p.cardsIn) { return; }
    p.cardsIn = true;
    var cards = p.el.querySelectorAll('[data-cb9-card]');
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
      isFrame = el.hasAttribute('data-cb9-frame');
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
    var cards = p.el.querySelectorAll('[data-cb9-card]');
    var i, delay, el, isFrame;
    for (i = 0; i < cards.length; i++) {
      el = cards[i];
      delay = staggerFor(i);
      isFrame = el.hasAttribute('data-cb9-frame');
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
    var cards = p.el.querySelectorAll('[data-cb9-card]');
    for (var i = 0; i < cards.length; i++) {
      cards[i].classList.remove('is-formed');
      cards[i].classList.remove('is-forming');
      cards[i].classList.remove('is-crumbling');
      cards[i].style.transitionDelay = '';
    }
  }

  function countUp(p) {
    var nums = p.el.querySelectorAll('.cb9-stat__num[data-count]');
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
   * Per-card idle float, Home 7's signature. It lives on .cb9-card__inner while
   * form/crumble owns .cb9-card's transform -- the same two-element split Home 7
   * used, for the same reason.
   *
   * The bob composes multiplicatively with the page's projected scale, so a 12px
   * float at s=0.75 lands as 9px on screen: a page further down the corridor
   * wobbles less, for free.
   *
   * CSS keyframes (cb-home8.css) are the no-Motion fallback and look near enough
   * identical; html.cb9-motion switches them off so the two never double up.
   */
  function startCardFloats(p) {
    if (p.floatsOn || _capture) { return; }
    p.floatsOn = true;
    if (!M || !M.animate) { return; }   // CSS keyframes carry it
    var inners = p.el.querySelectorAll('[data-cb9-card] > .cb9-card__inner');
    for (var i = 0; i < inners.length; i++) {
      // Frames hold live content (MLS grid, rotator) -- Home 7 exempts them from
      // the float so a scrollable table is not drifting under the cursor.
      if (inners[i].parentNode.hasAttribute('data-cb9-frame')) { continue; }
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
    p.el.setAttribute('data-cb9-state', next);

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
      // Re-scan images whenever a page becomes the one being read. watchImages is
      // idempotent (guarded on is-loaded), so this is a cheap safety net that
      // catches any image the first reveal's listeners missed -- the Connect
      // room's blog thumbnails, whose fade could otherwise stick at opacity 0.
      if (next === 'reading' && p.bodyOn) { watchImages(p.el); }
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

    if (_corridorReady) { updateLights(); updateSignage(); }
    updateBursts(dt);

    // Pages MUST project from the same camera state, in the same frame, as the
    // render. One frame of slip is 10-20px of visible offset against the room.
    // Never a second rAF.
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
    // Do not re-raise the cap if adaptive quality already dropped it to 1.0 --
    // a resize would otherwise undo the very mitigation that made it smooth.
    dpr = Math.min(window.devicePixelRatio || 1, _degraded ? 1.0 : 2);
    renderer.setPixelRatio(dpr); renderer.setSize(vw, vh, true);
    camera.aspect = vw / vh; camera.updateProjectionMatrix();
    material.uniforms.uPixelRatio.value = dpr;
    computeExtents(); sizePages(); cacheRects();
  }

  function observeNav() {
    var dots = document.querySelectorAll('.cb9-nav__dot');
    for (var k = 0; k < dots.length; k++) {
      dots[k].addEventListener('click', function () {
        var t = document.querySelector('[data-cb9-section="' + this.getAttribute('data-cb9-to') + '"]');
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
      _dots[d].classList.toggle('is-active', parseInt(_dots[d].getAttribute('data-cb9-to'), 10) === act);
    }
  }

  function collectPages() {
    var els = document.querySelectorAll('[data-cb9-page]');
    var i, el, sec;
    for (i = 0; i < els.length; i++) {
      el = els[i];
      sec = document.querySelector('[data-cb9-section="' + el.getAttribute('data-cb9-page') + '"]');
      if (!sec) { continue; }
      _pages.push({
        i: i,
        el: el,
        sec: sec,
        skin: el.querySelector('.cb9-page__skin') || el,
        scroll: el.querySelector('.cb9-page__scroll'),
        body: el.querySelector('.cb9-page__body'),
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
    // Directory holding Home 2's scene plates, which hang as framed art. Same
    // `basePath` contract Home 2's own loader uses.
    _artBase = opts.basePath || '';
    // Material samples sit alongside the plates: .../assets/images/textures/. Take
    // an explicit texBase if given, else derive it from the plate base so a single
    // basePath keeps working. Empty -> photoTex() returns null -> procedural
    // fallback, so a missing folder degrades rather than breaks.
    _texBase = opts.texBase || (_artBase ? _artBase.replace(/webgl\/?$/, 'textures/') : '');

    var sel = opts.canvas || '#cb9-canvas';
    canvas = (typeof sel === 'string') ? document.querySelector(sel) : sel;
    if (!canvas) { return false; }
    // antialias ON, unlike Home 8. Home 8 can skip it: additive lines and points
    // have no silhouettes to alias. A house is nothing but silhouettes -- every
    // door casing, mantel and skirting is a hard straight edge, and unantialiased
    // they crawl as the camera turns.
    try { renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true, powerPreference: 'high-performance' }); }
    catch (e) { renderer = null; }
    if (!renderer) { return false; }

    try {
      _v = new THREE.Vector3(); _dir = new THREE.Vector3(); _out = new THREE.Vector3();
      _pv = new THREE.Vector3(); _fwd = new THREE.Vector3(); _mwi = new THREE.Matrix4();
      renderer.setClearColor(0x000000, 0);
      // Neutral (linear) tone mapping, NOT ACES. ACES rolls the highlights off like
      // a camera, but it also crushes the shadows -- and that toe is what turned the
      // walls and floor black everywhere the light did not directly land, no matter
      // how the colour or the fill was tuned. This is a walkthrough whose entire job
      // is to show the colours of the house, so keeping the shadows OPEN and the
      // colours true beats a filmic highlight roll-off. Exposure is the master
      // brightness knob now that no curve is compressing the range.
      if (THREE.LinearToneMapping) { renderer.toneMapping = THREE.LinearToneMapping; }
      renderer.toneMappingExposure = 1.0;
      vw = document.documentElement.clientWidth || 1;
      vh = document.documentElement.clientHeight || 1;
      // 1.25 -> 2. Home 8's cap is fine for glowing wireframe, where softness reads
      // as bloom; here every straight edge in the room is a hard edge, and 1.25 on a
      // 2x display renders them visibly stepped. The adaptive-quality pass still
      // drops this to 1.0 if frame time suffers, so the cost is bounded.
      dpr = Math.min(window.devicePixelRatio || 1, 2);

      scene = new THREE.Scene();
      camera = new THREE.PerspectiveCamera(FOV, vw / vh, 0.1, 160);
      camera.position.set(0, 0, CAMZ);
      computeExtents();

      pos = new Float32Array(COUNT * 3); color = new Float32Array(COUNT * 3);
      size = new Float32Array(COUNT); alpha = new Float32Array(COUNT); phase = new Float32Array(COUNT);
      seedAmbient(); initBurstBuffers();

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
        vertexShader: VERT, fragmentShader: FRAG, transparent: true, blending: THREE.AdditiveBlending,
        // depthTest MUST be on now. Home 8 could disable it because its world was
        // additive wireframe with nothing to occlude anything; here the house is
        // solid, and untested motes would hang in front of every wall -- dust
        // floating through the plaster, in every room at once.
        depthTest: true, depthWrite: false
      });
      points = new THREE.Points(geom, material); points.frustumCulled = false; scene.add(points);

      renderer.setPixelRatio(dpr); renderer.setSize(vw, vh, true);

      collectPages();
      _act = document.querySelector('.cb9-scenes') || document.body;
      parseCapture();

      // Only now does the flat Home 2 layout become the floating one.
      document.documentElement.classList.add('cb9-on');
      // Kills the image fade transitions -- see watchImages().
      if (_capture) { document.documentElement.classList.add('cb9-capture'); }
      // Tells the CSS that Motion owns the card reveal + the idle float, so it
      // stands down (drops the transition, drops the keyframe fallback).
      if (M && M.animate) { document.documentElement.classList.add('cb9-motion'); }

      sizePages(); cacheRects();

      try { buildCorridor(); }
      catch (eo) { _corridorReady = false; if (window.console) { console.warn('[cb9] corridor build failed; running nebula+pages only.', eo); } }

      observeNav();
      // Plates live outside .cb9-page__body, so they start fetching immediately --
      // watch them now. Body images are picked up in revealBody().
      for (var pi = 0; pi < _pages.length; pi++) { watchImages(_pages[pi].el); }
      // Only hide the native cursor if the custom one actually started.
      //
      // CBCursor.init() RETURNS FALSE when it bails (reduced-motion, coarse
      // pointer, no body) and can also simply fail to load -- but the CSS hid the
      // native cursor regardless, so any of those left you with NO cursor at all.
      // Links stayed clickable the whole time; there was just nothing on screen to
      // tell you so, which is indistinguishable from a dead page. Same principle as
      // the image fade: an enhancement must never remove the thing it enhances.
      if (window.CBCursor && window.CBCursor.init && !_capture) {
        try {
          if (window.CBCursor.init() === true) { document.documentElement.classList.add('cb9-cursor'); }
        } catch (e2) {}
      }

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
              note.className = 'cb9-watch__note';
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
          // Wait for the art to decode, not just for a frame count. Bounded so a
          // missing file cannot hang the shot forever -- it just photographs the
          // empty frame, which is the honest result.
          if (_captureFrames < 3 || (_pendingTex > 0 && _captureFrames < 240)) { raf(shot); }
          else { window.__cbReady = true; document.title = 'CB_READY'; }
        };
        raf(shot);
      } else {
        start();
      }
      initialized = true; return true;
    } catch (err) {
      document.documentElement.classList.remove('cb9-on');
      document.documentElement.classList.remove('cb9-motion');
      stop();
      if (renderer && renderer.dispose) { try { renderer.dispose(); } catch (e3) {} }
      if (window.console) { console.warn('[cb9] init failed; using flat fallback.', err); }
      return false;
    }
  }

  window.CBHome9 = { init: init };

})(window, document);









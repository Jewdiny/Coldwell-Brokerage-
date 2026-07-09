/* =========================================================================
   office.js — Coldwell Banker "Virtual Office" homepage (Home 5 preview)

   Extends the Home 4 fusion engine (Three.js dust-nebula + glass info-cards +
   custom cursor) with a scroll-driven VIRTUAL OFFICE walkthrough. The office is
   literally made of the dust: a third "STRUCT" particle pool lives in the SAME
   single Points draw call and, as you scroll the office act, condenses from
   scattered motes onto the edges of stylized office fixtures (portal, reception,
   listings wall, legacy arch, communities table, valuation desk, story wall),
   then dissolves back into the nebula. One merged additive LINE layer sharpens
   the fixture edges; a couple of baked CB-monogram quads bloom as you arrive.
   The camera dollies forward through the office, anchored to each section's own
   getBoundingClientRect (robust to unequal live-content heights).

   Smoothness: ONE Points draw call (+1 line layer, +<=2 signage quads); ambient
   + struct are static (GPU-twinkled), struct positions update ONLY while the
   office assembles/dissolves (dirty flag); cards never use backdrop-filter.

   Progressive enhancement: init() adds html.cb-office-on only after WebGL starts;
   if the office build fails the nebula+cards still run (degrades to fusion); with
   no WebGL / reduced-motion the static glass-card fallback (cb-office.css) shows.
   Exposes window.CBOffice.init(opts). Requires window.THREE.
   ========================================================================= */
(function (window, document) {
  'use strict';

  if (window.CBOffice) { return; }

  var THREE = window.THREE;
  var raf = window.requestAnimationFrame || function (cb) { return setTimeout(function () { cb(now()); }, 16); };
  var caf = window.cancelAnimationFrame || window.clearTimeout;
  function now() { return (window.performance && performance.now) ? performance.now() : Date.now(); }

  // ---- config -------------------------------------------------------------
  var AMB = 1600;            // ambient (static) dust
  var STRUCT = 1500;         // office-structure pool (condenses onto fixtures)
  var BURST = 1000;          // reserved card-burst slots
  var COUNT = AMB + STRUCT + BURST;
  var B0 = AMB + STRUCT;     // first burst index
  var CAMZ = 22;             // nebula camera distance
  var FOV = 58;
  var TAU = Math.PI * 2;

  var PAL = [
    [1.00, 1.00, 1.00], [1.00, 1.00, 1.00],
    [0.78, 0.86, 0.96], [0.72, 0.81, 0.92],
    [0.36, 0.56, 0.87], [0.16, 0.45, 1.00]
  ];
  // structural colors (0..1)
  var C_BLUE = [0.13, 0.27, 0.62], C_TIDE = [0.72, 0.81, 0.92], C_BRIGHT = [0.20, 0.48, 1.00];

  // Per-section camera waypoints {x,y,z,p=parallaxScale}. Camera looks toward
  // (z-12) with NO rotation — "looking around" is faked via x/y offsets.
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

  // ---- state --------------------------------------------------------------
  var canvas, renderer, scene, camera, geom, material, points;
  var pos, color, size, alpha, phase;
  var posAttr, alpAttr;

  // struct pool (parallel arrays, index 0..STRUCT-1 -> particle AMB+i)
  var sHx = new Float32Array(STRUCT), sHy = new Float32Array(STRUCT), sHz = new Float32Array(STRUCT);
  var sRx = new Float32Array(STRUCT), sRy = new Float32Array(STRUCT), sRz = new Float32Array(STRUCT);
  var sStag = new Float32Array(STRUCT), sBase = new Float32Array(STRUCT);

  // burst pool (index 0..BURST-1 -> particle B0+i)
  var bMode = new Float32Array(BURST), bAge = new Float32Array(BURST), bLife = new Float32Array(BURST), bA0 = new Float32Array(BURST);
  var bsx = new Float32Array(BURST), bsy = new Float32Array(BURST), bsz = new Float32Array(BURST);
  var btx = new Float32Array(BURST), bty = new Float32Array(BURST), btz = new Float32Array(BURST);
  var bvx = new Float32Array(BURST), bvy = new Float32Array(BURST), bvz = new Float32Array(BURST);
  var bPtr = 0;

  // office line layer + signage
  var officeGroup = null, lineMat = null, _officeReady = false;
  var signage = [];   // {mesh, mat, z}

  var vw = 0, vh = 0, dpr = 1, halfW = 0, halfH = 0;
  var rafId = null, lastT = 0, uTime = 0, started = false, initialized = false;
  var mouseNX = 0, mouseNY = 0, camX = 0, camY = 0, scrollFrac = 0;
  var oX = 0, oY = 0, oZ = CAMZ, oTX = 0, oTY = 0, oTZ = CAMZ, oP = 1;
  var officeAssembly = 0, _structDirty = false, _camDirty = true;
  var _sections = [], _act = null;
  var _fpsAcc = 0, _fpsN = 0, _degraded = false;
  var _v = null, _dir = null, _out = null;
  var _monoUrl = '', _monoStackUrl = '';

  function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }
  function clamp01(v) { return v < 0 ? 0 : (v > 1 ? 1 : v); }
  function rand(a, b) { return a + Math.random() * (b - a); }
  function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }
  function smooth(t) { return t * t * (3 - 2 * t); }
  function band(x, a, b) { return smooth(clamp01((x - a) / (b - a))); }

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

  // ---- line layer shaders -------------------------------------------------
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
  }
  function seedAmbient() {
    var i, c;
    for (i = 0; i < AMB; i++) {
      pos[i * 3] = rand(-halfW * 1.3, halfW * 1.3);
      pos[i * 3 + 1] = rand(-halfH * 1.7, halfH * 1.7);
      pos[i * 3 + 2] = (Math.random() < 0.42) ? rand(-46, 6) : rand(-14, 6); // fill the corridor
      c = PAL[(Math.random() < 0.16) ? (2 + (Math.random() * 4 | 0)) : (Math.random() < 0.5 ? 0 : 2)];
      color[i * 3] = c[0]; color[i * 3 + 1] = c[1]; color[i * 3 + 2] = c[2];
      size[i] = rand(0.7, 2.1); alpha[i] = rand(0.35, 0.9); phase[i] = Math.random() * TAU;
    }
  }
  function parkStruct() {  // safe default before office build
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

  // ---- office geometry ----------------------------------------------------
  var EDG = []; // {a:[x,y,z], b:[x,y,z], c:[r,g,b], g:0|1}
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
  function quadXZ(cx, y, cz, w, d, c, g) { // horizontal rectangle outline (table top)
    var x0 = cx - w / 2, x1 = cx + w / 2, z0 = cz - d / 2, z1 = cz + d / 2;
    E(x0, y, z0, x1, y, z0, c, g); E(x1, y, z0, x1, y, z1, c, g); E(x1, y, z1, x0, y, z1, c, g); E(x0, y, z1, x0, y, z0, c, g);
  }
  function arcE(cx, cy, cz, r, a0, a1, segs, c, g) {
    var prev = null, i, a, x, y;
    for (i = 0; i <= segs; i++) { a = a0 + (a1 - a0) * i / segs; x = cx + Math.cos(a) * r; y = cy + Math.sin(a) * r; if (prev) { E(prev[0], prev[1], cz, x, y, cz, c, g); } prev = [x, y]; }
  }

  function defineOffice() {
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
    // 4 (buyers beat) — a simple gateway frame so the corridor never feels empty
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

  function buildOfficeFromEdges() {
    // 1) merged LINE layer (manual merge — core three has no BufferGeometryUtils)
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
    officeGroup.add(lines);

    // 2) STRUCT homes sampled along edges (proportional to length), + rest scatter
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
        sStag[idx] = clamp01((4 - hz) / 50);     // front (z~+4) first, back (z~-46) last
        sBase[idx] = rand(0.34, 0.78);
        var k = AMB + idx;
        color[k * 3] = e.c[0] * 0.6 + 0.4; color[k * 3 + 1] = e.c[1] * 0.6 + 0.4; color[k * 3 + 2] = e.c[2] * 0.5 + 0.5;
        size[k] = rand(0.7, 1.7); phase[k] = Math.random() * TAU;
        pos[k * 3] = sRx[idx]; pos[k * 3 + 1] = sRy[idx]; pos[k * 3 + 2] = sRz[idx];
        alpha[k] = sBase[idx] * 0.5;
        idx++;
      }
    }
    // any unused struct slots -> parked
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
        // Render the OFFICIAL lockup in its own (white) color — do NOT recolor the
        // mark (BRAND.md §5/§9 forbid altering the lockup). AdditiveBlending over
        // the navy field already gives it the glow.
        var tex = new THREE.CanvasTexture(c); tex.needsUpdate = true;
        var mat = new THREE.MeshBasicMaterial({ map: tex, transparent: true, opacity: 0, blending: THREE.AdditiveBlending, depthTest: false, depthWrite: false });
        var mesh = new THREE.Mesh(new THREE.PlaneGeometry(h * ar, h), mat);
        mesh.position.set(x, y, z); mesh.frustumCulled = false;
        officeGroup.add(mesh);
        signage.push({ mesh: mesh, mat: mat, z: z });
      } catch (e) {}
    };
    img.onerror = function () {};
    img.src = url;
  }

  function buildOffice() {
    officeGroup = new THREE.Group();
    defineOffice();
    buildOfficeFromEdges();
    bakeMonogram(_monoUrl, 0, 0.4, -10.85, 2.6);       // reception wall
    bakeMonogram(_monoStackUrl, 0, 1.2, -45.9, 4.6);   // story-wall climax
    scene.add(officeGroup);
    _officeReady = true;
  }

  function updateStruct() {
    for (var i = 0; i < STRUCT; i++) {
      if (sStag[i] > 1.5) { continue; }
      var k = AMB + i;
      var f = easeOutCubic(clamp01((officeAssembly - sStag[i] * 0.5) / 0.5));
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
      s.mat.opacity = on * officeAssembly;
    }
  }

  // ---- screen px -> world (plane 12u in front of the dollying camera) ------
  function screenToWorld(px, py) {
    camera.updateMatrixWorld();
    _v.set((px / vw) * 2 - 1, -((py / vh) * 2 - 1), 0.5).unproject(camera);
    _dir.copy(_v).sub(camera.position).normalize();
    var t = (-12) / _dir.z;            // intersect plane at camera.z - 12
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

  // ---- camera (per-section rect → dolly) ----------------------------------
  function computeCamera() {
    if (!_officeReady || !_sections.length) {
      // nebula fallback target (scrollFrac only needed here)
      var smax = Math.max(1, (document.documentElement.scrollHeight || 0) - vh);
      scrollFrac = clamp((window.pageYOffset || 0) / smax, 0, 1);
      oTX = mouseNX * 2.6; oTY = mouseNY * 1.9 - (scrollFrac - 0.5) * 6.0; oTZ = CAMZ; oP = 1; return;
    }
    var center = vh * 0.5, active = _sections.length - 1, p = 1, i, r;
    for (i = 0; i < _sections.length; i++) {
      r = _sections[i].getBoundingClientRect();
      if (r.bottom >= center && r.top <= center) { active = i; p = clamp01((center - r.top) / Math.max(1, r.height)); break; }
      if (r.top > center) { active = Math.max(0, i - 1); p = 1; break; }
    }
    var nx = Math.min(active + 1, W.length - 1), sp = smooth(p);
    var a = W[active] || W[0], b = W[nx] || a;
    var sx = a.x + (b.x - a.x) * sp, sy = a.y + (b.y - a.y) * sp, sz = a.z + (b.z - a.z) * sp, spp = a.p + (b.p - a.p) * sp;
    // act progress from the whole sections container → assembly with lead in/out
    var cr = _act.getBoundingClientRect();
    var ap = clamp01((center - cr.top) / Math.max(1, cr.height));
    var asm = band(ap, 0.0, 0.10) * (1 - band(ap, 0.90, 1.0));
    oTZ = CAMZ + (sz - CAMZ) * asm;
    oTX = sx * asm; oTY = sy * asm;
    oP = 1 - (1 - spp) * asm;
    if (Math.abs(asm - officeAssembly) > 0.0006) { _structDirty = true; }
    officeAssembly = asm;
  }

  // ---- loop ---------------------------------------------------------------
  function frame(t) {
    rafId = raf(frame);
    var dt = lastT ? (t - lastT) / 1000 : 0.016; lastT = t;
    if (dt > 0.05) { dt = 0.05; }
    uTime += dt; material.uniforms.uTime.value = uTime;

    if (_camDirty) { computeCamera(); _camDirty = false; }  // one bounded layout read per frame

    // Adaptive quality: sample frame time DURING the office phase (the heavy
    // render), not the idle hero; re-arm until it degrades once or stays healthy.
    if (!_degraded && officeAssembly > 0.05) {
      _fpsAcc += dt; _fpsN++;
      if (_fpsN >= 90) {
        if ((_fpsAcc / _fpsN) > 0.024 && dpr > 1.0) { dpr = 1.0; renderer.setPixelRatio(dpr); material.uniforms.uPixelRatio.value = dpr; _degraded = true; }
        else { _fpsAcc = 0; _fpsN = 0; }
      }
    }

    var driftX = Math.sin(uTime * 0.13) * 1.1, driftY = Math.cos(uTime * 0.10) * 0.8;
    oX += (oTX - oX) * 0.05; oY += (oTY - oY) * 0.05; oZ += (oTZ - oZ) * 0.05;
    var px = (mouseNX * 2.6 + driftX) * oP, py = (mouseNY * 1.9 + driftY) * oP;
    camera.position.set(oX + px, oY + py, oZ);

    if (_officeReady) {
      if (_structDirty) { updateStruct(); _structDirty = false; }
      if (officeAssembly > 0.001 || lineMat.uniforms.uReveal.value > 0.001) {
        lineMat.uniforms.uReveal.value += (officeAssembly - lineMat.uniforms.uReveal.value) * 0.12;
        updateSignage();
      }
    }
    updateBursts(dt);
    renderer.render(scene, camera);
  }
  function start() { if (!started) { started = true; lastT = 0; rafId = raf(frame); } }
  function stop() { if (started) { started = false; caf(rafId); rafId = null; } }

  function resize() {
    vw = window.innerWidth; vh = window.innerHeight;
    dpr = Math.min(window.devicePixelRatio || 1, 1.25);
    renderer.setPixelRatio(dpr); renderer.setSize(vw, vh, false);
    camera.aspect = vw / vh; camera.updateProjectionMatrix();
    material.uniforms.uPixelRatio.value = dpr; computeExtents(); _camDirty = true;
  }

  // ---- cards (unchanged form/crumble; bursts now land at camera.z-12) ------
  function staggerFor(rectTop) { return clamp((rectTop / vh) * 200, 0, 260); }
  function formCard(el, delay) {
    el.classList.remove('is-crumbling'); el.classList.add('is-forming');
    el.style.transitionDelay = (delay / 1000) + 's';
    raf(function () { el.classList.add('is-formed'); });
    var isFrame = el.hasAttribute('data-office-frame');
    setTimeout(function () { if (!document.hidden) { spawnBurst(el.getBoundingClientRect(), 'in', isFrame); } }, delay);
    setTimeout(function () { el.classList.remove('is-forming'); }, delay + 950);
    countUp(el);
  }
  function crumbleCard(el, delay) {
    el.classList.remove('is-formed'); el.classList.add('is-crumbling');
    el.style.transitionDelay = (delay / 1000) + 's';
    var isFrame = el.hasAttribute('data-office-frame');
    setTimeout(function () { if (!document.hidden) { spawnBurst(el.getBoundingClientRect(), 'out', isFrame); } }, delay);
  }
  function countUp(el) {
    var nums = el.querySelectorAll('.cb-office-stat__num[data-count]'); if (!nums.length) { return; }
    for (var i = 0; i < nums.length; i++) {
      (function (n) {
        if (n.__c) { return; } n.__c = true;
        var target = parseInt(n.getAttribute('data-count'), 10) || 0, t0 = now(), dur = 1600;
        (function step(t) { var p = clamp((t - t0) / dur, 0, 1); n.textContent = Math.round(target * easeOutCubic(p)).toLocaleString(); if (p < 1) { raf(step); } })(t0);
      })(nums[i]);
    }
  }
  function observeCards() {
    var cards = document.querySelectorAll('.cb-office-card[data-office]'); if (!cards.length) { return; }
    if (!('IntersectionObserver' in window)) { for (var k = 0; k < cards.length; k++) { cards[k].classList.add('is-formed'); cards[k].__s = 'formed'; } return; }
    var io = new IntersectionObserver(function (entries) {
      for (var j = 0; j < entries.length; j++) {
        var e = entries[j], el = e.target, delay = staggerFor(e.boundingClientRect.top);
        if (e.isIntersecting) { if (el.__s !== 'formed') { formCard(el, delay); el.__s = 'formed'; } }
        else { if (el.__s === 'formed') { crumbleCard(el, delay); el.__s = 'crumbled'; } }
      }
    }, { rootMargin: '-8% 0px -14% 0px', threshold: [0, 0.18] });
    for (var c = 0; c < cards.length; c++) { cards[c].__s = 'pre'; io.observe(cards[c]); }
    setTimeout(function () { for (var s = 0; s < cards.length; s++) { var el = cards[s]; if (el.__s === 'pre') { var r = el.getBoundingClientRect(); if (r.top < vh && r.bottom > 0) { formCard(el, 0); el.__s = 'formed'; } } } }, 1400);
  }
  function observeSections() {
    var dots = document.querySelectorAll('.cb-office-nav__dot');
    var sections = document.querySelectorAll('[data-office-section]');
    if (sections.length && 'IntersectionObserver' in window && dots.length) {
      var sio = new IntersectionObserver(function (entries) {
        for (var i = 0; i < entries.length; i++) {
          if (entries[i].isIntersecting) {
            var idx = entries[i].target.getAttribute('data-office-section');
            for (var d = 0; d < dots.length; d++) { dots[d].classList.toggle('is-active', dots[d].getAttribute('data-office-to') === idx); }
          }
        }
      }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });
      for (var s = 0; s < sections.length; s++) { sio.observe(sections[s]); }
    }
    for (var k = 0; k < dots.length; k++) {
      dots[k].addEventListener('click', function () { var t = document.querySelector('[data-office-section="' + this.getAttribute('data-office-to') + '"]'); if (t) { t.scrollIntoView({ behavior: 'smooth', block: 'start' }); } });
    }
  }
  function trackScroll() {
    function upd() {
      var max = Math.max(1, (document.documentElement.scrollHeight || 0) - window.innerHeight);
      scrollFrac = clamp((window.pageYOffset || 0) / max, 0, 1);
      computeCamera();
    }
    window.addEventListener('scroll', upd, { passive: true });
    upd();
  }

  // ---- init ---------------------------------------------------------------
  function init(opts) {
    if (initialized) { return true; }
    opts = opts || {};
    if (!THREE) { return false; }
    try { if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return false; } } catch (e) {}
    _monoUrl = opts.monogram || ''; _monoStackUrl = opts.monogramStacked || opts.monogram || '';

    var sel = opts.canvas || '#cb-office-canvas';
    canvas = (typeof sel === 'string') ? document.querySelector(sel) : sel;
    if (!canvas) { return false; }
    try { renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: false, powerPreference: 'high-performance' }); }
    catch (e) { renderer = null; }
    if (!renderer) { return false; }

    try {
      _v = new THREE.Vector3(); _dir = new THREE.Vector3(); _out = new THREE.Vector3();
      renderer.setClearColor(0x000000, 0);
      vw = window.innerWidth; vh = window.innerHeight; dpr = Math.min(window.devicePixelRatio || 1, 1.25);

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

      renderer.setPixelRatio(dpr); renderer.setSize(vw, vh, false);
      document.documentElement.classList.add('cb-office-on');

      // office layer — isolated so a failure degrades to fusion (nebula+cards)
      try {
        _sections = [].slice.call(document.querySelectorAll('[data-office-section]'));
        _act = document.querySelector('.cb-office-scenes') || (_sections[0] && _sections[0].parentNode) || document.body;
        buildOffice();
      } catch (eo) { _officeReady = false; if (window.console) { console.warn('[cb-office] office build failed; running nebula+cards only.', eo); } }

      observeCards(); observeSections(); trackScroll();
      if (window.CBCursor && window.CBCursor.init) { try { window.CBCursor.init(); } catch (e2) {} }

      // A11y: if keyboard focus lands in a not-yet-formed (invisible) card, reveal it.
      document.addEventListener('focusin', function (e) {
        var el = e.target, card = (el && el.closest) ? el.closest('.cb-office-card[data-office]') : null;
        if (card && card.__s !== 'formed') { formCard(card, 0); card.__s = 'formed'; }
      }, true);

      // Property Watch is decorative on the preview — stop the native page reload
      // and confirm inline instead of silently navigating to ?email=...
      document.addEventListener('submit', function (e) {
        var f = e.target;
        if (f && f.matches && f.matches('form[data-cb-watch]')) {
          e.preventDefault();
          if (f.parentNode) {
            var note = document.createElement('p');
            note.className = 'cb-office-watch__note';
            note.style.opacity = '1';
            note.textContent = "Thanks — you're on the Property Watch list. We'll be in touch.";
            f.parentNode.replaceChild(note, f);
          }
        }
      }, false);

      window.addEventListener('mousemove', function (e) { mouseNX = (e.clientX / vw) * 2 - 1; mouseNY = -((e.clientY / vh) * 2 - 1); }, { passive: true });
      var rt; window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(resize, 180); }, { passive: true });
      document.addEventListener('visibilitychange', function () { if (document.hidden) { stop(); } else { start(); } });

      start(); initialized = true; return true;
    } catch (err) {
      document.documentElement.classList.remove('cb-office-on'); stop();
      if (renderer && renderer.dispose) { try { renderer.dispose(); } catch (e3) {} }
      if (window.console) { console.warn('[cb-office] init failed; using static fallback.', err); }
      return false;
    }
  }

  window.CBOffice = { init: init };

})(window, document);

/* ==========================================================================
 * CB Legacy — WebGL Scrolltelling :: scenes.js
 * Global: window.CBScenes
 * Load order: THREE -> CBShaders -> CBEngine -> CBScenes -> CBCursor -> CBWebGL
 *
 * The 8 per-scene 3D builders (CONTRACT §7 + BRIEF "Scene snippets").
 *
 *   CBScenes.build(layerKey, { texture, width, height, params })
 *     -> { scene, camera (PerspectiveCamera fov 55), update(localProg,time,mouse), dispose() }
 *
 * Every builder adds a cover-fit fullscreen image plane carrying {texture}.
 * UMD Three.js via window.THREE only. No imports, no examples/jsm addons.
 * Production quality, self-contained, no per-frame allocations, full dispose paths.
 * ========================================================================== */
(function (global) {
  'use strict';

  var THREE = global.THREE;
  if (!THREE) {
    // Defensive: without THREE we still publish a usable (no-op) global so the
    // page never throws during load. Engine/main guard webgl support separately.
    global.CBScenes = {
      build: function () {
        return {
          scene: null,
          camera: null,
          update: function () {},
          dispose: function () {}
        };
      }
    };
    return;
  }

  /* ----------------------------------------------------------------------
   * Brand tokens (do not invent colors — CONTRACT §0)
   * -------------------------------------------------------------------- */
  var COLOR = {
    navy: 0x012169,
    navyMid: 0x1b3c55,
    navyDark: 0x0a1730,
    blue: 0x1f69ff,
    gold: 0x1f69ff, // rerouted to CB Bright Blue per BRAND.md (was gold 0xc9a84c)
    cream: 0xf0ebe0,
    mist: 0xbecad7,
    white: 0xffffff
  };

  /* Camera / plane geometry constants. The background plane sits at this Z and
   * is sized to exactly cover the 55° FOV (cover-fit, see coverPlaneSize). */
  var FOV = 55;
  var PLANE_Z = -300;        // background plane depth in front of camera origin
  var CAM_Z_DEFAULT = 0;     // most cameras sit at origin looking down -Z

  /* ----------------------------------------------------------------------
   * Tiny inline value-noise / Perlin-ish helper (shared, allocation-free at use)
   * Classic 3D gradient noise (Perlin). Deterministic, no dependencies.
   * -------------------------------------------------------------------- */
  var Noise = (function () {
    var p = new Uint8Array(512);
    var perm = [
      151, 160, 137, 91, 90, 15, 131, 13, 201, 95, 96, 53, 194, 233, 7, 225,
      140, 36, 103, 30, 69, 142, 8, 99, 37, 240, 21, 10, 23, 190, 6, 148,
      247, 120, 234, 75, 0, 26, 197, 62, 94, 252, 219, 203, 117, 35, 11, 32,
      57, 177, 33, 88, 237, 149, 56, 87, 174, 20, 125, 136, 171, 168, 68, 175,
      74, 165, 71, 134, 139, 48, 27, 166, 77, 146, 158, 231, 83, 111, 229, 122,
      60, 211, 133, 230, 220, 105, 92, 41, 55, 46, 245, 40, 244, 102, 143, 54,
      65, 25, 63, 161, 1, 216, 80, 73, 209, 76, 132, 187, 208, 89, 18, 169,
      200, 196, 135, 130, 116, 188, 159, 86, 164, 100, 109, 198, 173, 186, 3, 64,
      52, 217, 226, 250, 124, 123, 5, 202, 38, 147, 118, 126, 255, 82, 85, 212,
      207, 206, 59, 227, 47, 16, 58, 17, 182, 189, 28, 42, 223, 183, 170, 213,
      119, 248, 152, 2, 44, 154, 163, 70, 221, 153, 101, 155, 167, 43, 172, 9,
      129, 22, 39, 253, 19, 98, 108, 110, 79, 113, 224, 232, 178, 185, 112, 104,
      218, 246, 97, 228, 251, 34, 242, 193, 238, 210, 144, 12, 191, 179, 162, 241,
      81, 51, 145, 235, 249, 14, 239, 107, 49, 192, 214, 31, 181, 199, 106, 157,
      184, 84, 204, 176, 115, 121, 50, 45, 127, 4, 150, 254, 138, 236, 205, 93,
      222, 114, 67, 29, 24, 72, 243, 141, 128, 195, 78, 66, 215, 61, 156, 180
    ];
    for (var i = 0; i < 256; i++) { p[i] = perm[i]; p[i + 256] = perm[i]; }

    function fade(t) { return t * t * t * (t * (t * 6 - 15) + 10); }
    function lerp(t, a, b) { return a + t * (b - a); }
    function grad(hash, x, y, z) {
      var h = hash & 15;
      var u = h < 8 ? x : y;
      var v = h < 4 ? y : (h === 12 || h === 14 ? x : z);
      return ((h & 1) === 0 ? u : -u) + ((h & 2) === 0 ? v : -v);
    }

    function noise3(x, y, z) {
      var X = Math.floor(x) & 255, Y = Math.floor(y) & 255, Z = Math.floor(z) & 255;
      x -= Math.floor(x); y -= Math.floor(y); z -= Math.floor(z);
      var u = fade(x), v = fade(y), w = fade(z);
      var A = p[X] + Y, AA = p[A] + Z, AB = p[A + 1] + Z;
      var B = p[X + 1] + Y, BA = p[B] + Z, BB = p[B + 1] + Z;
      return lerp(w,
        lerp(v,
          lerp(u, grad(p[AA], x, y, z), grad(p[BA], x - 1, y, z)),
          lerp(u, grad(p[AB], x, y - 1, z), grad(p[BB], x - 1, y - 1, z))),
        lerp(v,
          lerp(u, grad(p[AA + 1], x, y, z - 1), grad(p[BA + 1], x - 1, y, z - 1)),
          lerp(u, grad(p[AB + 1], x, y - 1, z - 1), grad(p[BB + 1], x - 1, y - 1, z - 1))));
    }

    return { noise3: noise3 };
  })();

  /* ----------------------------------------------------------------------
   * Shared scratch objects (NO per-frame allocations — reuse these)
   * -------------------------------------------------------------------- */
  var _color = new THREE.Color();

  /* ----------------------------------------------------------------------
   * Helpers
   * -------------------------------------------------------------------- */

  function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }

  // power3.out / power2.inOut easings used in the brief snippets
  function easeOutCubic(t) { t = clamp(t, 0, 1); var u = 1 - t; return 1 - u * u * u; }
  function easeInOutQuad(t) {
    t = clamp(t, 0, 1);
    return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
  }

  // staggered local progress for a child entering with offset/duration in [0..1]
  function staggerProg(localProg, start, duration) {
    if (duration <= 0) return localProg >= start ? 1 : 0;
    return clamp((localProg - start) / duration, 0, 1);
  }

  /* The plane size that exactly covers the 55° FOV at distance `dist`, then
   * scaled up to "cover" (not "contain") the viewport aspect given the image
   * aspect. Returns {w, h}. dist is the distance from camera to plane. */
  function coverPlaneSize(dist, viewW, viewH, imgAspect) {
    var vFov = (FOV * Math.PI) / 180;
    var frustH = 2 * Math.tan(vFov / 2) * dist;
    var viewAspect = viewW / viewH || (16 / 9);
    var frustW = frustH * viewAspect;
    // cover-fit: choose plane dims so the image (imgAspect) fully covers frustum
    var planeW, planeH;
    if (imgAspect > viewAspect) {
      // image wider than view -> match height, overflow width
      planeH = frustH;
      planeW = frustH * imgAspect;
    } else {
      // image taller/narrower -> match width, overflow height
      planeW = frustW;
      planeH = frustW / imgAspect;
    }
    return { w: planeW, h: planeH, frustW: frustW, frustH: frustH };
  }

  function textureAspect(texture, fallbackW, fallbackH) {
    if (texture && texture.image && texture.image.width && texture.image.height) {
      return texture.image.width / texture.image.height;
    }
    // Asset map: all images are 2048x1170
    if (fallbackW && fallbackH) return fallbackW / fallbackH;
    return 2048 / 1170;
  }

  /* Build the shared cover-fit background image plane carrying {texture}.
   * Returns { mesh, geometry, material }. Distance is |camZ - planeZ|. */
  function makeImagePlane(texture, width, height, opts) {
    opts = opts || {};
    var planeZ = opts.planeZ != null ? opts.planeZ : PLANE_Z;
    var camZ = opts.camZ != null ? opts.camZ : CAM_Z_DEFAULT;
    var dist = Math.abs(camZ - planeZ);
    var imgAspect = textureAspect(texture, 2048, 1170);
    // Over-size the cover plane a touch so parallax/dolly never reveals an edge.
    var pad = opts.pad != null ? opts.pad : 1.18;
    var size = coverPlaneSize(dist, width, height, imgAspect);
    var geo = new THREE.PlaneGeometry(size.w * pad, size.h * pad, 1, 1);
    var mat = new THREE.MeshBasicMaterial({
      map: texture || null,
      color: texture ? 0xffffff : COLOR.navyDark,
      depthWrite: false,
      depthTest: false,
      toneMapped: false
    });
    var mesh = new THREE.Mesh(geo, mat);
    mesh.position.set(0, 0, planeZ);
    mesh.renderOrder = -10; // always behind 3D content
    mesh.frustumCulled = false;
    return { mesh: mesh, geometry: geo, material: mat, planeSize: size, imgAspect: imgAspect };
  }

  function makePerspectiveCamera(width, height) {
    var cam = new THREE.PerspectiveCamera(FOV, (width / height) || (16 / 9), 1, 6000);
    cam.position.set(0, 0, CAM_Z_DEFAULT);
    return cam;
  }

  /* Soft round radial sprite texture (white core -> transparent). Used for
   * dust / star / bokeh points. Generated once, cached. */
  var _spriteCache = null;
  function softSpriteTexture() {
    if (_spriteCache) return _spriteCache;
    var size = 64;
    var c = document.createElement('canvas');
    c.width = c.height = size;
    var ctx = c.getContext('2d');
    var g = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    g.addColorStop(0.0, 'rgba(255,255,255,1.0)');
    g.addColorStop(0.25, 'rgba(255,255,255,0.85)');
    g.addColorStop(0.55, 'rgba(255,255,255,0.35)');
    g.addColorStop(1.0, 'rgba(255,255,255,0.0)');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, size, size);
    var tex = new THREE.CanvasTexture(c);
    tex.minFilter = THREE.LinearFilter;
    tex.magFilter = THREE.LinearFilter;
    tex.needsUpdate = true;
    _spriteCache = tex;
    return tex;
  }

  /* Disposer registry helper — collects geometries/materials/textures/RTs so
   * each builder's dispose() is exhaustive and trivial. */
  function makeDisposer() {
    var items = [];
    return {
      add: function (obj) { if (obj) items.push(obj); return obj; },
      run: function () {
        for (var i = 0; i < items.length; i++) {
          var o = items[i];
          if (!o) continue;
          try { if (o.dispose) o.dispose(); } catch (e) {}
        }
        items.length = 0;
      }
    };
  }

  /* ======================================================================
   * BUILDER 1 — arrival
   * image plane + slow camera dolly (z 600->350) + y-bob + mouse parallax
   * planes; gold dust particles (Points <=3000, soft sprite, drift).
   * ==================================================================== */
  function buildArrival(ctx) {
    var texture = ctx.texture, width = ctx.width, height = ctx.height;
    var d = makeDisposer();
    var scene = new THREE.Scene();
    var camera = makePerspectiveCamera(width, height);
    camera.position.set(0, 0, 600);

    // Background plane sized for the *near* dolly distance so it always covers.
    var bg = makeImagePlane(texture, width, height, { planeZ: -350, camZ: 350, pad: 1.25 });
    d.add(bg.geometry); d.add(bg.material);
    scene.add(bg.mesh);

    // ENH: 3 blurred parallax planes reacting to mouse (subtle gold-tinted veils).
    var parallax = [];
    var pConf = [
      { z: -300, s: 0.015, op: 0.10, col: COLOR.navyMid },
      { z: -200, s: 0.030, op: 0.08, col: COLOR.navy },
      { z: -120, s: 0.055, op: 0.06, col: COLOR.gold }
    ];
    for (var pi = 0; pi < pConf.length; pi++) {
      var cfg = pConf[pi];
      var size = coverPlaneSize(Math.abs(camera.position.z - cfg.z), width, height, 16 / 9);
      var pg = new THREE.PlaneGeometry(size.w * 1.4, size.h * 1.4, 1, 1);
      var pm = new THREE.MeshBasicMaterial({
        color: cfg.col, transparent: true, opacity: cfg.op,
        depthWrite: false, depthTest: false, blending: THREE.AdditiveBlending
      });
      d.add(pg); d.add(pm);
      var pmesh = new THREE.Mesh(pg, pm);
      pmesh.position.set(0, 0, cfg.z);
      pmesh.renderOrder = -9 + pi;
      pmesh.frustumCulled = false;
      scene.add(pmesh);
      parallax.push({ mesh: pmesh, s: cfg.s });
    }

    // Gold dust particles
    var COUNT = 3000;
    var positions = new Float32Array(COUNT * 3);
    var phases = new Float32Array(COUNT);
    var basX = new Float32Array(COUNT);
    for (var i = 0; i < COUNT; i++) {
      var x = (Math.random() - 0.5) * 1400;
      var y = (Math.random() - 0.5) * 900;
      var z = -250 + Math.random() * 500;
      positions[i * 3] = x;
      positions[i * 3 + 1] = y;
      positions[i * 3 + 2] = z;
      basX[i] = x;
      phases[i] = Math.random() * Math.PI * 2;
    }
    var dustGeo = new THREE.BufferGeometry();
    dustGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    d.add(dustGeo);
    var sprite = softSpriteTexture();
    var dustMat = new THREE.PointsMaterial({
      color: COLOR.gold,
      size: 4.5,
      map: sprite,
      transparent: true,
      opacity: 0.6,
      depthWrite: false,
      sizeAttenuation: true,
      blending: THREE.AdditiveBlending
    });
    d.add(dustMat);
    var dust = new THREE.Points(dustGeo, dustMat);
    dust.frustumCulled = false;
    scene.add(dust);

    // ENH: faint tilted grid helper (gold, subtle)
    var grid = new THREE.GridHelper(400, 20, COLOR.gold, COLOR.gold);
    grid.material.transparent = true;
    grid.material.opacity = 0.08;
    grid.material.depthWrite = false;
    grid.rotation.x = (-15 * Math.PI) / 180;
    grid.position.set(0, -120, -150);
    d.add(grid.geometry); d.add(grid.material);
    scene.add(grid);

    var posAttr = dustGeo.attributes.position;

    function update(localProg, time, mouse) {
      var lp = clamp(localProg, 0, 1);
      // scroll 0->1 across hold: camera.position.z 600 -> 350 (brief snippet)
      camera.position.z = 600 - (600 - 350) * easeOutCubic(lp);
      // slow vertical bob
      camera.position.y = Math.sin(time * 0.0003) * 8;
      camera.lookAt(0, 0, -350);

      var mx = mouse ? mouse.x : 0;
      var my = mouse ? mouse.y : 0;
      for (var k = 0; k < parallax.length; k++) {
        parallax[k].mesh.position.x = -mx * parallax[k].s * 200;
        parallax[k].mesh.position.y = my * parallax[k].s * 120;
      }

      // dust drift (allocation-free)
      var arr = posAttr.array;
      for (var j = 0; j < COUNT; j++) {
        var ix = j * 3;
        arr[ix] = basX[j] + Math.sin(time * 0.00012 + phases[j]) * 28;
        arr[ix + 1] += 0.06; // gentle upward drift
        if (arr[ix + 1] > 460) arr[ix + 1] = -460;
      }
      posAttr.needsUpdate = true;
      dust.rotation.y = time * 0.00002;
    }

    function dispose() {
      d.run();
      while (scene.children.length) scene.remove(scene.children[0]);
    }

    return { scene: scene, camera: camera, update: update, dispose: dispose };
  }

  /* ======================================================================
   * BUILDER 2 — welcome
   * image plane; 4 gold marker spheres at varied Z with bloom billboards,
   * animate in (y +80 -> 0 staggered via localProg).
   * ==================================================================== */
  function buildWelcome(ctx) {
    var texture = ctx.texture, width = ctx.width, height = ctx.height;
    var d = makeDisposer();
    var scene = new THREE.Scene();
    var camera = makePerspectiveCamera(width, height);
    camera.position.set(0, 0, 0);

    var bg = makeImagePlane(texture, width, height, { planeZ: PLANE_Z, camZ: 0, pad: 1.18 });
    d.add(bg.geometry); d.add(bg.material);
    scene.add(bg.mesh);

    // Lighting for the spheres (markers read as physical gold beads).
    var amb = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(amb);
    var key = new THREE.DirectionalLight(0xfff0c8, 0.9);
    key.position.set(1, 1.2, 2);
    scene.add(key);

    var sphereGeo = new THREE.SphereGeometry(6, 32, 24);
    d.add(sphereGeo);
    var sprite = softSpriteTexture();

    var ZS = [-30, -60, -100, -140];
    var XS = [-70, 50, -30, 90];
    var markers = [];
    for (var i = 0; i < 4; i++) {
      var mat = new THREE.MeshStandardMaterial({
        color: COLOR.gold, roughness: 0.25, metalness: 0.85,
        emissive: COLOR.gold, emissiveIntensity: 0.25
      });
      d.add(mat);
      var sph = new THREE.Mesh(sphereGeo, mat);
      sph.position.set(XS[i], 0, ZS[i]);
      scene.add(sph);

      // bloom billboard behind each
      var bmat = new THREE.SpriteMaterial({
        map: sprite, color: COLOR.gold, transparent: true,
        opacity: 0.55, depthWrite: false, blending: THREE.AdditiveBlending
      });
      d.add(bmat);
      var billboard = new THREE.Sprite(bmat);
      billboard.scale.set(34, 34, 1);
      billboard.position.set(XS[i], 0, ZS[i] - 2);
      scene.add(billboard);

      markers.push({ sph: sph, bill: billboard, baseY: 0, x: XS[i], z: ZS[i], mat: mat, bmat: bmat });
    }

    // ENH: thin warm "fog" plane to catch the marker glow
    var fogGeo = new THREE.PlaneGeometry(600, 360, 1, 1);
    var fogMat = new THREE.MeshBasicMaterial({
      color: 0xfff0c8, transparent: true, opacity: 0.05,
      depthWrite: false, blending: THREE.AdditiveBlending
    });
    d.add(fogGeo); d.add(fogMat);
    var fog = new THREE.Mesh(fogGeo, fogMat);
    fog.position.set(40, 10, -20);
    fog.frustumCulled = false;
    scene.add(fog);

    function update(localProg, time, mouse) {
      var lp = clamp(localProg, 0, 1);
      for (var i = 0; i < markers.length; i++) {
        var m = markers[i];
        var pr = easeOutCubic(staggerProg(lp, i * 0.12, 0.5));
        var y = (1 - pr) * 80 + m.baseY;
        // subtle idle float once in
        y += Math.sin(time * 0.001 + i * 1.3) * 2.5 * pr;
        m.sph.position.y = y;
        m.bill.position.y = y;
        var op = 0.55 * pr;
        m.bmat.opacity = op + Math.sin(time * 0.002 + i) * 0.06 * pr;
        m.mat.emissiveIntensity = 0.15 + 0.2 * pr;
        m.sph.rotation.y = time * 0.0006;
      }
      var mx = mouse ? mouse.x : 0;
      camera.position.x = mx * 6;
      camera.lookAt(0, 0, -300);
    }

    function dispose() {
      d.run();
      while (scene.children.length) scene.remove(scene.children[0]);
    }

    return { scene: scene, camera: camera, update: update, dispose: dispose };
  }

  /* ======================================================================
   * BUILDER 3 — listings
   * image plane; 6 card planes in a gentle arc (curving back), float-in by
   * localProg, arc rotates y 0 -> -15deg across the hold.
   * ==================================================================== */
  function buildListings(ctx) {
    var texture = ctx.texture, width = ctx.width, height = ctx.height;
    var d = makeDisposer();
    var scene = new THREE.Scene();
    var camera = makePerspectiveCamera(width, height);
    camera.position.set(0, 0, 220);

    var bg = makeImagePlane(texture, width, height, { planeZ: -300, camZ: 220, pad: 1.2 });
    d.add(bg.geometry); d.add(bg.material);
    scene.add(bg.mesh);

    var amb = new THREE.AmbientLight(0xffffff, 0.85);
    scene.add(amb);
    var key = new THREE.DirectionalLight(0xffffff, 0.5);
    key.position.set(0.5, 1, 1);
    scene.add(key);

    // Group holds the arc so we can rotate the whole arrangement.
    var arc = new THREE.Group();
    scene.add(arc);

    var cardGeo = new THREE.PlaneGeometry(80, 60, 1, 1);
    d.add(cardGeo);

    var N = 6;
    var cards = [];
    // first (-200,0,0) -> last (200,0,0), each +3deg Y and -5 Z (curving back)
    for (var i = 0; i < N; i++) {
      var t = N > 1 ? i / (N - 1) : 0;
      var x = -200 + t * 400;
      var z = -i * 5;
      var ry = (((i * 3) - ((N - 1) * 3) / 2) * Math.PI) / 180; // center the arc
      var mat = new THREE.MeshStandardMaterial({
        color: COLOR.cream, roughness: 0.55, metalness: 0.05,
        emissive: COLOR.navy, emissiveIntensity: 0.04,
        side: THREE.DoubleSide, transparent: true, opacity: 1.0
      });
      d.add(mat);
      var card = new THREE.Mesh(cardGeo, mat);
      card.position.set(x, 0, z);
      card.rotation.y = ry;
      arc.add(card);

      // thin gold frame edge (outline via slightly larger backing plane)
      var frameGeo = new THREE.PlaneGeometry(86, 66, 1, 1);
      d.add(frameGeo);
      var frameMat = new THREE.MeshBasicMaterial({
        color: COLOR.gold, transparent: true, opacity: 0.5,
        side: THREE.DoubleSide, depthWrite: false
      });
      d.add(frameMat);
      var frame = new THREE.Mesh(frameGeo, frameMat);
      frame.position.set(x, 0, z - 0.5);
      frame.rotation.y = ry;
      arc.add(frame);

      // ENH: small price-tag sphere upper-left of each card
      var psGeo = new THREE.SphereGeometry(4, 16, 12);
      d.add(psGeo);
      var psMat = new THREE.MeshStandardMaterial({
        color: COLOR.gold, roughness: 0.2, metalness: 0.8,
        emissive: COLOR.gold, emissiveIntensity: 0.3
      });
      d.add(psMat);
      var ps = new THREE.Mesh(psGeo, psMat);
      ps.position.set(x - 34, 26, z + 1);
      ps.rotation.y = ry;
      arc.add(ps);

      cards.push({
        card: card, frame: frame, ps: ps, mat: mat, frameMat: frameMat,
        baseY: 0, baseZ: z, idx: i
      });
    }

    function update(localProg, time, mouse) {
      var lp = clamp(localProg, 0, 1);
      // arc rotates Y 0 -> -15deg across the section
      arc.rotation.y = ((-15 * Math.PI) / 180) * easeInOutQuad(lp);
      for (var i = 0; i < cards.length; i++) {
        var c = cards[i];
        var pr = easeOutCubic(staggerProg(lp, i * 0.06, 0.5));
        var y = (pr - 1) * 30 + c.baseY; // float in Y -30 -> 0
        y += Math.sin(time * 0.0009 + i * 0.9) * 1.8 * pr;
        c.card.position.y = y;
        c.frame.position.y = y;
        c.ps.position.y = 26 + y;
        c.mat.opacity = pr;
        c.frameMat.opacity = 0.5 * pr;
        c.ps.rotation.y = time * 0.0008;
      }
      var mx = mouse ? mouse.x : 0;
      var my = mouse ? mouse.y : 0;
      camera.position.x = mx * 12;
      camera.position.y = my * 8;
      camera.lookAt(0, 0, -40);
    }

    function dispose() {
      d.run();
      while (scene.children.length) scene.remove(scene.children[0]);
    }

    return { scene: scene, camera: camera, update: update, dispose: dispose };
  }

  /* ======================================================================
   * BUILDER 4 — legacy (signature)
   * image plane (dark); star field Points <=12000 on a sampled sphere, very
   * slow y-rotation; Milky-Way band <=4000 softer points. ENH gold ring orbits.
   * ==================================================================== */
  function buildLegacy(ctx) {
    var texture = ctx.texture, width = ctx.width, height = ctx.height;
    var d = makeDisposer();
    var scene = new THREE.Scene();
    var camera = makePerspectiveCamera(width, height);
    camera.position.set(0, 0, 0);

    var bg = makeImagePlane(texture, width, height, { planeZ: PLANE_Z, camZ: 0, pad: 1.18 });
    d.add(bg.geometry); d.add(bg.material);
    scene.add(bg.mesh);

    var sprite = softSpriteTexture();

    /* --- Primary star field: 12000 points on sphere r=2000 --- */
    var STAR_COUNT = 12000;
    var starPos = new Float32Array(STAR_COUNT * 3);
    var starCol = new Float32Array(STAR_COUNT * 3);
    var cWhite = new THREE.Color(COLOR.white);
    var cCream = new THREE.Color(COLOR.cream);
    var cGold = new THREE.Color(COLOR.gold);
    for (var i = 0; i < STAR_COUNT; i++) {
      // uniform sphere sampling
      var u = Math.random(), v = Math.random();
      var theta = 2 * Math.PI * u;
      var phi = Math.acos(2 * v - 1);
      var r = 2000;
      var sx = r * Math.sin(phi) * Math.cos(theta);
      var sy = r * Math.sin(phi) * Math.sin(theta);
      var sz = r * Math.cos(phi);
      starPos[i * 3] = sx; starPos[i * 3 + 1] = sy; starPos[i * 3 + 2] = sz;
      var roll = Math.random();
      var col = roll < 0.80 ? cWhite : (roll < 0.95 ? cCream : cGold);
      starCol[i * 3] = col.r; starCol[i * 3 + 1] = col.g; starCol[i * 3 + 2] = col.b;
    }
    var starGeo = new THREE.BufferGeometry();
    starGeo.setAttribute('position', new THREE.BufferAttribute(starPos, 3));
    starGeo.setAttribute('color', new THREE.BufferAttribute(starCol, 3));
    d.add(starGeo);
    var starMat = new THREE.PointsMaterial({
      size: 6, map: sprite, vertexColors: true, transparent: true,
      opacity: 0.95, depthWrite: false, sizeAttenuation: true,
      blending: THREE.AdditiveBlending
    });
    d.add(starMat);
    var stars = new THREE.Points(starGeo, starMat);
    stars.frustumCulled = false;
    scene.add(stars);

    /* --- Milky Way band: 4000 points along a great-circle band --- */
    var MW_COUNT = 4000;
    var mwPos = new Float32Array(MW_COUNT * 3);
    var mwCol = new Float32Array(MW_COUNT * 3);
    var bandTilt = 0.5; // radians, tilt the band off-axis
    for (var m = 0; m < MW_COUNT; m++) {
      var ang = Math.random() * Math.PI * 2;
      var bandR = 1900 + (Math.random() - 0.5) * 120;
      var spread = (Math.random() - 0.5) * 320; // band thickness
      var bx = Math.cos(ang) * bandR;
      var by = spread;
      var bz = Math.sin(ang) * bandR;
      // tilt around X
      var ty = by * Math.cos(bandTilt) - bz * Math.sin(bandTilt);
      var tz = by * Math.sin(bandTilt) + bz * Math.cos(bandTilt);
      mwPos[m * 3] = bx; mwPos[m * 3 + 1] = ty; mwPos[m * 3 + 2] = tz;
      var mc = Math.random() < 0.5 ? cWhite : cCream;
      mwCol[m * 3] = mc.r; mwCol[m * 3 + 1] = mc.g; mwCol[m * 3 + 2] = mc.b;
    }
    var mwGeo = new THREE.BufferGeometry();
    mwGeo.setAttribute('position', new THREE.BufferAttribute(mwPos, 3));
    mwGeo.setAttribute('color', new THREE.BufferAttribute(mwCol, 3));
    d.add(mwGeo);
    var mwMat = new THREE.PointsMaterial({
      size: 10, map: sprite, vertexColors: true, transparent: true,
      opacity: 0.35, depthWrite: false, sizeAttenuation: true,
      blending: THREE.AdditiveBlending
    });
    d.add(mwMat);
    var milkyway = new THREE.Points(mwGeo, mwMat);
    milkyway.frustumCulled = false;
    scene.add(milkyway);

    /* --- ENH: 4 thin gold ring orbits radii 80/120/160/200 --- */
    var ringRadii = [80, 120, 160, 200];
    var ringSpeeds = [0.0003, 0.0002, 0.00015, 0.0001];
    var rings = [];
    for (var ri = 0; ri < ringRadii.length; ri++) {
      var rr = ringRadii[ri];
      var rGeo = new THREE.RingGeometry(rr, rr + 2, 96, 1);
      d.add(rGeo);
      var rMat = new THREE.MeshBasicMaterial({
        color: COLOR.gold, transparent: true, opacity: 0.25,
        side: THREE.DoubleSide, depthWrite: false, blending: THREE.AdditiveBlending
      });
      d.add(rMat);
      var ring = new THREE.Mesh(rGeo, rMat);
      ring.rotation.x = Math.PI / 2 + (ri * 0.06);
      ring.position.z = -120;
      ring.frustumCulled = false;
      scene.add(ring);
      rings.push({ ring: ring, speed: ringSpeeds[ri] });
    }

    function update(localProg, time, mouse) {
      stars.rotation.y = time * 0.00008;
      milkyway.rotation.y = time * 0.00005;
      for (var i = 0; i < rings.length; i++) {
        rings[i].ring.rotation.z = time * rings[i].speed;
      }
      var mx = mouse ? mouse.x : 0;
      var my = mouse ? mouse.y : 0;
      camera.rotation.y = -mx * 0.04;
      camera.rotation.x = my * 0.025;
    }

    function dispose() {
      d.run();
      while (scene.children.length) scene.remove(scene.children[0]);
    }

    return { scene: scene, camera: camera, update: update, dispose: dispose };
  }

  /* ======================================================================
   * BUILDER 5 — door (signature)
   * image plane; two BoxGeometry door panels (navy MeshPhysicalMaterial,
   * brass cylinder handles) hinged on outer edges; swing open by localProg;
   * warm PointLight behind viewer ramps 0 -> 1.5 as they open.
   * ==================================================================== */
  function buildDoor(ctx) {
    var texture = ctx.texture, width = ctx.width, height = ctx.height;
    var d = makeDisposer();
    var scene = new THREE.Scene();
    var camera = makePerspectiveCamera(width, height);
    camera.position.set(0, 0, 220);

    var bg = makeImagePlane(texture, width, height, { planeZ: -360, camZ: 220, pad: 1.22 });
    d.add(bg.geometry); d.add(bg.material);
    scene.add(bg.mesh);

    // Lighting — physical doors need real lights.
    var amb = new THREE.AmbientLight(0x2a3550, 0.55);
    scene.add(amb);
    var key = new THREE.DirectionalLight(0xffffff, 0.7);
    key.position.set(0.4, 0.8, 1);
    scene.add(key);
    // Warm point light "behind viewer", ramps with opening.
    var warm = new THREE.PointLight(0xffd9a0, 0.0, 1200, 1.6);
    warm.position.set(0, 0, 120);
    scene.add(warm);
    // A second warm glow behind the doors so the gap reads as inviting light.
    var inner = new THREE.PointLight(0xffe6b8, 0.0, 900, 2.0);
    inner.position.set(0, 0, -140);
    scene.add(inner);

    // ENH: simple raised-panel canvas texture on the doors.
    function panelTexture() {
      var c = document.createElement('canvas');
      c.width = 256; c.height = 410;
      var g = c.getContext('2d');
      g.fillStyle = '#012169';
      g.fillRect(0, 0, 256, 410);
      // two raised rectangular panels
      g.strokeStyle = 'rgba(31,105,255,0.55)';
      g.lineWidth = 4;
      g.strokeRect(36, 30, 184, 150);
      g.strokeRect(36, 210, 184, 165);
      g.strokeStyle = 'rgba(10,23,48,0.8)';
      g.lineWidth = 2;
      g.strokeRect(46, 40, 164, 130);
      g.strokeRect(46, 220, 164, 145);
      var t = new THREE.CanvasTexture(c);
      t.needsUpdate = true;
      return t;
    }
    var leftTex = panelTexture();
    var rightTex = panelTexture();
    d.add(leftTex); d.add(rightTex);

    // Hinge groups so rotation pivots on the OUTER edges.
    var doorW = 100, doorH = 160, doorD = 8;
    var boxGeo = new THREE.BoxGeometry(doorW, doorH, doorD);
    d.add(boxGeo);

    function makeDoor(side, tex) {
      var mat = new THREE.MeshPhysicalMaterial({
        color: COLOR.navy, roughness: 0.15, metalness: 0.4,
        envMapIntensity: 1.2, map: tex, clearcoat: 0.3, clearcoatRoughness: 0.4
      });
      d.add(mat);
      var mesh = new THREE.Mesh(boxGeo, mat);
      // offset so the panel sits to the inner side of the hinge group
      mesh.position.x = (side === 'left') ? doorW / 2 : -doorW / 2;

      // brass handle: CylinderGeometry r1.5 h12
      var handleGeo = new THREE.CylinderGeometry(1.5, 1.5, 12, 16);
      d.add(handleGeo);
      var handleMat = new THREE.MeshStandardMaterial({
        color: COLOR.gold, roughness: 0.1, metalness: 0.9,
        emissive: COLOR.gold, emissiveIntensity: 0.12
      });
      d.add(handleMat);
      var handle = new THREE.Mesh(handleGeo, handleMat);
      // place near the inner edge of the leaf, vertically centered
      handle.position.set((side === 'left') ? doorW - 12 : -(doorW - 12), 0, doorD / 2 + 3);
      mesh.add(handle);

      var group = new THREE.Group();
      group.position.x = (side === 'left') ? -doorW / 2 : doorW / 2; // hinge on outer edge
      group.add(mesh);
      return { group: group, mat: mat, handleMat: handleMat };
    }

    var left = makeDoor('left', leftTex);
    var right = makeDoor('right', rightTex);
    scene.add(left.group);
    scene.add(right.group);

    function update(localProg, time, mouse) {
      var lp = clamp(localProg, 0, 1);
      var e = easeInOutQuad(lp); // power2.inOut
      // left -> -PI*0.55, right -> +PI*0.55
      left.group.rotation.y = -Math.PI * 0.55 * e;
      right.group.rotation.y = Math.PI * 0.55 * e;
      // warm light ramps 0 -> 1.5
      warm.intensity = 1.5 * e;
      inner.intensity = 2.2 * e;
      left.handleMat.emissiveIntensity = 0.12 + 0.2 * e;
      right.handleMat.emissiveIntensity = 0.12 + 0.2 * e;
      var mx = mouse ? mouse.x : 0;
      camera.position.x = mx * 8;
      camera.lookAt(0, 0, -80);
    }

    function dispose() {
      d.run();
      while (scene.children.length) scene.remove(scene.children[0]);
    }

    return { scene: scene, camera: camera, update: update, dispose: dispose };
  }

  /* ======================================================================
   * BUILDER 6 — communities
   * image plane; 6 gold pins (spheres) at fixed map positions with thin line
   * to a name label plane; camera descends (y 200 -> 80).
   * ENH: displaced terrain plane; river TubeGeometry drawing in by localProg.
   * ==================================================================== */
  function buildCommunities(ctx) {
    var texture = ctx.texture, width = ctx.width, height = ctx.height;
    var d = makeDisposer();
    var scene = new THREE.Scene();
    var camera = makePerspectiveCamera(width, height);
    camera.position.set(0, 200, 800);

    // Background sits well back; cover-fit for the descending camera.
    var bg = makeImagePlane(texture, width, height, { planeZ: -500, camZ: 800, pad: 1.25 });
    d.add(bg.geometry); d.add(bg.material);
    bg.mesh.position.set(0, 80, -500);
    scene.add(bg.mesh);

    var amb = new THREE.AmbientLight(0xffffff, 0.7);
    scene.add(amb);
    var key = new THREE.DirectionalLight(0xfff0c8, 0.7);
    key.position.set(0.5, 1, 0.4);
    scene.add(key);

    // ENH: low-seg displaced terrain plane (Perlin), navy 15%
    var terrGeo = new THREE.PlaneGeometry(400, 300, 64, 48);
    var tpos = terrGeo.attributes.position;
    for (var ti = 0; ti < tpos.count; ti++) {
      var vx = tpos.getX(ti), vy = tpos.getY(ti);
      var hgt = Noise.noise3(vx * 0.012, vy * 0.012, 0.0) * 8;
      tpos.setZ(ti, hgt);
    }
    terrGeo.computeVertexNormals();
    d.add(terrGeo);
    var terrMat = new THREE.MeshStandardMaterial({
      color: COLOR.navyMid, roughness: 0.9, metalness: 0.0,
      transparent: true, opacity: 0.15, wireframe: false,
      side: THREE.DoubleSide
    });
    d.add(terrMat);
    var terrain = new THREE.Mesh(terrGeo, terrMat);
    terrain.rotation.x = -Math.PI / 2;
    terrain.position.set(0, 0, -120);
    scene.add(terrain);

    // 6 pins at fixed map positions
    var PINS = [
      { name: 'Grape Creek', p: [-60, 10, -20] },
      { name: 'Bentwood', p: [-20, 12, -10] },
      { name: 'College Hills', p: [10, 8, 0] },
      { name: 'Christoval', p: [60, 6, 30] },
      { name: 'Wall', p: [-40, 5, 40] },
      { name: 'Lake Nasworthy', p: [30, 12, -40] }
    ];

    var pinGeo = new THREE.SphereGeometry(5, 20, 16);
    d.add(pinGeo);
    var sprite = softSpriteTexture();

    function labelTexture(text) {
      var c = document.createElement('canvas');
      c.width = 320; c.height = 80;
      var g = c.getContext('2d');
      g.clearRect(0, 0, 320, 80);
      g.fillStyle = 'rgba(10,23,48,0.78)';
      // rounded rect
      var rad = 14, w = 320, h = 80, x = 4, y = 4, ww = w - 8, hh = h - 8;
      g.beginPath();
      g.moveTo(x + rad, y);
      g.arcTo(x + ww, y, x + ww, y + hh, rad);
      g.arcTo(x + ww, y + hh, x, y + hh, rad);
      g.arcTo(x, y + hh, x, y, rad);
      g.arcTo(x, y, x + ww, y, rad);
      g.closePath();
      g.fill();
      g.strokeStyle = 'rgba(31,105,255,0.85)';
      g.lineWidth = 2;
      g.stroke();
      g.fillStyle = '#1F69FF';
      g.font = '700 30px "Josefin Sans", "Roboto", sans-serif';
      g.textAlign = 'center';
      g.textBaseline = 'middle';
      g.fillText(text, 160, 42);
      var t = new THREE.CanvasTexture(c);
      t.minFilter = THREE.LinearFilter;
      t.needsUpdate = true;
      return t;
    }

    var pins = [];
    for (var pi = 0; pi < PINS.length; pi++) {
      var def = PINS[pi];
      var pmat = new THREE.MeshStandardMaterial({
        color: COLOR.gold, roughness: 0.2, metalness: 0.85,
        emissive: COLOR.gold, emissiveIntensity: 0.3
      });
      d.add(pmat);
      var pin = new THREE.Mesh(pinGeo, pmat);
      pin.position.set(def.p[0], def.p[1], def.p[2]);
      scene.add(pin);

      // glow billboard
      var gmat = new THREE.SpriteMaterial({
        map: sprite, color: COLOR.gold, transparent: true, opacity: 0.5,
        depthWrite: false, blending: THREE.AdditiveBlending
      });
      d.add(gmat);
      var glow = new THREE.Sprite(gmat);
      glow.scale.set(22, 22, 1);
      glow.position.copy(pin.position);
      scene.add(glow);

      // thin line up to label
      var labelY = def.p[1] + 36;
      var lpos = new Float32Array([
        def.p[0], def.p[1], def.p[2],
        def.p[0], labelY, def.p[2]
      ]);
      var lineGeo = new THREE.BufferGeometry();
      lineGeo.setAttribute('position', new THREE.BufferAttribute(lpos, 3));
      d.add(lineGeo);
      var lineMat = new THREE.LineBasicMaterial({
        color: COLOR.gold, transparent: true, opacity: 0.6
      });
      d.add(lineMat);
      var line = new THREE.Line(lineGeo, lineMat);
      scene.add(line);

      // label plane (sprite, always faces camera)
      var ltex = labelTexture(def.name);
      d.add(ltex);
      var lmat = new THREE.SpriteMaterial({
        map: ltex, transparent: true, opacity: 0.95, depthWrite: false
      });
      d.add(lmat);
      var label = new THREE.Sprite(lmat);
      label.scale.set(60, 15, 1);
      label.position.set(def.p[0], labelY + 8, def.p[2]);
      scene.add(label);

      pins.push({
        pin: pin, glow: glow, line: line, label: label,
        pmat: pmat, gmat: gmat, lmat: lmat, base: def.p.slice(), idx: pi
      });
    }

    // ENH: river TubeGeometry drawing in by localProg
    var riverCurve = new THREE.CatmullRomCurve3([
      new THREE.Vector3(-110, 2, 50),
      new THREE.Vector3(-60, 3, 10),
      new THREE.Vector3(-10, 2, -10),
      new THREE.Vector3(40, 3, -30),
      new THREE.Vector3(95, 2, -60)
    ]);
    var riverGeo = new THREE.TubeGeometry(riverCurve, 80, 1.2, 10, false);
    d.add(riverGeo);
    var riverMat = new THREE.MeshStandardMaterial({
      color: COLOR.blue, roughness: 0.25, metalness: 0.1,
      transparent: true, opacity: 0.5,
      emissive: COLOR.blue, emissiveIntensity: 0.15
    });
    d.add(riverMat);
    var river = new THREE.Mesh(riverGeo, riverMat);
    river.position.y = 0.5;
    river.position.z = -120;
    scene.add(river);
    var riverIndexCount = riverGeo.index ? riverGeo.index.count : 0;

    function update(localProg, time, mouse) {
      var lp = clamp(localProg, 0, 1);
      // camera descends Y 200 -> 80
      camera.position.y = 200 - 120 * easeInOutQuad(lp);
      camera.position.z = 800 - 120 * easeInOutQuad(lp);
      var mx = mouse ? mouse.x : 0;
      camera.position.x = mx * 14;
      camera.lookAt(0, 20, -120); // look down ~30deg toward the map

      // river "draws in" via index draw range
      if (riverIndexCount > 0) {
        riverGeo.setDrawRange(0, Math.floor(riverIndexCount * easeOutCubic(lp)));
      }

      for (var i = 0; i < pins.length; i++) {
        var pn = pins[i];
        var pr = easeOutCubic(staggerProg(lp, i * 0.07, 0.45));
        var floaty = Math.sin(time * 0.0012 + i) * 1.5;
        pn.pin.position.y = pn.base[1] + floaty;
        pn.glow.position.y = pn.base[1] + floaty;
        pn.gmat.opacity = (0.4 + Math.sin(time * 0.002 + i) * 0.08) * pr;
        pn.lmat.opacity = 0.95 * pr;
        pn.line.material.opacity = 0.6 * pr;
        pn.pin.rotation.y = time * 0.0008;
        pn.pmat.emissiveIntensity = 0.2 + 0.2 * pr;
      }
    }

    function dispose() {
      d.run();
      while (scene.children.length) scene.remove(scene.children[0]);
    }

    return { scene: scene, camera: camera, update: update, dispose: dispose };
  }

  /* ======================================================================
   * BUILDER 7 — value
   * image plane; floating valuation plane (canvas texture "$487,500",
   * Josefin Sans 700 gold on navy) scaling 0.6 -> 1.0 + soft glow.
   * ENH: <=200 upward gold data particles; pulsing vertical gold divider.
   * ==================================================================== */
  function buildValue(ctx) {
    var texture = ctx.texture, width = ctx.width, height = ctx.height;
    var params = ctx.params || {};
    var d = makeDisposer();
    var scene = new THREE.Scene();
    var camera = makePerspectiveCamera(width, height);
    camera.position.set(0, 0, 220);

    var bg = makeImagePlane(texture, width, height, { planeZ: -300, camZ: 220, pad: 1.2 });
    d.add(bg.geometry); d.add(bg.material);
    scene.add(bg.mesh);

    // Valuation canvas texture
    var valueText = params.valuation || '$487,500';
    function valuationTexture(text) {
      var c = document.createElement('canvas');
      c.width = 1024; c.height = 512;
      var g = c.getContext('2d');
      // navy backing with subtle gradient
      var grad = g.createLinearGradient(0, 0, 0, 512);
      grad.addColorStop(0, '#0A1730');
      grad.addColorStop(1, '#012169');
      g.fillStyle = grad;
      // rounded panel
      var rad = 40, x = 24, y = 24, w = 1024 - 48, h = 512 - 48;
      g.beginPath();
      g.moveTo(x + rad, y);
      g.arcTo(x + w, y, x + w, y + h, rad);
      g.arcTo(x + w, y + h, x, y + h, rad);
      g.arcTo(x, y + h, x, y, rad);
      g.arcTo(x, y, x + w, y, rad);
      g.closePath();
      g.fill();
      g.strokeStyle = 'rgba(31,105,255,0.85)';
      g.lineWidth = 5;
      g.stroke();
      // label
      g.fillStyle = 'rgba(190,202,215,0.9)';
      g.font = '700 46px "Josefin Sans", "Roboto", sans-serif';
      g.textAlign = 'center';
      g.textBaseline = 'middle';
      g.fillText('ESTIMATED VALUE', 512, 150);
      // big gold number
      g.fillStyle = '#1F69FF';
      g.shadowColor = 'rgba(31,105,255,0.6)';
      g.shadowBlur = 30;
      g.font = '700 140px "Josefin Sans", "Roboto", sans-serif';
      g.fillText(text, 512, 300);
      g.shadowBlur = 0;
      g.fillStyle = 'rgba(240,235,224,0.85)';
      g.font = '400 38px "Josefin Sans", "Roboto", sans-serif';
      g.fillText('Live Well With Coldwell', 512, 420);
      var t = new THREE.CanvasTexture(c);
      t.minFilter = THREE.LinearFilter;
      t.needsUpdate = true;
      return t;
    }
    var valTex = valuationTexture(valueText);
    d.add(valTex);
    var valGeo = new THREE.PlaneGeometry(180, 90, 1, 1);
    d.add(valGeo);
    var valMat = new THREE.MeshBasicMaterial({
      map: valTex, transparent: true, opacity: 1.0,
      depthWrite: false, side: THREE.DoubleSide
    });
    d.add(valMat);
    var valPlane = new THREE.Mesh(valGeo, valMat);
    valPlane.position.set(0, 0, -40);
    scene.add(valPlane);

    // soft glow behind valuation
    var sprite = softSpriteTexture();
    var glowMat = new THREE.SpriteMaterial({
      map: sprite, color: COLOR.gold, transparent: true, opacity: 0.35,
      depthWrite: false, blending: THREE.AdditiveBlending
    });
    d.add(glowMat);
    var glow = new THREE.Sprite(glowMat);
    glow.scale.set(280, 160, 1);
    glow.position.set(0, 0, -45);
    scene.add(glow);

    // ENH: vertical gold divider plane (pulses opacity 0.4-0.7)
    var divGeo = new THREE.PlaneGeometry(3, 260, 1, 1);
    d.add(divGeo);
    var divMat = new THREE.MeshBasicMaterial({
      color: COLOR.gold, transparent: true, opacity: 0.5,
      depthWrite: false, blending: THREE.AdditiveBlending
    });
    d.add(divMat);
    var divider = new THREE.Mesh(divGeo, divMat);
    divider.position.set(-150, 0, -50);
    scene.add(divider);

    // ENH: 200 upward gold data particles
    var PCOUNT = 200;
    var pPos = new Float32Array(PCOUNT * 3);
    var pSpeed = new Float32Array(PCOUNT);
    for (var i = 0; i < PCOUNT; i++) {
      pPos[i * 3] = (Math.random() - 0.5) * 360;
      pPos[i * 3 + 1] = (Math.random() - 0.5) * 260;
      pPos[i * 3 + 2] = -20 - Math.random() * 80;
      pSpeed[i] = 0.2 + Math.random() * 0.6;
    }
    var partGeo = new THREE.BufferGeometry();
    partGeo.setAttribute('position', new THREE.BufferAttribute(pPos, 3));
    d.add(partGeo);
    var partMat = new THREE.PointsMaterial({
      color: COLOR.gold, size: 3.5, map: sprite, transparent: true,
      opacity: 0.7, depthWrite: false, sizeAttenuation: true,
      blending: THREE.AdditiveBlending
    });
    d.add(partMat);
    var particles = new THREE.Points(partGeo, partMat);
    particles.frustumCulled = false;
    scene.add(particles);
    var partAttr = partGeo.attributes.position;

    function update(localProg, time, mouse) {
      var lp = clamp(localProg, 0, 1);
      var s = 0.6 + 0.4 * easeOutCubic(lp); // scale 0.6 -> 1.0
      valPlane.scale.set(s, s, 1);
      glow.scale.set(280 * s, 160 * s, 1);
      valMat.opacity = easeOutCubic(clamp(lp / 0.4, 0, 1));
      glowMat.opacity = (0.25 + 0.15 * Math.sin(time * 0.002)) * easeOutCubic(lp);
      // gentle float
      valPlane.position.y = Math.sin(time * 0.0009) * 4;
      glow.position.y = valPlane.position.y;
      // divider pulse 0.4 - 0.7
      divMat.opacity = 0.4 + (Math.sin(time * 0.0025) * 0.5 + 0.5) * 0.3;

      // upward data particles
      var arr = partAttr.array;
      for (var j = 0; j < PCOUNT; j++) {
        var iy = j * 3 + 1;
        arr[iy] += pSpeed[j];
        if (arr[iy] > 150) arr[iy] = -150;
      }
      partAttr.needsUpdate = true;

      var mx = mouse ? mouse.x : 0;
      var my = mouse ? mouse.y : 0;
      camera.position.x = mx * 10;
      camera.position.y = my * 6;
      camera.lookAt(0, 0, -40);
    }

    function dispose() {
      d.run();
      while (scene.children.length) scene.remove(scene.children[0]);
    }

    return { scene: scene, camera: camera, update: update, dispose: dispose };
  }

  /* ======================================================================
   * BUILDER 8 — connect (terminal, camera holds)
   * image plane (dark/warm); 40 bokeh spheres (MeshBasicMaterial gold/cream,
   * varied Z, gentle scale pulse). ENH: slow gold ring halo. No camera dolly.
   * ==================================================================== */
  function buildConnect(ctx) {
    var texture = ctx.texture, width = ctx.width, height = ctx.height;
    var d = makeDisposer();
    var scene = new THREE.Scene();
    var camera = makePerspectiveCamera(width, height);
    camera.position.set(0, 0, 0); // holds — no dolly

    var bg = makeImagePlane(texture, width, height, { planeZ: PLANE_Z, camZ: 0, pad: 1.18 });
    d.add(bg.geometry); d.add(bg.material);
    scene.add(bg.mesh);

    // 40 bokeh spheres
    var BCOUNT = 40;
    var sphereGeo = new THREE.SphereGeometry(1, 16, 12);
    d.add(sphereGeo);
    var cGold = new THREE.Color(COLOR.gold);
    var cCream = new THREE.Color(COLOR.cream);
    var cWarm = new THREE.Color(0xffe6b8);
    var bokeh = [];
    for (var i = 0; i < BCOUNT; i++) {
      var r = 3 + Math.random() * 9; // r 3-12
      var z = -50 - Math.random() * 250; // Z -50 .. -300
      var roll = Math.random();
      var baseCol = roll < 0.4 ? cGold : (roll < 0.75 ? cCream : cWarm);
      _color.copy(baseCol);
      var op = 0.08 + Math.random() * 0.10; // ~0.08 .. 0.18
      var mat = new THREE.MeshBasicMaterial({
        color: _color.getHex(), transparent: true, opacity: op,
        depthWrite: false, blending: THREE.AdditiveBlending
      });
      d.add(mat);
      var mesh = new THREE.Mesh(sphereGeo, mat);
      // spread across the frame, deeper ones wider
      var spread = 120 + (-z) * 0.9;
      mesh.position.set(
        (Math.random() - 0.5) * spread,
        (Math.random() - 0.5) * spread * 0.62,
        z
      );
      mesh.scale.setScalar(r);
      scene.add(mesh);
      bokeh.push({
        mesh: mesh, baseR: r, phase: Math.random() * Math.PI * 2,
        speed: 0.0008 + Math.random() * 0.0014
      });
    }

    // ENH: slow gold ring halo behind the closing-mark area (center)
    var haloGeo = new THREE.RingGeometry(70, 74, 96, 1);
    d.add(haloGeo);
    var haloMat = new THREE.MeshBasicMaterial({
      color: COLOR.gold, transparent: true, opacity: 0.18,
      side: THREE.DoubleSide, depthWrite: false, blending: THREE.AdditiveBlending
    });
    d.add(haloMat);
    var halo = new THREE.Mesh(haloGeo, haloMat);
    halo.position.set(0, 0, -180);
    halo.frustumCulled = false;
    scene.add(halo);

    var haloGeo2 = new THREE.RingGeometry(110, 112, 96, 1);
    d.add(haloGeo2);
    var haloMat2 = new THREE.MeshBasicMaterial({
      color: COLOR.gold, transparent: true, opacity: 0.10,
      side: THREE.DoubleSide, depthWrite: false, blending: THREE.AdditiveBlending
    });
    d.add(haloMat2);
    var halo2 = new THREE.Mesh(haloGeo2, haloMat2);
    halo2.position.set(0, 0, -190);
    halo2.frustumCulled = false;
    scene.add(halo2);

    function update(localProg, time, mouse) {
      for (var i = 0; i < bokeh.length; i++) {
        var b = bokeh[i];
        // gentle scale pulse amp 0.05 staggered phase
        var pulse = 1 + Math.sin(time * b.speed + b.phase) * 0.05;
        b.mesh.scale.setScalar(b.baseR * pulse);
      }
      halo.rotation.z = time * 0.00012;
      halo2.rotation.z = -time * 0.0001;
      haloMat.opacity = 0.14 + Math.sin(time * 0.0012) * 0.05;
      // camera holds; only the faintest mouse sway for life
      var mx = mouse ? mouse.x : 0;
      var my = mouse ? mouse.y : 0;
      camera.position.x = mx * 3;
      camera.position.y = my * 2;
      camera.lookAt(0, 0, -300);
    }

    function dispose() {
      d.run();
      while (scene.children.length) scene.remove(scene.children[0]);
    }

    return { scene: scene, camera: camera, update: update, dispose: dispose };
  }

  /* ----------------------------------------------------------------------
   * Registry + public build()
   * -------------------------------------------------------------------- */
  var BUILDERS = {
    arrival: buildArrival,
    welcome: buildWelcome,
    listings: buildListings,
    legacy: buildLegacy,
    door: buildDoor,
    communities: buildCommunities,
    value: buildValue,
    connect: buildConnect
  };

  function build(layerKey, opts) {
    opts = opts || {};
    var ctx = {
      texture: opts.texture || null,
      width: opts.width || 1280,
      height: opts.height || 720,
      params: opts.params || {}
    };
    var fn = BUILDERS[layerKey] || BUILDERS.arrival;
    return fn(ctx);
  }

  global.CBScenes = {
    build: build,
    // exposed for diagnostics / showcase tooling (not part of the core contract)
    _builders: BUILDERS,
    _noise: Noise
  };
})(typeof window !== 'undefined' ? window : this);

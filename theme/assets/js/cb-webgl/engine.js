/* ==========================================================================
 * CB Legacy — WebGL engine / compositor  (window.CBEngine)
 * --------------------------------------------------------------------------
 * Classic browser script. UMD Three.js via window.THREE only.
 * Reads earlier globals: THREE, CBShaders.
 * Attaches exactly ONE global: window.CBEngine.
 *
 * Implements CONTRACT.md §5:
 *   CBEngine.supported()
 *   CBEngine.create({ canvas, width, height, dpr })
 *   engine.registerScene(index, { scene, camera, update })
 *   engine.render({ fromIndex, toIndex, t, inTransition, transition, time,
 *                   mouse, vignette, grain, exposure })
 *   engine.resize(width, height, dpr)
 *   engine.dispose()
 *
 * Architecture:
 *   - One THREE.WebGLRenderer (antialias:true, alpha:false), pixelRatio min(dpr,2).
 *   - One WebGLRenderTarget per registered scene (lazy, depth ON), reused & resized.
 *   - A transitionRT for blend output.
 *   - An ortho fullscreen-quad compositor scene running CBShaders.transitions[name].
 *   - A post fullscreen-quad running CBShaders.post -> screen.
 *   - One ShaderMaterial per transition, created once and reused (no per-frame alloc).
 * ======================================================================== */
(function (global) {
  'use strict';

  var THREE = global.THREE;
  if (!THREE) {
    // Three.js must be present; expose a stub so callers can probe supported().
    global.CBEngine = {
      supported: function () { return false; },
      create: function () { return null; }
    };
    return;
  }

  // ---- brand tokens (used only as shader fallbacks; not invented) ----------
  // navy-mid #1B3C55, accent #1F69FF (CB Bright Blue, rerouted from gold per
  // BRAND.md) — referenced by post grade if shaders module exposes matching uniforms.

  // -------------------------------------------------------------------------
  // Capability probe
  // -------------------------------------------------------------------------
  function prefersReducedMotion() {
    try {
      return !!(global.matchMedia &&
        global.matchMedia('(prefers-reduced-motion: reduce)').matches);
    } catch (e) { return false; }
  }

  function isMobile() {
    try {
      // Narrow viewport OR coarse-only pointer => treat as mobile (fallback path).
      var narrow = (global.innerWidth || 0) > 0 && global.innerWidth < 768;
      var coarse = global.matchMedia &&
        global.matchMedia('(pointer: coarse)').matches &&
        !(global.matchMedia('(pointer: fine)').matches);
      return narrow || coarse;
    } catch (e) { return false; }
  }

  function canCreateGL() {
    try {
      var c = global.document ? global.document.createElement('canvas') : null;
      if (!c) return false;
      var gl = c.getContext('webgl2') ||
               c.getContext('webgl') ||
               c.getContext('experimental-webgl');
      if (!gl) return false;
      // Release the probe context if the extension is available.
      var lose = gl.getExtension && gl.getExtension('WEBGL_lose_context');
      if (lose && lose.loseContext) { try { lose.loseContext(); } catch (e2) {} }
      return true;
    } catch (e) { return false; }
  }

  function supported() {
    return canCreateGL() && !isMobile() && !prefersReducedMotion();
  }

  // -------------------------------------------------------------------------
  // Engine
  // -------------------------------------------------------------------------
  function Engine(opts) {
    opts = opts || {};
    var canvas = opts.canvas;
    this._width = Math.max(1, opts.width | 0 || 1);
    this._height = Math.max(1, opts.height | 0 || 1);
    this._dpr = clampDpr(opts.dpr);

    // --- renderer (one only) ---
    this.renderer = new THREE.WebGLRenderer({
      canvas: canvas,
      antialias: true,
      alpha: false,
      stencil: false,
      premultipliedAlpha: false,
      powerPreference: 'high-performance'
    });
    this.renderer.setPixelRatio(this._dpr);
    this.renderer.setSize(this._width, this._height, false);
    this.renderer.autoClear = true;
    if ('outputColorSpace' in this.renderer) {
      this.renderer.outputColorSpace = THREE.SRGBColorSpace;
    } else if ('outputEncoding' in this.renderer && THREE.sRGBEncoding) {
      this.renderer.outputEncoding = THREE.sRGBEncoding;
    }

    // --- registered FX scenes: { scene, camera, update } ---
    this._scenes = [];          // index -> {scene,camera,update}
    this._sceneRTs = [];        // index -> WebGLRenderTarget (lazy)

    // --- render-target pixel size (accounts for pixel ratio) ---
    var rtW = this._rtWidth();
    var rtH = this._rtHeight();

    // --- transition output RT ---
    this.transitionRT = makeRT(rtW, rtH, false);

    // --- ortho fullscreen quad compositor scene/cam (shared) ---
    this._quadGeo = new THREE.PlaneGeometry(2, 2);
    this._orthoCam = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);

    // resolution scratch vector (reused; no per-frame alloc)
    this._resolution = new THREE.Vector2(rtW, rtH);
    this._mouse = new THREE.Vector2(0, 0);

    // --- transition materials: one ShaderMaterial per transition, reused ---
    this._transitionScene = new THREE.Scene();
    this._transitionMats = {};   // name -> ShaderMaterial
    this._transitionMesh = new THREE.Mesh(this._quadGeo, null);
    this._transitionMesh.frustumCulled = false;
    this._transitionScene.add(this._transitionMesh);
    this._buildTransitionMaterials();

    // --- post pass quad: CBShaders.post -> screen ---
    this._postScene = new THREE.Scene();
    this._postMat = this._buildPostMaterial();
    this._postMesh = new THREE.Mesh(this._quadGeo, this._postMat);
    this._postMesh.frustumCulled = false;
    this._postScene.add(this._postMesh);

    // --- context loss guard ---
    this._contextLost = false;
    this._canvas = canvas;
    var self = this;
    this._onContextLost = function (ev) {
      if (ev && ev.preventDefault) ev.preventDefault();
      self._contextLost = true;
    };
    this._onContextRestored = function () {
      self._contextLost = false;
    };
    if (canvas && canvas.addEventListener) {
      canvas.addEventListener('webglcontextlost', this._onContextLost, false);
      canvas.addEventListener('webglcontextrestored', this._onContextRestored, false);
    }

    this._disposed = false;
  }

  // ---- helpers ------------------------------------------------------------
  function clampDpr(dpr) {
    var d = (typeof dpr === 'number' && isFinite(dpr) && dpr > 0)
      ? dpr
      : (global.devicePixelRatio || 1);
    return Math.min(d, 2);
  }

  function makeRT(w, h, depth) {
    var rt = new THREE.WebGLRenderTarget(Math.max(1, w | 0), Math.max(1, h | 0), {
      minFilter: THREE.LinearFilter,
      magFilter: THREE.LinearFilter,
      format: THREE.RGBAFormat,
      type: THREE.UnsignedByteType,
      depthBuffer: depth !== false,   // depth ON for 3D scenes by default
      stencilBuffer: false,
      depthTexture: null
    });
    rt.texture.generateMipmaps = false;
    // Intermediate RTs feed hand-rolled ShaderMaterials (transition/post) that
    // sample raw via texture2D() with NO colorSpace decode. The final post pass
    // writes to the screen where renderer.outputColorSpace = SRGB does the single
    // encode. Marking these RTs sRGB would make WebGLRenderer sRGB-ENCODE on write
    // into them, then the screen would encode AGAIN -> double sRGB -> washed out.
    // Keep intermediates linear; only the default framebuffer gets encoded.
    if ('colorSpace' in rt.texture) {
      rt.texture.colorSpace = (THREE.LinearSRGBColorSpace !== undefined)
        ? THREE.LinearSRGBColorSpace
        : (THREE.NoColorSpace !== undefined ? THREE.NoColorSpace : rt.texture.colorSpace);
    }
    return rt;
  }

  Engine.prototype._rtWidth = function () {
    return Math.max(1, Math.round(this._width * this._dpr));
  };
  Engine.prototype._rtHeight = function () {
    return Math.max(1, Math.round(this._height * this._dpr));
  };

  // -------------------------------------------------------------------------
  // Transition materials — one per transition, created once.
  // -------------------------------------------------------------------------
  Engine.prototype._buildTransitionMaterials = function () {
    var shaders = global.CBShaders;
    var vtx = (shaders && shaders.quadVertex) || DEFAULT_QUAD_VERTEX;
    var transitions = (shaders && shaders.transitions) || {};
    var names = ['landing', 'gallerySpread', 'nightFalls', 'dawnBreak',
                 'river', 'goldenMoment', 'theClose'];
    for (var i = 0; i < names.length; i++) {
      var name = names[i];
      var frag = transitions[name] || DEFAULT_TRANSITION_FRAGMENT;
      this._transitionMats[name] = new THREE.ShaderMaterial({
        uniforms: {
          uFromTexture: { value: null },
          uToTexture: { value: null },
          uProgress: { value: 0.0 },
          uTime: { value: 0.0 },
          uResolution: { value: this._resolution }
        },
        vertexShader: vtx,
        fragmentShader: frag,
        depthTest: false,
        depthWrite: false,
        transparent: false
      });
    }
    // also keep any extra transitions the shaders module defines
    for (var key in transitions) {
      if (!transitions.hasOwnProperty(key)) continue;
      if (this._transitionMats[key]) continue;
      this._transitionMats[key] = new THREE.ShaderMaterial({
        uniforms: {
          uFromTexture: { value: null },
          uToTexture: { value: null },
          uProgress: { value: 0.0 },
          uTime: { value: 0.0 },
          uResolution: { value: this._resolution }
        },
        vertexShader: vtx,
        fragmentShader: transitions[key],
        depthTest: false,
        depthWrite: false,
        transparent: false
      });
    }
  };

  // -------------------------------------------------------------------------
  // Post material — CBShaders.post. We declare every uniform the contract /
  // brief name; harmless if a uniform is unused by the actual fragment source.
  // -------------------------------------------------------------------------
  Engine.prototype._buildPostMaterial = function () {
    var shaders = global.CBShaders;
    var vtx = (shaders && shaders.quadVertex) || DEFAULT_QUAD_VERTEX;
    var frag = (shaders && shaders.post) || DEFAULT_POST_FRAGMENT;
    return new THREE.ShaderMaterial({
      uniforms: {
        uTexture: { value: null },
        uGrain: { value: 0.025 },
        uVignette: { value: 0.30 },
        uExposure: { value: 1.0 },
        uTime: { value: 0.0 },
        // bloom strength is named uBloom in CONTRACT §6 and uBloomStrength in the
        // brief; declare both so whichever the shader references resolves.
        uBloom: { value: 0.16 },
        uBloomStrength: { value: 0.16 },
        uResolution: { value: this._resolution }
      },
      vertexShader: vtx,
      fragmentShader: frag,
      depthTest: false,
      depthWrite: false,
      transparent: false
    });
  };

  // -------------------------------------------------------------------------
  // registerScene(index, { scene, camera, update })
  // -------------------------------------------------------------------------
  Engine.prototype.registerScene = function (index, cfg) {
    if (this._disposed) return;
    index = index | 0;
    if (!cfg) return;
    this._scenes[index] = {
      scene: cfg.scene || null,
      camera: cfg.camera || null,
      update: (typeof cfg.update === 'function') ? cfg.update : null
    };
    // RT is created lazily on first render of this index.
  };

  Engine.prototype._rtFor = function (index) {
    var rt = this._sceneRTs[index];
    if (!rt) {
      rt = makeRT(this._rtWidth(), this._rtHeight(), true);
      this._sceneRTs[index] = rt;
    }
    return rt;
  };

  // -------------------------------------------------------------------------
  // Render a single registered scene into its RT (runs its update()).
  // -------------------------------------------------------------------------
  Engine.prototype._renderSceneToRT = function (index, localProg, time, mouse) {
    var entry = this._scenes[index];
    var rt = this._rtFor(index);
    var renderer = this.renderer;

    if (!entry || !entry.scene || !entry.camera) {
      // Nothing registered — clear the RT to navy-dark so we never show garbage.
      renderer.setRenderTarget(rt);
      renderer.setClearColor(0x0a1730, 1); // navy-dark
      renderer.clear(true, true, false);
      renderer.setRenderTarget(null);
      return rt;
    }

    // Drive the scene's local animation. `localProg === null` is a deliberate
    // signal that the caller (main.js) has ALREADY called update() with the
    // authoritative hold-local progress this frame; re-running it here with a
    // wrong value (e.g. the transition blend `t`, which is 0 during a hold) would
    // clobber that state and freeze localProg-driven motion (door swing, dolly,
    // value scale). Only call update() when we have a real localProg to pass.
    if (entry.update && localProg !== null && typeof localProg !== 'undefined') {
      // mouse is a {x,y} in [-1,1]; pass through verbatim.
      entry.update(localProg, time, mouse);
    }

    renderer.setRenderTarget(rt);
    renderer.setClearColor(0x000000, 1);
    renderer.clear(true, true, false);
    renderer.render(entry.scene, entry.camera);
    renderer.setRenderTarget(null);
    return rt;
  };

  // -------------------------------------------------------------------------
  // render(...) — main per-frame entry. NO allocations here.
  // -------------------------------------------------------------------------
  Engine.prototype.render = function (state) {
    if (this._disposed || this._contextLost) return;
    state = state || EMPTY;

    var fromIndex = state.fromIndex | 0;
    var hasTo = (typeof state.toIndex === 'number');
    var toIndex = hasTo ? (state.toIndex | 0) : fromIndex;
    var t = clamp01(num(state.t, 0));
    // Per-scene local progress (0..1 within a hold band). CONTRACT §7: the engine
    // drives each scene's update(localProg,...). In a hold band `t` is 0 (it is the
    // transition blend, NOT the scene's local progress); using `t` would freeze
    // every localProg-driven animation (door swing, camera dolly, value scale).
    // If the caller supplies an explicit `localProg` we honor it; otherwise we
    // pass `null` so _renderSceneToRT leaves whatever update() the caller already
    // ran this frame intact (see main.js updateSceneLocals).
    var hasLocalProg = (typeof state.localProg === 'number' && isFinite(state.localProg));
    var holdLocalProg = hasLocalProg ? clamp01(state.localProg) : null;
    var inTransition = !!state.inTransition && (fromIndex !== toIndex);
    var time = num(state.time, 0);
    var exposure = num(state.exposure, 1.0);
    var grain = num(state.grain, 0.025);
    var vignette = num(state.vignette, 0.30);

    // mouse scratch (no alloc)
    var mx = 0, my = 0;
    if (state.mouse) {
      mx = num(state.mouse.x, 0);
      my = num(state.mouse.y, 0);
    }
    this._mouse.set(mx, my);

    if (inTransition) {
      // Outgoing scene at localProg ~1, incoming at ~0 (per CONTRACT §7).
      var rtFrom = this._renderSceneToRT(fromIndex, 1.0, time, this._mouse);
      var rtTo = this._renderSceneToRT(toIndex, 0.0, time, this._mouse);

      // pick transition material (reused, created once)
      var name = state.transition;
      var mat = this._transitionMats[name];
      if (!mat) {
        // unknown transition -> fall back to a straight cross-dissolve material
        mat = this._fallbackDissolveMat || (this._fallbackDissolveMat =
          this._makeFallbackDissolve());
      }
      var u = mat.uniforms;
      u.uFromTexture.value = rtFrom.texture;
      u.uToTexture.value = rtTo.texture;
      u.uProgress.value = t;
      u.uTime.value = time;
      if (u.uResolution) u.uResolution.value = this._resolution;

      this._transitionMesh.material = mat;

      // blend -> transitionRT
      this.renderer.setRenderTarget(this.transitionRT);
      this.renderer.setClearColor(0x000000, 1);
      this.renderer.clear(true, false, false);
      this.renderer.render(this._transitionScene, this._orthoCam);
      this.renderer.setRenderTarget(null);

      // detach textures from uniforms? Keep refs (RTs persist) — fine, reused.
      this._post(this.transitionRT.texture, grain, vignette, exposure, time);
    } else {
      // hold band: render only fromIndex. Pass the scene's local progress (NOT `t`,
      // which is 0 in a hold); `null` means "caller already updated this frame".
      var rt = this._renderSceneToRT(fromIndex, holdLocalProg, time, this._mouse);
      this._post(rt.texture, grain, vignette, exposure, time);
    }
  };

  Engine.prototype._post = function (texture, grain, vignette, exposure, time) {
    var u = this._postMat.uniforms;
    u.uTexture.value = texture;
    if (u.uGrain) u.uGrain.value = grain;
    if (u.uVignette) u.uVignette.value = vignette;
    if (u.uExposure) u.uExposure.value = exposure;
    if (u.uTime) u.uTime.value = time;
    if (u.uResolution) u.uResolution.value = this._resolution;

    this.renderer.setRenderTarget(null);
    this.renderer.setClearColor(0x000000, 1);
    this.renderer.clear(true, true, false);
    this.renderer.render(this._postScene, this._orthoCam);
  };

  Engine.prototype._makeFallbackDissolve = function () {
    var shaders = global.CBShaders;
    var vtx = (shaders && shaders.quadVertex) || DEFAULT_QUAD_VERTEX;
    return new THREE.ShaderMaterial({
      uniforms: {
        uFromTexture: { value: null },
        uToTexture: { value: null },
        uProgress: { value: 0.0 },
        uTime: { value: 0.0 },
        uResolution: { value: this._resolution }
      },
      vertexShader: vtx,
      fragmentShader: DEFAULT_TRANSITION_FRAGMENT,
      depthTest: false,
      depthWrite: false
    });
  };

  // -------------------------------------------------------------------------
  // resize(width, height, dpr)
  // -------------------------------------------------------------------------
  Engine.prototype.resize = function (width, height, dpr) {
    if (this._disposed) return;
    this._width = Math.max(1, width | 0 || this._width);
    this._height = Math.max(1, height | 0 || this._height);
    if (typeof dpr !== 'undefined') this._dpr = clampDpr(dpr);

    this.renderer.setPixelRatio(this._dpr);
    this.renderer.setSize(this._width, this._height, false);

    var rtW = this._rtWidth();
    var rtH = this._rtHeight();
    this._resolution.set(rtW, rtH);

    // resize all live scene RTs
    for (var i = 0; i < this._sceneRTs.length; i++) {
      var rt = this._sceneRTs[i];
      if (rt) rt.setSize(rtW, rtH);
    }
    if (this.transitionRT) this.transitionRT.setSize(rtW, rtH);

    // update registered cameras' aspect if they are perspective cameras
    for (var j = 0; j < this._scenes.length; j++) {
      var entry = this._scenes[j];
      if (entry && entry.camera && entry.camera.isPerspectiveCamera) {
        entry.camera.aspect = this._width / this._height;
        entry.camera.updateProjectionMatrix();
      }
    }
  };

  // -------------------------------------------------------------------------
  // dispose() — release every RT/geo/material/texture.
  // -------------------------------------------------------------------------
  Engine.prototype.dispose = function () {
    if (this._disposed) return;
    this._disposed = true;

    if (this._canvas && this._canvas.removeEventListener) {
      this._canvas.removeEventListener('webglcontextlost', this._onContextLost, false);
      this._canvas.removeEventListener('webglcontextrestored', this._onContextRestored, false);
    }

    // scene RTs
    for (var i = 0; i < this._sceneRTs.length; i++) {
      if (this._sceneRTs[i]) this._sceneRTs[i].dispose();
    }
    this._sceneRTs.length = 0;

    if (this.transitionRT) { this.transitionRT.dispose(); this.transitionRT = null; }

    // transition materials
    for (var key in this._transitionMats) {
      if (this._transitionMats.hasOwnProperty(key) && this._transitionMats[key]) {
        this._transitionMats[key].dispose();
      }
    }
    this._transitionMats = {};
    if (this._fallbackDissolveMat) { this._fallbackDissolveMat.dispose(); this._fallbackDissolveMat = null; }

    // post
    if (this._postMat) { this._postMat.dispose(); this._postMat = null; }

    // shared quad geometry
    if (this._quadGeo) { this._quadGeo.dispose(); this._quadGeo = null; }

    // Note: registered scenes are owned/disposed by CBScenes builders, not here.
    this._scenes.length = 0;

    if (this.renderer) {
      try { this.renderer.dispose(); } catch (e) {}
      try {
        if (this.renderer.forceContextLoss) this.renderer.forceContextLoss();
      } catch (e2) {}
      this.renderer = null;
    }
  };

  // -------------------------------------------------------------------------
  // small numeric helpers (no closures per-frame)
  // -------------------------------------------------------------------------
  function num(v, d) { return (typeof v === 'number' && isFinite(v)) ? v : d; }
  function clamp01(v) { return v < 0 ? 0 : (v > 1 ? 1 : v); }

  var EMPTY = {};

  // -------------------------------------------------------------------------
  // Default GLSL fallbacks — only used if CBShaders is missing/incomplete so
  // the engine is still runnable standalone. Real shaders come from CBShaders.
  // -------------------------------------------------------------------------
  var DEFAULT_QUAD_VERTEX = [
    'precision highp float;',
    'varying vec2 vUv;',
    'void main() {',
    '  vUv = uv;',
    '  gl_Position = vec4(position.xy, 0.0, 1.0);',
    '}'
  ].join('\n');

  var DEFAULT_TRANSITION_FRAGMENT = [
    'precision highp float;',
    'varying vec2 vUv;',
    'uniform sampler2D uFromTexture;',
    'uniform sampler2D uToTexture;',
    'uniform float uProgress;',
    'uniform float uTime;',
    '#define PI 3.14159265359',
    'void main() {',
    '  vec2 uv = clamp(vUv, 0.001, 0.999);',
    '  vec4 a = texture2D(uFromTexture, uv);',
    '  vec4 b = texture2D(uToTexture, uv);',
    '  gl_FragColor = mix(a, b, clamp(uProgress, 0.0, 1.0));',
    '}'
  ].join('\n');

  var DEFAULT_POST_FRAGMENT = [
    'precision highp float;',
    'varying vec2 vUv;',
    'uniform sampler2D uTexture;',
    'uniform float uGrain;',
    'uniform float uVignette;',
    'uniform float uExposure;',
    'uniform float uTime;',
    'float hash(vec2 p){ return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }',
    'void main() {',
    '  vec2 uv = vUv;',
    '  vec3 col = texture2D(uTexture, uv).rgb;',
    '  // exposure',
    '  col *= uExposure;',
    '  // warm grade: lift shadows toward navy-mid, push highlights toward gold',
    '  float lum = dot(col, vec3(0.299, 0.587, 0.114));',
    '  vec3 navyMid = vec3(0.106, 0.235, 0.333);',  // #1B3C55
    '  vec3 gold    = vec3(0.1216, 0.4118, 1.0);',  // #1F69FF CB Bright Blue (was gold)
    '  col = mix(col, col + navyMid * 0.12, 1.0 - smoothstep(0.0, 0.5, lum));',
    '  col = mix(col, mix(col, gold, 0.10), smoothstep(0.6, 1.0, lum));',
    '  // grain',
    '  float g = (hash(uv * 1024.0 + uTime) - 0.5) * 2.0;',
    '  col += g * uGrain;',
    '  // vignette',
    '  vec2 d = uv - 0.5;',
    '  float vig = smoothstep(0.8, 0.2, dot(d, d) * 2.0);',
    '  col *= mix(1.0, vig, uVignette);',
    '  gl_FragColor = vec4(clamp(col, 0.0, 1.0), 1.0);',
    '}'
  ].join('\n');

  // -------------------------------------------------------------------------
  // public factory
  // -------------------------------------------------------------------------
  function create(opts) {
    if (!supported()) {
      // Still allow forced creation if a canvas + context can be made; but per
      // contract create() is called after supported() gate. Guard anyway.
      if (!canCreateGL()) return null;
    }
    return new Engine(opts || {});
  }

  global.CBEngine = {
    supported: supported,
    create: create
  };

})(typeof window !== 'undefined' ? window : this);

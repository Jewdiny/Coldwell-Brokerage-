/*
 * CB Legacy — WebGL Scrolltelling :: shaders.js
 * Global: window.CBShaders
 *
 * GLSL library for the FXScene / FBO compositor (CONTRACT.md §6 +
 * BRIEF_EXCERPTS "Transition fragment shaders" + "Post pass").
 *
 * Classic browser script. No import/export, no modules. Reads only window.THREE
 * (vendored UMD r160) if present, but the strings themselves are THREE-agnostic.
 *
 * Everything here is an immutable string (GLSL source). No GL resources are
 * allocated by this module, so dispose() is a no-op kept for API symmetry and to
 * satisfy the "dispose path" contract for consumers that iterate modules.
 *
 * Common transition uniforms (set by the engine):
 *   uFromTexture (sampler2D), uToTexture (sampler2D),
 *   uProgress (float 0..1), uTime (float), uResolution (vec2)
 *
 * Shared header per transition: precision highp float; varying vec2 vUv;
 *   uniform sampler2D uFromTexture, uToTexture; uniform float uProgress, uTime;
 *   uniform vec2 uResolution; #define PI 3.14159265359
 *
 * UV samples are clamped to [0.001, 0.999] wherever a transition can sample
 * outside the texture, to avoid edge artifacts (per BRIEF).
 */
(function (root) {
  'use strict';

  /* ---------------------------------------------------------------------- *
   * Shared fullscreen-quad vertex shader.
   * Drives every transition, the post pass, and any compositor quad. Emits
   * varying vUv in [0,1]. Works for a unit PlaneGeometry rendered with an
   * orthographic camera; THREE injects projectionMatrix/modelViewMatrix/uv.
   * ---------------------------------------------------------------------- */
  var quadVertex = [
    'precision highp float;',
    'varying vec2 vUv;',
    'void main() {',
    '  vUv = uv;',
    '  gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);',
    '}'
  ].join('\n');

  /* ---------------------------------------------------------------------- *
   * Shared header prepended to every transition fragment source. Keeps the
   * uniform block, precision and PI define identical across the set so the
   * engine can bind one uniform schema for all of them.
   * ---------------------------------------------------------------------- */
  var transitionHeader = [
    'precision highp float;',
    'varying vec2 vUv;',
    'uniform sampler2D uFromTexture;',
    'uniform sampler2D uToTexture;',
    'uniform float uProgress;',
    'uniform float uTime;',
    'uniform vec2 uResolution;',
    '#define PI 3.14159265359',
    '',
    '// Clamp a UV into the safe interior to avoid bilinear edge bleeding.',
    'vec2 cbSafeUv(vec2 uv) {',
    '  return clamp(uv, vec2(0.001), vec2(0.999));',
    '}',
    ''
  ].join('\n');

  function transition(body) {
    return transitionHeader + 'void main() {\n' + body + '\n}\n';
  }

  /* ----- landing  (Scene 0→1): zoom + dissolve ----- */
  var landing = transition([
    '  vec2 centered = vUv - 0.5;',
    '  float zoom = 1.0 + uProgress * 0.12;',
    '  vec2 zoomedUv = centered / zoom + 0.5;',
    '  vec4 zoomed = texture2D(uFromTexture, cbSafeUv(zoomedUv));',
    '  float blend = smoothstep(0.4, 1.0, uProgress);',
    '  gl_FragColor = mix(zoomed, texture2D(uToTexture, cbSafeUv(vUv)), blend);'
  ].join('\n'));

  /* ----- gallerySpread  (Scene 1→2): diagonal wipe ----- */
  var gallerySpread = transition([
    '  float wipeAxis = vUv.x * 0.5 + (1.0 - vUv.y) * 0.5;',
    '  float edge = uProgress * 1.4 - 0.2;',
    '  float feather = 0.12;',
    '  float mask = smoothstep(edge - feather, edge, wipeAxis);',
    '  gl_FragColor = mix(',
    '    texture2D(uFromTexture, cbSafeUv(vUv)),',
    '    texture2D(uToTexture, cbSafeUv(vUv)),',
    '    mask',
    '  );'
  ].join('\n'));

  /* ----- nightFalls  (Scene 2→3): darken → emerge (grain spike via engine) ----- */
  var nightFalls = transition([
    '  vec4 from = texture2D(uFromTexture, cbSafeUv(vUv));',
    '  float darkFade = 1.0 - smoothstep(0.0, 0.45, uProgress) * 0.92;',
    '  vec4 darkened = vec4(from.rgb * darkFade, 1.0);',
    '  vec4 to = texture2D(uToTexture, cbSafeUv(vUv));',
    '  float emerge = smoothstep(0.45, 1.0, uProgress);',
    '  gl_FragColor = mix(darkened, to, emerge);'
  ].join('\n'));

  /* ----- dawnBreak  (Scene 3→4): warm bottom sweep + exposure flash ----- */
  var dawnBreak = transition([
    '  float sweepY = smoothstep(uProgress * 1.2 - 0.2, uProgress * 1.2, 1.0 - vUv.y);',
    '  vec4 from = texture2D(uFromTexture, cbSafeUv(vUv));',
    '  vec4 to   = texture2D(uToTexture, cbSafeUv(vUv));',
    '  vec3 warmEdge = vec3(1.0, 0.94, 0.78) * 1.6;',
    '  float edgeGlow = smoothstep(abs(sweepY - 0.5), 0.5, 0.08);',
    '  vec4 swept = mix(from, to, sweepY);',
    '  swept.rgb = mix(swept.rgb, warmEdge, edgeGlow * 0.4);',
    '  gl_FragColor = swept;'
  ].join('\n'));

  /* ----- river  (Scene 4→5): liquid horizontal wipe ----- */
  var river = transition([
    '  float waveY = vUv.y + sin(vUv.x * 8.0 + uTime * 2.0) * 0.025 * uProgress;',
    '  float wipeX = smoothstep(uProgress * 1.15 - 0.15, uProgress * 1.15, vUv.x);',
    '  gl_FragColor = mix(',
    '    texture2D(uFromTexture, cbSafeUv(vec2(vUv.x, waveY))),',
    '    texture2D(uToTexture, cbSafeUv(vUv)),',
    '    wipeX',
    '  );'
  ].join('\n'));

  /* ----- goldenMoment  (Scene 5→6): radial gold flash ----- */
  var goldenMoment = transition([
    '  float dist = distance(vUv, vec2(0.5));',
    '  float goldFlash = smoothstep(0.3, 0.0, dist) * sin(uProgress * PI) * 0.6;',
    '  vec4 from = texture2D(uFromTexture, cbSafeUv(vUv));',
    '  vec4 to   = texture2D(uToTexture, cbSafeUv(vUv));',
    '  vec4 gold = vec4(0.1216, 0.4118, 1.0, 1.0);', // CB Bright Blue #1F69FF (was gold)
    '  vec4 blended = mix(from, to, smoothstep(0.3, 0.8, uProgress));',
    '  gl_FragColor = mix(blended, gold, goldFlash);'
  ].join('\n'));

  /* ----- theClose  (Scene 6→7): scale-out dissolve (terminal) ----- */
  var theClose = transition([
    '  vec2 centered = vUv - 0.5;',
    '  float scaleOut = 1.0 - uProgress * 0.06;',
    '  vec2 scaledUv = centered / scaleOut + 0.5;',
    '  vec4 from = texture2D(uFromTexture, clamp(scaledUv, 0.001, 0.999));',
    '  vec4 to   = texture2D(uToTexture, cbSafeUv(vUv));',
    '  gl_FragColor = mix(from, to, smoothstep(0.2, 0.9, uProgress));'
  ].join('\n'));

  var transitions = {
    landing: landing,
    gallerySpread: gallerySpread,
    nightFalls: nightFalls,
    dawnBreak: dawnBreak,
    river: river,
    goldenMoment: goldenMoment,
    theClose: theClose
  };

  /* ---------------------------------------------------------------------- *
   * Post pass  (CBShaders.post) — applied every frame after compositing.
   * Order (BRIEF + CONTRACT §6):
   *   1. sample uTexture
   *   2. cheap luminance-keyed additive bloom (9-tap bright-pass, gold-weighted)
   *   3. signed film grain (hash on vUv + uTime) * uGrain
   *   4. radial vignette by uVignette
   *   5. LUT-ish grade: shadows -> navy-mid #1B3C55, highlights -> CB Bright Blue #1F69FF
   *   6. uExposure multiply (default 1.0; dawnBreak spikes to 1.4)
   *
   * Uniforms: uTexture (sampler2D), uGrain (float), uVignette (float),
   *           uExposure (float), uBloomStrength (float), uTime (float),
   *           uResolution (vec2).
   * ---------------------------------------------------------------------- */
  var post = [
    'precision highp float;',
    'varying vec2 vUv;',
    'uniform sampler2D uTexture;',
    'uniform float uGrain;',
    'uniform float uVignette;',
    'uniform float uExposure;',
    'uniform float uBloomStrength;',
    'uniform float uTime;',
    'uniform vec2 uResolution;',
    '#define PI 3.14159265359',
    '',
    '// Brand grade targets (linear-ish sRGB values).',
    'const vec3 CB_SHADOW_TINT = vec3(0.106, 0.235, 0.333);   // #1B3C55 navy-mid',
    'const vec3 CB_HIGHLIGHT_TINT = vec3(0.1216, 0.4118, 1.0); // #1F69FF CB Bright Blue (was gold)',
    'const vec3 LUMA = vec3(0.299, 0.587, 0.114);',
    '',
    '// Cheap hash for film grain. Stable per-pixel, animated by uTime.',
    'float cbHash(vec2 p) {',
    '  p = fract(p * vec2(123.34, 456.21));',
    '  p += dot(p, p + 45.32);',
    '  return fract(p.x * p.y);',
    '}',
    '',
    'vec2 cbSafeUv(vec2 uv) {',
    '  return clamp(uv, vec2(0.001), vec2(0.999));',
    '}',
    '',
    '// 9-tap bright-pass glow, gold/light weighted. Keeps cost low.',
    'vec3 cbBloom(vec2 uv) {',
    '  vec2 texel = 1.0 / max(uResolution, vec2(1.0));',
    '  float spread = 2.5;',
    '  vec3 sum = vec3(0.0);',
    '  float wsum = 0.0;',
    '  // 8 surrounding taps + center, gaussian-ish weights.',
    '  for (int i = -1; i <= 1; i++) {',
    '    for (int j = -1; j <= 1; j++) {',
    '      vec2 off = vec2(float(i), float(j)) * texel * spread;',
    '      vec3 c = texture2D(uTexture, cbSafeUv(uv + off)).rgb;',
    '      float lum = dot(c, LUMA);',
    '      // bright-pass: only keep the upper luminance band.',
    '      float bright = smoothstep(0.62, 0.95, lum);',
    '      // bias the glow toward warm/gold pixels (high R+G, lower B).',
    '      float warm = clamp((c.r + c.g) * 0.5 - c.b + 0.15, 0.0, 1.0);',
    '      float w = 1.0 - 0.18 * (abs(float(i)) + abs(float(j)));',
    '      sum += c * bright * (0.65 + 0.35 * warm) * w;',
    '      wsum += w;',
    '    }',
    '  }',
    '  return sum / max(wsum, 0.0001);',
    '}',
    '',
    'void main() {',
    '  vec2 uv = vUv;',
    '  vec3 col = texture2D(uTexture, cbSafeUv(uv)).rgb;',
    '',
    '  // 2. bloom (additive, luminance-keyed, gold-weighted).',
    '  vec3 glow = cbBloom(uv);',
    '  col += glow * uBloomStrength;',
    '',
    '  // 5. warm LUT-ish grade (applied before grain so noise stays neutral).',
    '  float lum = dot(col, LUMA);',
    '  float shadowMask = 1.0 - smoothstep(0.0, 0.45, lum);',
    '  float highMask = smoothstep(0.55, 1.0, lum);',
    '  col = mix(col, CB_SHADOW_TINT, shadowMask * 0.10);',
    '  col = mix(col, CB_HIGHLIGHT_TINT, highMask * 0.08);',
    '',
    '  // 6. exposure multiply.',
    '  col *= uExposure;',
    '',
    '  // 3. signed film grain.',
    '  float g = cbHash(uv * uResolution * 0.5 + fract(uTime) * 137.0) - 0.5;',
    '  col += g * uGrain * 2.0;',
    '',
    '  // 4. radial vignette.',
    '  vec2 vc = uv - 0.5;',
    '  float r = dot(vc, vc) * 2.0;',
    '  float vig = smoothstep(1.05, 0.25, r);',
    '  col *= mix(1.0, vig, clamp(uVignette, 0.0, 1.0));',
    '',
    '  gl_FragColor = vec4(clamp(col, 0.0, 1.0), 1.0);',
    '}'
  ].join('\n');

  /* ---------------------------------------------------------------------- *
   * Particle shaders — soft round point sprites for star fields / gold dust.
   *
   * particleVertex:
   *   attributes (per-point): aSize (float), aAlpha (float), aColor (vec3)
   *   uniforms: uPixelRatio (float), uSizeScale (float), uTime (float)
   *   varyings : vColor (vec3), vAlpha (float)
   *   gl_PointSize uses 1/-mvPosition.z perspective size attenuation.
   *
   * particleFragment:
   *   soft round falloff from gl_PointCoord; per-point alpha + color; discards
   *   fully transparent fragments so additive/normal blending stays clean.
   * ---------------------------------------------------------------------- */
  var particleVertex = [
    'precision highp float;',
    'attribute float aSize;',
    'attribute float aAlpha;',
    'attribute vec3 aColor;',
    'uniform float uPixelRatio;',
    'uniform float uSizeScale;',
    'uniform float uTime;',
    'varying vec3 vColor;',
    'varying float vAlpha;',
    'void main() {',
    '  vColor = aColor;',
    '  vAlpha = aAlpha;',
    '  vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);',
    '  // size attenuation: shrink with distance from camera.',
    '  float dist = max(-mvPosition.z, 1.0);',
    '  gl_PointSize = aSize * uSizeScale * uPixelRatio * (300.0 / dist);',
    '  gl_PointSize = max(gl_PointSize, 1.0);',
    '  gl_Position = projectionMatrix * mvPosition;',
    '}'
  ].join('\n');

  var particleFragment = [
    'precision highp float;',
    'varying vec3 vColor;',
    'varying float vAlpha;',
    'void main() {',
    '  // soft round sprite: radial gaussian-ish falloff from point center.',
    '  vec2 d = gl_PointCoord - vec2(0.5);',
    '  float r = dot(d, d) * 4.0;',         // 0 at center, 1 at edge of disc
    '  float falloff = exp(-r * 2.2);',
    '  float alpha = falloff * vAlpha;',
    '  if (alpha < 0.003) discard;',
    '  // slight core brighten for a star-like twinkle profile.',
    '  vec3 col = vColor * (0.85 + 0.55 * falloff);',
    '  gl_FragColor = vec4(col, alpha);',
    '}'
  ].join('\n');

  /* ---------------------------------------------------------------------- *
   * Public API. Strings are frozen so consumers cannot mutate shared source.
   * ---------------------------------------------------------------------- */
  var CBShaders = {
    quadVertex: quadVertex,
    transitionHeader: transitionHeader,
    transitions: transitions,
    post: post,
    particleVertex: particleVertex,
    particleFragment: particleFragment,

    // Ordered list of transition keys matching CONTRACT §4 pacing map.
    transitionOrder: [
      'landing', 'gallerySpread', 'nightFalls', 'dawnBreak',
      'river', 'goldenMoment', 'theClose'
    ],

    // No GL resources are owned here; kept for API symmetry with the contract's
    // "dispose path" requirement so a generic module-disposer can call it safely.
    dispose: function () { /* no-op: shader source strings hold no GL state */ }
  };

  if (typeof Object.freeze === 'function') {
    Object.freeze(CBShaders.transitions);
    Object.freeze(CBShaders.transitionOrder);
    Object.freeze(CBShaders);
  }

  root.CBShaders = CBShaders;
})(typeof window !== 'undefined' ? window : this);

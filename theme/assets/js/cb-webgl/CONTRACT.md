# CB Legacy — WebGL Scrolltelling Build Contract (authoritative)

This is the single source of truth for the Three.js WebGL homepage. Every module is
drafted **against this contract** so the parts compose. Source design brief:
`ColdwellBanker_WebGL_Design_Prompt.md` (the FXScene / FBO compositor architecture).
Where the brief and this contract differ, **this contract wins** (it adapts the brief
to our real theme + a headless-verifiable harness).

---

## 0. Strategy & guardrails

- **Standalone first.** Build a self-contained showcase page `cb-webgl-showcase.html`
  (lives on the Desktop, like the existing `Coldwell Preview - *.html`). It references the
  real theme assets via `file:///` and is verified with headless WebGL screenshots. The live
  WordPress homepage is **NOT touched** until the user approves the showcase. WP integration
  is delivered as a documented patch at the end, not applied mid-build.
- **Purely additive + bulletproof fallback.** If WebGL is unavailable, the device is mobile
  (`< 768px`), or `prefers-reduced-motion: reduce`, the canvas never initializes and the page
  shows the existing DOM scenes over the static AI images as CSS backgrounds. The site can
  never blank.
- **UMD Three.js only.** `window.THREE` from `theme/assets/js/vendor/three.min.js` (r160).
  No ES modules, no import maps, no `examples/jsm` (no EffectComposer/postprocessing addon —
  we hand-roll the compositor + post pass). No GSAP plugins beyond ScrollTrigger.
- **No per-frame allocations** in `update()`/`render()` (reuse vectors/colors). **Dispose**
  every RenderTarget/geometry/material in `dispose()`. Guard `webglcontextlost`.
- **Brand tokens** (do not invent colors): navy `#012169`, navy-mid `#1B3C55`,
  navy-dark `#0A1730`, blue `#1F69FF`, gold `#C9A84C`, cream `#F0EBE0`, mist `#BECAD7`,
  white `#FFFFFF`. Fonts already loaded: EB Garamond (display), Josefin Sans (labels),
  Roboto (UI), Familjen Grotesk (leads). Tagline "Live Well With Coldwell℠" stays verbatim.

---

## 1. Files (all new; create exactly these paths)

```
theme/assets/js/vendor/three.min.js            (already vendored, r160 UMD)
theme/assets/js/cb-webgl/shaders.js            -> global  window.CBShaders
theme/assets/js/cb-webgl/engine.js             -> global  window.CBEngine
theme/assets/js/cb-webgl/scenes.js             -> global  window.CBScenes
theme/assets/js/cb-webgl/cursor.js             -> global  window.CBCursor
theme/assets/js/cb-webgl/main.js               -> global  window.CBWebGL  (entry)
theme/assets/css/cb-webgl.css                  -> canvas + cursor + overlay + fallback CSS
Desktop/cb-webgl-showcase.html                 -> standalone showcase (assembled last, by lead)
```

**Load order** (scripts, all classic `<script>`, no `type=module`):
`three.min.js` → `shaders.js` → `engine.js` → `scenes.js` → `cursor.js` → `main.js`.
Each module attaches ONE global and reads only globals defined earlier in this order.

---

## 2. Asset map (already downloaded, self-hosted)

`theme/assets/images/webgl/<slug>.jpg`, all 2048×1170 JPG, brand-color-conditioned (Recraft):

| idx | slug | image content | mood |
|----|------|---------------|------|
| 0 | `01-arrival` | aerial golden-hour San Angelo, Concho River | bright/warm |
| 1 | `02-welcome` | luxury home exterior, porch, oaks, blue sky | bright |
| 2 | `03-listings` | luxury property collage / grid of homes | bright |
| 3 | `04-legacy` | Milky Way over West Texas, lone lit estate | **dark** |
| 4 | `05-door` | navy double doors, brass hardware, stone arch | mid |
| 5 | `06-communities` | aerial neighborhoods + Lake Nasworthy, sunset | bright/warm |
| 6 | `07-value` | couple + SOLD sign, champagne, staged home | bright/warm |
| 7 | `08-connect` | agent handing keys, handshake, warm bokeh interior | **dark/warm** |

Showcase references them as `file:///C:/Users/simeo/OneDrive/Desktop/Coldwell%20Site%20File/theme/assets/images/webgl/<slug>.jpg`.
WP build will reference `CB_THEME_URI . '/assets/images/webgl/<slug>.jpg'`.

---

## 3. Scene registry (defined in main.js, consumed by engine/scenes)

`CBWebGL` owns `SCENES` — an array of 8 configs, index = scene order:

```js
{
  slug: '01-arrival',
  image: '<resolved url>',          // set at init from a base path
  dark: false,                       // affects cursor color + postFX vignette/grade
  vignette: 0.30,                    // 0.25–0.55
  grain: 0.025,                      // 0.015–0.04
  layer: 'arrival',                  // which CBScenes builder to use (see §7)
  // optional per-scene tuning consumed by the builder:
  params: { /* builder-specific */ }
}
```

The 8 `layer` builder keys, in order:
`arrival, welcome, listings, legacy, door, communities, value, connect`.

---

## 4. Pacing map (global progress 0..1 over 600vh)

`main.js` computes `globalProgress` (0 at top, 1 at bottom). It derives, for any progress:
`{ fromIndex, toIndex, t, inTransition }` where `t` is 0..1 transition blend.

Scene "hold" bands and transition bands (from the brief):

| progress | state | transition shader |
|----------|-------|-------------------|
| 0.00–0.18 | Scene 0 hold | — |
| 0.18–0.22 | 0→1 | `landing` (zoom + dissolve) |
| 0.22–0.35 | Scene 1 hold | — |
| 0.35–0.38 | 1→2 | `gallerySpread` (diagonal wipe) |
| 0.38–0.50 | Scene 2 hold | — |
| 0.50–0.53 | 2→3 | `nightFalls` (darken→emerge, grain spike) |
| 0.53–0.63 | Scene 3 hold | — |
| 0.63–0.66 | 3→4 | `dawnBreak` (warm bottom sweep + exposure flash) |
| 0.66–0.74 | Scene 4 hold | — (doors open on scroll within hold) |
| 0.74–0.77 | 4→5 | `river` (liquid horizontal wipe) |
| 0.77–0.86 | Scene 5 hold | — |
| 0.86–0.88 | 5→6 | `goldenMoment` (radial gold flash) |
| 0.88–0.94 | Scene 6 hold | — |
| 0.94–0.96 | 6→7 | `theClose` (scale-out dissolve) |
| 0.96–1.00 | Scene 7 hold | — |

In a hold band: `inTransition=false`, render only `fromIndex`. In a transition band:
`inTransition=true`, `t = (progress - bandStart)/(bandEnd - bandStart)`.

A per-scene **local progress** (0..1 across that scene's hold band) is also passed to the
builder's `update()` so scenes can self-animate (door swing, counters, camera dolly).

---

## 5. Engine API — `window.CBEngine`

```js
// Capability probe (called before any GL work). Returns true if we should run WebGL.
CBEngine.supported(): boolean   // WebGL2 or WebGL1 context creatable, not mobile, not reduced-motion

// Construct once. opts: { canvas, width, height, dpr, scenes:[{three.Scene, camera, update}] }
const engine = CBEngine.create({ canvas, width, height, dpr });

// FXScene: engine builds one RenderTarget per scene lazily; you give it scene+camera.
engine.registerScene(index, { scene, camera, update })   // update(localProg, time, mouse)

// Per frame. Renders only the needed FXScene(s), runs the active transition shader if
// inTransition, then the post pass (grain/vignette/LUT/bloom-lite) to the screen.
engine.render({ fromIndex, toIndex, t, inTransition, transition, time, mouse,
                vignette, grain, exposure })

engine.resize(width, height, dpr)
engine.dispose()
```

**Internals the engine owns:**
- One `THREE.WebGLRenderer` (antialias:true, alpha:false), `setPixelRatio(min(dpr,2))`.
- `WebGLRenderTarget` per scene (LinearFilter, RGBAFormat, no stencil/depth-texture; depth
  buffer ON for 3D scenes). Created lazily, reused, resized on `resize`.
- A `transitionRT` (scene-blend output) and the compositor fullscreen-quad scene/cam (ortho).
- A post-process fullscreen quad reading the composited texture, applying
  `CBShaders.post` (grain + vignette + warm LUT-ish grade + light bloom approximation).
- Render path:
  - if `inTransition`: render scene[from]→rtFrom, scene[to]→rtTo, run
    `CBShaders.transitions[transition]` quad with uFromTexture=rtFrom, uToTexture=rtTo,
    uProgress=t, uTime, → transitionRT; then post(transitionRT)→screen.
  - else: render scene[from]→rtFrom; post(rtFrom)→screen.
- Update `uTime` each frame **unless capture mode froze it** (main passes a fixed `time`).

---

## 6. Shaders — `window.CBShaders`

```js
CBShaders.quadVertex            // shared fullscreen-quad vertex shader (varying vUv)
CBShaders.transitions = {
  landing, gallerySpread, nightFalls, dawnBreak, river, goldenMoment, theClose
}                                // each = GLSL fragment source string
CBShaders.post                  // post fragment source (uTexture,uGrain,uVignette,uExposure,uTime)
CBShaders.particleVertex / particleFragment   // soft round point sprite for star/dust fields
```

**Common transition uniforms** (engine sets these): `uFromTexture` (sampler2D),
`uToTexture` (sampler2D), `uProgress` (float 0..1), `uTime` (float), `uResolution` (vec2).
`#define PI 3.14159265359`. Precision `highp float`. Each fragment writes `gl_FragColor`.

Implement the 7 transitions from the design brief **verbatim in intent** (the brief gives the
exact GLSL for landing/gallerySpread/nightFalls/dawnBreak/river/goldenMoment/theClose — use it,
fixing only obvious compile issues and clamping UVs to avoid edge sampling artifacts).

**Post pass** (`CBShaders.post`), applied every frame after compositing:
1. sample `uTexture`.
2. light bloom approx: add a few-tap bright-pass blur of the texture * `uBloom` (0.12–0.22),
   weighted toward gold/light pixels. Keep it cheap (≤9 taps) — or a simple luminance-keyed
   additive glow.
3. film grain: hash(vUv + uTime) * `uGrain`, signed, added to rgb.
4. vignette: `smoothstep` radial darken by `uVignette`.
5. warm LUT-ish grade: lift shadows toward `#1B3C55`, push highlights toward `#C9A84C`
   (mix in luminance bands). Subtle — unify the 8 images under one identity.
6. `uExposure` multiply (default 1.0; brief spikes it during dawnBreak).

---

## 7. Per-scene 3D layers — `window.CBScenes`

```js
// Each builder returns { scene:THREE.Scene, camera:THREE.PerspectiveCamera,
//                        update(localProg, time, mouse), dispose() }
// All share: a fullscreen-ish textured PlaneGeometry carrying the scene image (the
// "background layer"), sized so it fills the 55° FOV at the plane's Z with cover-fit.
CBScenes.build(layerKey, { texture, width, height, params }) -> sceneObj
```

Builders (CORE = must-have, ENH = enhancement if time/perf allow). Geometry/material counts
are budget ceilings.

- **arrival** — CORE: image plane + slow camera dolly (z 600→350 across localProg) + gentle
  y-bob; gold **dust particles** (`THREE.Points`, ≤3000, soft sprite, drift). ENH: 3 blurred
  parallax planes reacting to `mouse` (x offsets ×0.015/0.030/0.055); faint tilted GridHelper.
- **welcome** — CORE: image plane; 4 gold marker **spheres** at varied Z with bloom billboards,
  animate in (y +80→0 staggered via localProg). ENH: warm spotlight cone on a thin fog plane.
- **listings** — CORE: image plane; **6 card planes** in a gentle arc (curving back), float-in
  by localProg, arc rotates y 0→−15° across the hold. ENH: small price-tag sphere per card.
- **legacy** — CORE (signature): image plane (dark); **star field** `THREE.Points` ≤12000 on a
  sampled sphere, very slow y-rotation; **Milky-Way band** ≤4000 softer points. ENH: 4 thin gold
  **ring** orbits at radii 80/120/160/200 rotating at different speeds.
- **door** — CORE (signature): image plane; **two BoxGeometry door panels** (navy
  MeshPhysicalMaterial, brass cylinder handles) hinged on outer edges; swing open
  (rotation.y ∓ 0.55π) driven by localProg 0..1; a warm PointLight behind viewer ramps 0→1.5
  as they open. ENH: simple raised-panel canvas texture on the doors.
- **communities** — CORE: image plane; **6 gold pins** (spheres) at fixed map positions with
  thin line to a name label plane; camera descends (y 200→80). ENH: low-seg displaced terrain
  plane (≤64×48) tinted navy 15%; river **TubeGeometry** drawing in by localProg.
- **value** — CORE: image plane; **floating valuation plane** (canvas texture "$487,500",
  Josefin Sans 700 gold on navy) scaling 0.6→1.0 + soft glow. ENH: ≤200 upward gold data
  particles; a luminous vertical gold divider plane that pulses opacity.
- **connect** — CORE: image plane (dark/warm); **40 bokeh spheres** (MeshBasicMaterial, gold/
  cream, varied Z, gentle scale pulse). ENH: slow gold ring halo behind the closing-mark area.
  Terminal scene: camera holds (no dolly).

`mouse` is `{x,y}` in [-1,1] (from main; 0,0 in capture mode). `localProg` is 0..1 within the
scene's hold band (clamped; during transitions the outgoing scene gets localProg≈1, incoming≈0).

---

## 8. Cursor — `window.CBCursor`

Desktop pointer only (skip if coarse pointer / touch / reduced-motion / capture mode).
- Hides native cursor (`* { cursor:none }` added by `cb-webgl.css`, but only under
  `html.cb-webgl-on`).
- Dot: 6px gold, instant. Ring: 32px, 1.5px gold border @50%, lerp 0.10.
- Hover `.cb-btn, a, .cb-property-card, [data-cursor]`: ring scale 1.6, dot fades, optional
  label ("View"/"Open") from `data-cursor`.
- On dark scenes (main toggles `document.documentElement.dataset.cbDark = '1'`): ring/dot
  shift to cream/white. `CBCursor.setDark(bool)`.
- `CBCursor.init()`, `CBCursor.destroy()`.

---

## 9. Capability + fallback — owned by `main.js`

```js
CBWebGL.init({ basePath, canvas, capture })   // entry; basePath resolves image urls
```
- Gate: `if (!CBEngine.supported() || isMobile() || reducedMotion()) { return; /* DOM/CSS fallback stays */ }`.
- On success: add `html.cb-webgl-on` (CSS hides DOM `.cb-stage-bg` media + native cursor and
  lets the canvas show); init engine + scenes + cursor; wire Lenis + ScrollTrigger to update
  `globalProgress`; start RAF loop → compute `{from,to,t,inTransition,transition}` from the
  pacing map → `engine.render(...)`; toggle dark-scene cursor + postFX per current scene.
- Keep document scroll (no scrollerProxy). The canvas is `position:fixed; inset:0; z-index:0`.
  DOM `.cb-scroll-scenes` sits above (z-index:2) with `pointer-events` only on interactive
  children so the canvas receives mouse for parallax.

---

## 10. Capture / verification harness (REQUIRED — this is how we screenshot)

`main.js` parses `location.search`:
- `?cb_capture=1` → **capture mode**: do NOT init Lenis; freeze `uTime` to a constant (2.0);
  set `mouse={0,0}`; read `cb_progress` (float 0..1, default 0) and force `globalProgress` to
  it; render exactly the matching state once via `engine.render(...)`; then render a second
  time on next tick after textures load. Expose `window.__cbReady = true` and set
  `document.title = 'CB_READY'` once the frame with all textures loaded has drawn.
- `?cb_progress=0.52` → the forced progress (works with capture).
- This makes any scene/transition deterministically screenshot-able:
  `cb_capture=1&cb_progress=0.20` → mid `landing`; `0.58` → Scene 3 legacy; `0.70` → doors
  mid-open; `0.87` → goldenMoment; etc.

Texture readiness: preload all 8 images (THREE.TextureLoader) and only set `__cbReady`/title
after the `cb_progress` frame's scenes' textures report loaded. Screenshots use a generous
`--virtual-time-budget`; readiness flag is the contract for "frame is correct".

---

## 11. WordPress integration (DOCUMENT ONLY — do not apply during build)

Write `theme/assets/js/cb-webgl/WP_INTEGRATION.md` describing:
- `functions.php` (inside `is_front_page()`): enqueue `three.min.js`, then the 5 cb-webgl
  modules (correct dependency chain), and `cb-webgl.css`; localize `basePath` =
  `CB_THEME_URI.'/assets/images/webgl/'` via `wp_localize_script('cb-webgl-main', 'CB_WEBGL', [...])`.
- `front-page.php`: add `<canvas id="cb-webgl" aria-hidden="true"></canvas>` as first child of
  `.cb-scroll-stage`; keep existing `.cb-stage-bg` (becomes the fallback, hidden under
  `html.cb-webgl-on`). No copy changes.
- Confirm the existing reduced-motion / no-JS / mobile fallbacks still hold.

---

## 12. Definition of done (lead verifies via headless screenshots)

- `cb-webgl-showcase.html` renders each of the 8 scenes (`cb_progress` ≈ 0.08, 0.28, 0.44,
  0.58, 0.70, 0.81, 0.91, 0.98) with the correct image + 3D layer visible and legible.
- Each of the 7 transitions shows a sensible blend at its mid-progress.
- No black/blank frames; postFX (grain/vignette/warm grade) visibly unifies the set.
- Fallback: with `cb_capture` off and WebGL disabled, DOM scenes + CSS images still show.

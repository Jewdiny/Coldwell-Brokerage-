# WordPress integration plan — WebGL cinematic homepage

**Status: NOT YET APPLIED.** The WebGL layer was built + verified as a standalone showcase
(`Desktop/Coldwell WebGL Showcase.html`) so the live theme stays safe. Apply the steps below
only after the showcase look is approved. Each step is additive and reversible.

## Verified so far (headless, software-WebGL/SwiftShader)
- Engine renders end-to-end: **arrival** (bright, gold-dust particles), **connect** (dark, bokeh)
  and the **full showcase hero** (canvas + DOM overlay) all render with the cinematic post grade.
- Heavier scenes (legacy 16k-point star field, door PBR panels) exceed the *software* renderer's
  memory on this machine, so they couldn't be screenshot here — they render fine on a real GPU.
  **The real validation is opening the showcase in a normal browser and scrolling.**
- Three real fixes were applied to the generated modules: (1) hold-band `localProg` now flows
  main→engine; (2) texture `crossOrigin` only set for cross-origin http (was breaking file:// and
  was needless same-origin); (3) **scenes build lazily** (only the 1–2 visible scenes exist at
  once) — a memory/perf win on every device, required under software WebGL.

## Key architectural decision
The homepage already ships a CSS/GSAP "cinematic" scroll system gated on `html.cb-cinematic`
(driven by `home.js`). WebGL must **replace** that system on capable desktops, not run alongside it
(two scroll controllers would fight). So:

- On a WebGL-capable desktop → set `html.cb-webgl-on` **instead of** `html.cb-cinematic`.
  `home.js` already early-returns when `.cb-cinematic` is absent, so it stays dormant (it only
  wires the Property-Watch form). `CBWebGL` drives the visuals from document scroll.
- Mobile / reduced-motion / no-WebGL → neither class; the existing stacked DOM + static
  `assets/images/webgl/*.jpg` (or the current `scroll/*` plates) remain the fallback. Never blanks.

## 1. `functions.php` — inside the existing `if (is_front_page())` block
```php
// WebGL cinematic layer (vendored Three.js + modules). Order matters.
wp_enqueue_script('three',        CB_THEME_URI.'/assets/js/vendor/three.min.js', [], '0.160.0', true);
wp_enqueue_script('cb-wg-shaders', CB_THEME_URI.'/assets/js/cb-webgl/shaders.js', ['three'], CB_THEME_VERSION, true);
wp_enqueue_script('cb-wg-engine',  CB_THEME_URI.'/assets/js/cb-webgl/engine.js',  ['three','cb-wg-shaders'], CB_THEME_VERSION, true);
wp_enqueue_script('cb-wg-scenes',  CB_THEME_URI.'/assets/js/cb-webgl/scenes.js',  ['three','cb-wg-engine'], CB_THEME_VERSION, true);
wp_enqueue_script('cb-wg-cursor',  CB_THEME_URI.'/assets/js/cb-webgl/cursor.js',  ['three'], CB_THEME_VERSION, true);
wp_enqueue_script('cb-wg-main',    CB_THEME_URI.'/assets/js/cb-webgl/main.js',    ['three','cb-wg-shaders','cb-wg-engine','cb-wg-scenes','cb-wg-cursor'], CB_THEME_VERSION, true);
wp_enqueue_style('cb-webgl',       CB_THEME_URI.'/assets/css/cb-webgl.css', ['cb-scroll-home'], CB_THEME_VERSION);
wp_add_inline_script('cb-wg-main',
  "window.CBWebGL&&window.CBWebGL.init({basePath:'".esc_js(CB_THEME_URI."/assets/images/webgl/")."',canvas:'#cb-webgl'});",
  'after');
```
Same-origin images → no crossOrigin needed (the loader already handles this). No CDN.

## 2. `front-page.php` — add the canvas as the first child of `.cb-scroll-stage`
```php
<div class="cb-scroll-stage" id="cb-scroll-stage" data-cb-scroll>
  <canvas id="cb-webgl" aria-hidden="true"></canvas>   <!-- NEW: WebGL backdrop -->
  <div class="cb-stage-bg" aria-hidden="true"> ... existing plates stay as the fallback ... </div>
  ...
```
No copy changes. The existing `data-scene="N"` sections already match the 8-scene order
(arrival, welcome, listings, legacy, door, communities, value, close) the registry expects.
Add the 8 `webgl/*.jpg` images (already in `assets/images/webgl/`) — used as the WebGL textures.

## 3. `header.php` — capability class
In the inline head script that currently sets `cb-cinematic`, branch: if WebGL is creatable AND
desktop AND motion-allowed → add `cb-webgl-on` and DO NOT add `cb-cinematic`. Otherwise keep the
current behavior. (A tiny canvas `getContext('webgl2')||getContext('webgl')` probe is enough.)
`main.js` re-checks support and bails safely if the probe was optimistic.

## 4. Scene-content backgrounds
`cb-webgl.css` already hides `.cb-stage-bg` and manages pointer-events under `.cb-webgl-on`.
Confirm the scenes' own DOM backgrounds are transparent in this mode (the showcase adds a subtle
radial scrim behind copy for legibility — port that rule into `cb-webgl.css` if the live scenes
have opaque atmospheres).

## 5. Verify on the live homepage
Desktop GPU: all 8 scenes + 7 transitions, smooth scroll, header still toggles, no `position:fixed`
breakage, custom cursor, dark-scene cursor flip. Mobile/reduced-motion/no-JS: stacked fallback,
static images, native cursor. Lighthouse + cross-browser.

## Optional later: paid AI video transitions
The "end-frame → image→video → next scene" idea is a separate, credit-costing layer on top of the
free GLSL shader transitions. Decide after reviewing the shader transitions in the showcase.

---

## APPLIED: live preview via the "Home 2" page (front page left untouched)
Instead of editing `front-page.php` (steps 1–3 above), the WebGL homepage was shipped as a SAFE,
un-linked **page template** so it can be reviewed on the real domain first. The live front page is
unchanged. Files:

- `templates/template-home2-webgl.php` — Page Template "Home 2 — Cinematic WebGL Preview"; `noindex`.
- `template-parts/home-scenes.php` — the 8-scene stage extracted from `front-page.php` (faithful
  copy), emits `<canvas id="cb-webgl">` when `$GLOBALS['cb_home_webgl']` is set. **front-page.php still
  has its own inline copy** — when the WebGL homepage is approved, switch front-page.php's stage markup
  to `get_template_part('template-parts/home-scenes')` (set the flag first) and the two stay in sync.
- `functions.php` — `CB_HOME2_TEMPLATE` const; `cb_enqueue_home2_webgl()` (enqueues the stack on that
  template only); `cb_home2_capability_script()` (the head-phase mode arbiter); a `wp_sitemaps` exclusion;
  GA suppressed on the preview; body classes.
- `header.php` — transparent header also on this template (1 additive, guarded line).
- `assets/css/cb-webgl.css` — **§6 added** (scene transparency + legibility scrim + light copy + footer
  z-index lift), all scoped under `html.cb-webgl-on` so non-WebGL pages are untouched. This completes the
  "port the showcase text-scrim" TODO.

### Mode arbitration (the critical guarantee)
`header.php` sets `html.cb-cinematic` for every capable desktop. On the preview page,
`cb_home2_capability_script()` runs later in `<head>` and, when WebGL + desktop(≥1025px) + motion + fine
pointer all hold, **removes `.cb-cinematic`** so `home.js` (Version A) stays dormant; `main.js` then adds
`.cb-webgl-on` only after the engine truly starts. Result — exactly one scroll controller, never a
double-Lenis fight:
- WebGL-capable desktop → Version B (Three.js canvas).
- Desktop, no WebGL → Version A (GSAP/CSS scroll over the real San Angelo photos).
- Mobile / reduced-motion / no-JS → clean stacked layout.
`main.js` now creates the engine inside a try/catch and only adds `.cb-webgl-on` on success, so a
probe-passes-but-renderer-fails device degrades to the stacked layout instead of a blank screen.

### How to publish the preview
1. Upload the changed theme files (list below) to the server, preserving paths.
2. WP Admin → **Pages → Add New** → title **Home 2** (slug becomes `home-2`).
3. **Page Attributes → Template → "Home 2 — Cinematic WebGL Preview"** → **Publish**.
4. Visit `/home-2/`. It is `noindex`, in no menu, and excluded from the core sitemap. (If Yoast is
   active, also tick **noindex** on the page so it's dropped from the Yoast sitemap too.)
5. To take it down: unpublish or delete the "Home 2" page — nothing else is affected.

### Files to upload
```
theme/templates/template-home2-webgl.php          (new)
theme/template-parts/home-scenes.php              (new)
theme/functions.php                               (changed)
theme/header.php                                  (changed)
theme/assets/css/cb-webgl.css                     (changed)
theme/assets/js/cb-webgl/main.js                  (changed — fail-safe engine init)
theme/assets/js/cb-webgl/  (shaders/engine/scenes/cursor.js)   (if not already on server)
theme/assets/js/vendor/three.min.js               (if not already on server)
theme/assets/css/scroll-home.css, assets/js/page-animations/home.js, assets/js/vendor/lenis.min.js
theme/assets/images/webgl/*.jpg                   (the 8 scene textures, if not already on server)
```

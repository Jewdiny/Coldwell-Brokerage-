# Coldwell Banker Legacy — Session Handoff & Next Steps

**Project:** CB_Legacy_Luxury WordPress theme · homes-sanangelo.com · San Angelo, TX
**Working dir:** `c:\Users\simeo\OneDrive\Desktop\Coldwell Site File`
**Higgsfield credits:** 178 (starter plan; max 2 concurrent jobs; AI garbles text → keep signs/docs blank)
**Brand:** consult `theme/BRAND.md` before brand-affecting changes. Tagline "Live Well With Coldwell℠".

This session produced TWO complete versions of the cinematic homepage. Nothing is deployed live yet.

---

## VERSION A — Real-San-Angelo CSS/video scroll homepage  (DONE, verified)

Real, recognizable San Angelo landmarks over the existing scroll engine. 0 new credits (free
Wikimedia Commons photos + the 3 AI clips already paid for). Assets in `theme/assets/images/scroll/`:

| Scene | File | Source |
|---|---|---|
| 1 arrival (hero) | `01-arrival.jpg` | Downtown skyline w/ Cactus Hotel — Commons, M. Barera, CC BY-SA 4.0 |
| 2 welcome | `02-welcome.jpg` | E. Concho Avenue — CC BY-SA 4.0 |
| 3 listings | `03-listings.mp4`+`.jpg` | AI home orbit (kling) |
| 4 legacy | `04-legacy.jpg` | Cactus Hotel 1929 tower — CC BY-SA 4.0 |
| 5 buy/door | `05-door.jpg` | Mason-Hughes House (real Victorian) — L.D. Moore, CC BY 4.0 |
| 6 communities | `06-communities.jpg` | Concho River — CC BY-SA 4.0 |
| 7 value | `07-value.jpg` | Sacred Heart Cathedral — CC BY-SA 4.0 |
| 8 close | `08-close.mp4`+`.jpg` | AI aerial farewell (veo3) |

- Replaced an AI "for-sale sign" clip that rendered GARBLED text → Mason-Hughes House.
- All stills cinematically color-graded (ungraded originals in `scroll/_src/_ungraded/`).
- CC attribution block added to `front-page.php` (`.cb-photo-credits`) + styled in `scroll-home.css`.
- Preview to open: **`Desktop/Coldwell Preview - Homepage.html`**

---

## VERSION B — Full WebGL/Three.js rebuild  (BUILT, hero verified; YOU must review on a real GPU)

From `ColdwellBanker_WebGL_Design_Prompt.md`. Three.js FBO/FXScene compositor with 7 GLSL
transitions, 3D layers per scene (dust, parallax, star field, swinging doors, terrain+pins,
bokeh), custom cursor, post-processing grade. Uses the **8 Recraft 2K images already in your
Higgsfield account** (cost ~64 cr earlier; that's why balance is 178). 0 new credits this build.

**New files created:**
- `theme/assets/js/vendor/three.min.js` (Three.js r160 UMD)
- `theme/assets/js/cb-webgl/shaders.js` — `CBShaders` (7 transitions + post + particles)
- `theme/assets/js/cb-webgl/engine.js` — `CBEngine` (FBO compositor)
- `theme/assets/js/cb-webgl/scenes.js` — `CBScenes` (8 scene builders)
- `theme/assets/js/cb-webgl/cursor.js` — `CBCursor`
- `theme/assets/js/cb-webgl/main.js` — `CBWebGL` (registry, scroll pacing, capture harness)
- `theme/assets/css/cb-webgl.css`
- `theme/assets/images/webgl/01-arrival.jpg … 08-connect.jpg` (2048px Recraft images)
- `theme/assets/js/cb-webgl/CONTRACT.md`, `BRIEF_EXCERPTS.md`, `WP_INTEGRATION.md`
- **`Desktop/Coldwell WebGL Showcase.html`** ← the deliverable to open
- `Desktop/cb-webgl-harness.html` (verification harness: `?cb_capture=1&cb_progress=0..1`)

**3 real bugs found & fixed during debugging:**
1. Hold-band `localProg` now flows main→engine (doors/dolly/counters were frozen).
2. Texture `crossOrigin` only set for cross-origin http (was breaking file:// + needless same-origin).
3. **Lazy scene building** — only the 1–2 visible scenes are built at once (memory/perf win; was
   building all 8 → software-renderer out-of-memory).

**Verified rendering (headless):** arrival hero (gold dust), connect (dark + bokeh), and the FULL
showcase hero (canvas + overlay). Heavier scenes (legacy 16k-point stars, door PBR) exceed this
machine's *software* WebGL (SwiftShader) memory — they render fine on any real GPU.

**Headless screenshot recipe (for future debugging):**
`msedge --headless=new --use-gl=angle --use-angle=swiftshader --enable-unsafe-swiftshader
--ignore-gpu-blocklist --allow-file-access-from-files --disable-web-security --no-sandbox
--hide-scrollbars --window-size=1280,720 --virtual-time-budget=12000 --user-data-dir=<UNIQUE>
--screenshot=<out> <file-url>` — needs unique profile dir per launch + cooldown; kill leftover
headless msedge between runs (machine is memory-tight).

---

## NEXT STEPS / TO-DOS

### ▶ YOU (now)
- [ ] Open **`Desktop/Coldwell WebGL Showcase.html`**, scroll top→bottom. Judge look/feel of the
      8 scenes + 7 transitions on your real GPU. (Also compare `Coldwell Preview - Homepage.html`
      = Version A.)
- [ ] Decide direction: **WebGL (Version B)** as the desktop experience with **Version A as the
      mobile/no-WebGL fallback** is the intended architecture — confirm that's what you want.

### ▶ CLAUDE (after your review)
- [ ] Apply WordPress integration per `theme/assets/js/cb-webgl/WP_INTEGRATION.md`:
      enqueue Three.js+modules in `functions.php` (is_front_page block); add `<canvas id="cb-webgl">`
      to `front-page.php`; set `html.cb-webgl-on` (instead of `cb-cinematic`) in `header.php` on
      capable desktops; verify fallback still clean.
- [ ] Optional polish: reduce legacy star count 12k→~8k for low-end devices; port the showcase's
      text scrim into `cb-webgl.css` if live scenes have opaque backgrounds.
- [ ] DECISION: paid AI **video transitions** ("end-frame → image→video → next scene"). Separate
      credit cost (~5–8 cr/clip) on top of the free GLSL transitions. Scope only if you want it.

### ▶ DEPLOYMENT (blocked on info)
- [ ] How do you deploy to homes-sanangelo.com? (FTP/SFTP, cPanel file manager, git, host login)
      Need this to push the theme. Nothing is live yet.
- [ ] Scene 3 (Featured Listings) pulls `[cb_listings filter="featured"]` from Flexmls/Spark MLS —
      needs the **MLS invoice active** (was due 2026-06-16) to show real listings.

### ▶ STILL OPEN / EARLIER WORK
- Zillow-style find-a-home map restyle (from an earlier session) — re-verify with live MLS data.
- Hero CTA buttons were removed per earlier request (satisfied by dedicated Buy/Sell scenes).

# Homepage Cinematic Storyboard & Generation Brief
**Coldwell Banker Legacy — San Angelo, TX**

---

## FINAL ASSET LOG (2026-06-16) — what actually shipped

Direction shifted to **real, recognizable San Angelo locations** (hometown feel)
and **minimal credit spend**. There are no "unlimited"/free generation models on
the starter plan (cheapest still = 2 cr, cheapest video ≈ 5 cr), so most scene
backgrounds use **free, properly-licensed Wikimedia Commons photos as stills** —
the engine's scroll dolly-push animates them at **0 credits**. Files live in
`assets/images/scroll/` and auto-wire by name (`%02d-slug`).

| # | Scene | Asset shipped | Source / license |
|---|-------|---------------|------------------|
| 1 | Arrival (hero) | `01-arrival.jpg` Downtown **skyline** (still, depth-zoom) | Commons, M. Barera, CC BY-SA 4.0 |
| 2 | Welcome | `02-welcome.jpg` **E. Concho Avenue** (still) | Commons, M. Barera, CC BY-SA 4.0 |
| 3 | Listings | `03-listings.mp4` AI home exterior orbit (kling2_6) | Higgsfield (already paid) |
| 4 | Legacy | `04-legacy.jpg` **Cactus Hotel** 1929 tower (still) | Commons, M. Barera, CC BY-SA 4.0 |
| 5 | Buy / door | `05-door.jpg` **Mason-Hughes House** (real local Victorian) | Commons, L. D. Moore, CC BY 4.0 |
| 6 | Communities | `06-communities.jpg` **Concho River Walk** (still) | Commons, M. Barera, CC BY-SA 4.0 |
| 7 | Sell / value | `07-value.jpg` **Sacred Heart Cathedral** (still) | Commons, M. Barera, CC BY-SA 4.0 |
| 8 | Connect (close) | `08-close.mp4` AI golden-hour aerial over the city (veo3_1_lite) | Higgsfield (already paid) |

- All six stills get a subtle **filmic color grade** (ffmpeg: medium-contrast
  curve + teal-shadow/warm-highlight balance); ungraded originals kept in
  `assets/images/scroll/_src/_ungraded/`.
- Attribution is required by CC and lives in a `.cb-photo-credits` block at the
  bottom of `front-page.php` (footer.php untouched). Keep it on the page.
- **Retired:** the AI "for-sale sign" push-in clip rendered **garbled sign text**
  (AI can't spell) and was pulled to `_src/_retired-05-sign.mp4`; replaced by the
  real Mason-Hughes House.
- **Deferred (needs new credits):** the signature **sign→signing match-cut**
  (asset type B) and the `cb-scrub` engine module are not built — the page is
  complete and shippable without them. Revisit only if the user funds more video.

---

A scroll-driven film. The viewer descends from the sky over San Angelo, moves
through a neighborhood, finds a home, watches the deal close at the title
company, explores the valley, tours a home, and ends on a family getting their
keys. Scroll **is** the camera: every beat moves because the user moves.

---

## Asset types (each scene is one of these)

- **A · Ambient MP4** — a short looping clip with its own slow camera move,
  plays while the scene is on screen. The engine adds a scroll-linked **dolly
  push** on top, so scrolling drives the camera further. *(Built — works today.)*
- **B · Scrubbed MP4 match-cut** — the scene is **pinned** and the video's
  playback position is tied to scroll. Used for the signature
  *sign -> signing* transition: two clips stacked, the first scrubs as you scroll
  in, a match-dissolve hands off to the second, which scrubs as you scroll out.
  *(Needs the `cb-scrub` engine module — I'll build it; see "Engine work".)*
- **C · Layered "explainer"** — a still hero plate plus separate foreground
  elements (tiles, numbers, a seal, pins) that **fall / slide / settle into
  place** as you scroll, with a slow pull-back. *(Needs the `cb-assemble`
  module — I'll build it.)*

### How scroll drives the camera, per type
| Type | What scrolling does |
|---|---|
| A | Clip plays; engine scales the plate (dolly-in) tied to scroll progress |
| B | Scroll position = video time; you literally push in / pull out by scrolling |
| C | Scroll progress assembles the layers and pulls the framing outward |

---

## Engine status
- [DONE] Per-scene background **video** (auto play/pause on the active scene)
- [DONE] Scroll-linked **camera push** (dolly) on every scene
- [DONE] Cross-scene **crossfade** + readable scrim + reduced-motion/no-JS fallback
- [TO BUILD] **`cb-scrub`** — pinned two-clip scrubbed match-cut (Scene 5)
- [TO BUILD] **`cb-assemble`** — layered parts-fall-into-place sequence (Scenes 4 & 6 accents)

---

## Asset specs (all clips/stills)
- **Video:** MP4 (H.264), **1080p**, **24fps**, **6-12s**, slow continuous motion,
  **no on-screen text / no watermark**, graded warm golden-hour with deep navy
  shadows. Target **< 8 MB** each (compress at freeconvert.com). Provide a poster
  frame (same name `.webp`) for fast load.
- **Stills (explainer plates / posters):** WEBP, ~2560px wide, < 350 KB.
- **Drop location:** `theme/assets/images/scroll/` using the **exact file names**
  below. The scene picks them up automatically.
- **Brand text caveat:** AI garbles lettering. Generate signs/documents **blank**
  in Coldwell **navy (#012169) + white**; we overlay the real "Coldwell Banker
  Legacy" logo/text as a crisp PNG/SVG in the engine. Never rely on AI to spell.

---

## The film, scene by scene

### Scene 1 — ARRIVAL · over San Angelo · `01-arrival.mp4` · **Type A**
- **On screen:** H1 headline, "Live Well With Coldwell(SM)", scroll cue.
- **Camera / scroll:** high golden-hour aerial descending and pushing forward
  along the Concho River toward downtown; scrolling deepens the descent.
- **Transition OUT -> 2:** the descent continues and cross-dissolves to street
  level (we've "landed").
- **Prompt (Seedance 2.0 / Veo 3.1):**
  > Cinematic aerial drone shot at golden hour descending over a small West Texas
  > city, the Concho River winding through downtown, warm low sun and soft haze,
  > mesquite and pecan trees, brick rooftops and church steeples, slow forward
  > push with a gentle descent, photorealistic, atmospheric depth, subtle film
  > grain, no text, no watermark, 24fps, anamorphic, shallow horizon haze.

### Scene 2 — WELCOME · the neighborhood · `02-welcome.mp4` · **Type A**
- **On screen:** "At home in San Angelo." + 4 action cards (Find / Sell / Team / Office).
- **Camera / scroll:** slow forward dolly down a pecan-canopy residential street,
  dappled light; scrolling glides further down the street.
- **Transition IN <- 1:** dissolve from the aerial as it reaches rooftop height.
  **OUT -> 3:** the glide settles and cross-dissolves to a single home's facade.
- **Prompt (Kling 3.0 / Veo 3.1):**
  > Cinematic slow forward dolly down a quiet upscale residential street in West
  > Texas at golden hour, mature pecan tree canopy arching over the road, brick
  > and limestone family homes, manicured lawns, warm dappled sunlight, gentle
  > steady glide, photorealistic, shallow depth of field, no people, no text, no
  > watermark, 24fps, filmic warm grade.

### Scene 3 — LISTINGS · a home for sale · `03-listings.mp4` · **Type A**
- **On screen:** live MLS featured cards + "View All Properties".
- **Camera / scroll:** slow crane-and-orbit around a beautiful Texas home exterior;
  scrolling continues the orbit, light raking across the brick.
- **Transition IN <- 2:** arrive at the facade. **OUT -> 4:** ease toward the front
  of the house (sets up the for-sale sign in Scene 5).
- **Prompt (Kling 3.0 / Seedance 2.0):**
  > Cinematic slow crane-and-orbit around the exterior of a beautiful single-story
  > Texas brick-and-limestone home at golden hour, manicured lawn, large windows
  > catching warm light, gentle lens flare, photorealistic, shallow depth of
  > field, smooth steady camera, no people, no readable text, no watermark, 24fps.

### Scene 4 — LEGACY · the numbers assemble · `04-legacy.webp` (+ layers) · **Type C**
- **On screen:** "A legacy of results." + the 4 stat counters (Homes Sold, Agents,
  Years, Communities).
- **Camera / scroll:** dark cinematic office backdrop; as you scroll, four stat
  tiles **drop and settle into place** one after another, numbers **count up**,
  and a subtle Coldwell seal fades in while the framing **pulls back**.
- **Transition IN <- 3:** a blueprint-style wipe builds the dark stage.
  **OUT -> 5:** the tiles recede; cut to black, then the sign push-in begins.
- **Prompt (background still — Nano Banana Pro / FLUX.2):**
  > Cinematic dark navy interior of a modern real-estate office at dusk, soft
  > bokeh of agents working in the far background, deep blue tones with a faint
  > warm key light, premium and editorial, generous empty space for overlaid
  > numbers, photorealistic, shallow depth of field, no text, no watermark, 16:9.
- **Foreground layers (we build, no AI needed):** 4 stat tiles + count-up + seal.

### Scene 5 — THE DEAL · For-Sale sign -> signing at the title company · **Type B (signature)**
*Two clips, scroll-scrubbed, joined by a match-cut. This is the centerpiece.*
- **Files:** `05-door-a.mp4` (push-in) + `05-door-b.mp4` (pull-out) + poster `05-door.webp`.
- **On screen:** a single line — "From *For Sale* to *Sold*." + buy CTA
  ("Start your search" -> /find-a-home/). Minimal, so the footage leads.
- **Camera / scroll (pinned):**
  - 0-45% scroll -> **clip A scrubs**: extreme slow push-in into a navy "For Sale"
    yard sign until the deep blue fills the frame.
  - ~50% -> **match-cut**: the blue sign dissolves into a blue closing folder
    (matched color & shape) — *the zoom appears to continue through the cut.*
  - 55-100% scroll -> **clip B scrubs**: starts on a hand signing, then **pulls
    back and cranes up** to reveal a couple + agent **shaking hands** across the
    title-company table.
- **Transition IN <- 4 / OUT -> 6:** opens from black; releases on the handshake,
  cross-dissolving up to the aerial valley.
- **Prompt A (push-in — Kling 3.0 / Veo 3.1):**
  > Cinematic extreme slow push-in toward a navy-blue real-estate yard sign on a
  > green lawn in front of a Texas home at golden hour, the deep blue sign panel
  > growing to fill the frame, shallow depth of field, warm rim light, smooth
  > dolly, photorealistic, no readable text, no watermark, 24fps. End on the flat
  > blue panel filling frame.
- **Prompt B (signing -> pull-back handshake — Veo 3.1, top realism):**
  > Cinematic shot beginning as an extreme close-up of a navy-blue document folder
  > and a hand signing papers with a pen, then slowly pulling back and craning up
  > to reveal a happy young couple and a professional real-estate agent smiling
  > and shaking hands across a modern title-company conference table; soft natural
  > window light, house keys and documents on the table, warm and emotional,
  > photorealistic, shallow depth of field, no readable text, no watermark, 24fps.
  > Start on the flat blue folder filling frame to match the previous shot.
- **Why two clips:** current AI video does one continuous shot well but not a
  mid-clip match-cut. Generating A and B with **matched blue framing** lets the
  engine dissolve them seamlessly — and lets scroll drive the whole move.

### Scene 6 — COMMUNITIES · the valley opens up · `06-communities.mp4` · **Type A + C accent**
- **On screen:** 6 community cards + "Explore All Communities".
- **Camera / scroll:** high aerial gliding over Lake Nasworthy & the valley with a
  slow **pull-back**; as it widens, the **Concho River line draws itself** and
  **community pins drop** into place (the "things falling into place / pulling
  outward" beat).
- **Transition IN <- 5:** rise from the handshake into the sky. **OUT -> 7:**
  descend toward a single home and dissolve to its interior.
- **Prompt (Seedance 2.0 / Veo 3.1):**
  > Cinematic high aerial drone glide over a West Texas lake and surrounding
  > neighborhoods at golden hour, calm reservoir with a few boats, lakeside homes,
  > rolling ranch land beyond, warm light, slow pull-back revealing the wider
  > valley, photorealistic, atmospheric haze, no text, no watermark, 24fps.

### Scene 7 — HOME VALUE · the tour · `07-value.mp4` · **Type A**
- **On screen:** "What's my home worth?" + Get-My-Value CTA + Property Watch.
- **Camera / scroll:** smooth gimbal **walk-through** of a warm staged interior —
  foyer -> living -> kitchen; scrolling moves you **through the rooms**, then a
  gentle pull-back.
- **Transition IN <- 6:** dissolve through a window into the room. **OUT -> 8:**
  glide to the front door from inside and dissolve to the family outside.
- **Prompt (Veo 3.1 / Kling 3.0):**
  > Cinematic smooth gimbal walk-through of a warm, beautifully staged modern Texas
  > home interior at golden hour — entry foyer opening into an open-concept living
  > room and kitchen, large windows with warm light, wood floors, tasteful
  > furnishings, slow forward glide then a gentle pull-back, photorealistic,
  > shallow depth of field, no people, no text, no watermark, 24fps.

### Scene 8 — THE KEYS · a family home · `08-close.mp4` · **Type A**
- **On screen:** testimonials (Testimonial Tree) + latest blog + brand sign-off
  ("Live Well With Coldwell(SM) · At home in San Angelo").
- **Camera / scroll:** a happy family in front of their new home at dusk, agent
  handing over keys, interior lights glowing; slow warm **push-in**; scrolling
  eases the push to a resting frame, then releases to the footer.
- **Transition IN <- 7:** dissolve from the interior to the family outside at dusk.
- **Prompt (Veo 3.1, top realism):**
  > Cinematic warm shot of a happy young family standing in front of their new
  > Texas home at dusk, a friendly real-estate agent handing house keys to them,
  > golden interior lights glowing through the windows, deep blue twilight sky,
  > soft rim light, emotional and aspirational, slow gentle push-in,
  > photorealistic, shallow depth of field, no text, no watermark, 24fps.

---

## Recommended generation order & rough budget
Generate in story order so you can preview the cut as it fills in. Model picks:
- **Drone/aerial** (1, 6): Seedance 2.0 or Veo 3.1
- **Camera-controlled push/dolly** (2, 3, 5A): Kling 3.0
- **Human realism / emotional** (5B, 8): Veo 3.1 (highest fidelity)
- **Interior tour** (7): Veo 3.1 or Kling 3.0
- **Stills** (4 plate, posters): Nano Banana Pro or FLUX.2

Ballpark cost (Higgsfield credits): ~8-17 credits per clip -> **~90-150 credits**
for all 9 clips, plus a few stills. (Veo 3.1 Lite 8s ~ 8; Kling 5s ~ 10;
Seedance fast ~ 17.) Free-stock substitutes exist for 1, 2, 3, 6, 7; the two that
really want AI are **5B (signing->handshake)** and **8 (keys/family)**.

---

## Engine work to do (so assets are plug-and-play)
1. **`cb-scrub`** — pinned, scroll-scrubbed two-clip match-cut for Scene 5
   (load `05-door-a/-b.mp4`, map scroll->`currentTime`, crossfade at the match
   point, overlay the real CB sign logo). Reduced-motion -> static poster.
2. **`cb-assemble`** — layered parts-fall-into-place sequence for Scene 4 (stat
   tiles) and the Scene 6 accent (river draw + pins), driven by scroll progress.
3. Optional **scroll-scrubbed single video** flag for any Type-A scene that should
   be scroll-driven rather than ambient.

Once you (a) generate/drop the clips, or (b) green-light me to source free-stock
stand-ins, I build #1 and #2 and wire each scene to its file.

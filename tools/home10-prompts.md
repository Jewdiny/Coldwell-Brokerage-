# Home 10 — the generation chain

Source of truth for how the Home 10 plates and transitions are prompted. Home 10
has no geometry: the house IS these files, so "fix the navy" or "fix the doors"
means changing a prompt here and regenerating, not editing a material.

Model choices, and why:

| step | model | why |
|---|---|---|
| stills | `nano_banana_pro` @ 2k, 16:9 | photoreal interiors, and accepts a reference image so every room inherits the hallway's palette |
| transitions | `seedance_2_0` @ 1080p, 5s, silent | the only strong image-to-video model here that takes **both** `start_image` and `end_image` |

---

## 1. The chain

`STILL[i]` is section *i*'s resting frame. `TRANS[i]` is the walk that **arrives
at** section *i*. Every transition is pinned on both ends:

```
TRANS[i]   : STILL[i-1] ──> STILL[i]
TRANS[i+1] : STILL[i]   ──> STILL[i+1]
```

`start_image` **and** `end_image` are both mandatory. With `start_image` alone
the clip ends wherever the model likes, so every room arrives somewhere slightly
different from where the next walk departs, and every section boundary jumps.

| # | still | transition into it |
|---|---|---|
| 0 | `01-hall` | `t0-intro` — in the front door, settle looking down the hall (4s, see below) |
| 1 | `02-living` | `t1-living` |
| 2 | `03-gallery` | `t2-gallery` |
| 3 | `04-study` | `t3-study` |
| 4 | `05-entry` | `t4-entry` |
| 5 | `06-dining` | `t5-dining` |
| 6 | `07-kitchen` | `t6-kitchen` |
| 7 | `08-hearth` | `t7-hearth` |

`00-approach` is not a section; it is only `t0-intro`'s `start_image`.

---

## 2. THE DOOR RULE — read before touching the hallway plate

**The first cut of the hallway plate showed no side doorways.** The camera then
had to invent what was on the side walls the moment it moved, and it invented
doors: white four-panel doors materialising out of blank navy, several at once,
different ones in each clip. That is the "duplicate doors" and "doors popping
in" problem, and no amount of prompt-wrangling on the *transition* fixed it,
because the transition was not where it came from.

A video model interpolating between two frames will invent anything the frames
do not commit to. So the plate has to commit:

1. **The hallway plate must already contain every opening the walk goes
   through.** Both side walls carry wide **open cased doorways** — openings with
   moulded architraves and **no door leaves at all**.
2. **Exactly one actual door in the house-wide establishing shot**, closed, at
   the far end of the hall. A door leaf that exists is a door leaf that can be
   duplicated; openings cannot be.
3. **Daylight spills out of each opening onto the hallway floor.** This is not
   decoration — it is what tells the model the openings are real depth and not
   painted panels, and it stops them being "closed up" mid-move.

Then every transition prompt carries the **invariance clause**:

> The architecture is fixed and already exists in the first frame. Do not add,
> remove, duplicate, open or close any door or doorway. Every doorway the camera
> passes through is an existing open cased opening with no door leaf. Only the
> camera moves.

---

## 3. Transition prompt template

```
A single continuous first-person walking shot with no cuts. The camera backs
smoothly out of the {FROM}, into the navy panelled hallway, continues forward
along the hallway, then turns through an existing open doorway and comes to
rest facing into the {TO}.

The architecture is fixed and already exists in the first frame. Do not add,
remove, duplicate, open or close any door or doorway. Every doorway the camera
passes through is an existing open cased opening with no door leaf. Only the
camera moves.

Perfectly smooth constant-speed gimbal motion, no camera shake, no people, no
text. Photorealistic interior architectural cinematography, consistent warm
daylight throughout.
```

### t0-intro is different, and it is the hard one

It is the only clip whose two ends are both hallway views, and it has now
overshot twice: both cuts flew the entire corridor and finished pressed against
the far door, having grown two flanking door leaves that are nowhere in the
plate. Negative distance instructions did NOT fix it — "stays NEAR THE START",
"does NOT travel down it", "the door at the far end stays far away and small"
were all in the second attempt's prompt and all ignored.

What that tells us: **seedance weights `end_image` loosely and its walking bias
strongly.** Told "first-person" plus a corridor, it walks, and 5s of screen time
is time it will fill with travel whatever the prompt says. So fight it on those
two axes instead of by adding more prohibitions:

- **Shorten the clip.** 4s is the floor (`duration: 3` clamps to 4). Less time
  to fill is less distance travelled.
- **Deny the walking frame outright** — "a locked-off architectural shot with
  the faintest breath of movement, NOT a walking shot" rather than "walk, but
  only a little". A small number in a walking instruction still reads as walk.
- **Describe the end COMPOSITION in words**, so the prose and the pinned
  `end_image` are pulling the same direction: pendant at top centre, an open
  cased doorway filling each edge, runner leading away, far door small and
  distant. Prohibitions constrain nothing the model can aim at; a described
  frame does.

If it still overshoots, stop buying credits: **the intro does not need a
generative clip at all.** Both its ends are the same view, so a programmatic
push-in on `01-hall.jpg` (a CSS/JS transform ramp in home10.js) is smoother than
anything the model will produce, costs nothing, and cannot invent a door. Video
earns its keep on t1–t7, where the camera genuinely changes rooms.

---

## 4. Gotchas

- **Preset hijack.** `generate_video` sometimes returns a recommendation for the
  Higgsfield preset "IN THE DARK" *instead of* a job. It is triggered by the
  door language in the invariance clause above ("open or close any door",
  "no door leaf") — so it fires on essentially every transition now, not just
  the hearth one where it first showed up. It is a notice, not an error: the job
  was never submitted. Resend the identical request with
  `declined_preset_id: "24bae836-2c4a-48e0-89b6-49fcc0b21612"`. Adding "bright
  warm daylight" to the tail makes it less likely to match.
- **Stuck jobs.** One still sat `queued` for ~20 min and never moved; an
  identical reissue completed in under a minute. Reissue rather than wait.
- **End frames are approximate — and this is now fixed in the build, not here.**
  A clip lands *toward* its pinned `end_image`, not on it: 7% (mean abs channel
  difference) on a good room walk, 27% on the intro. That matters more than it
  sounds, because home10.js never swaps in a still — it parks the video on its
  final frame for the whole dwell, so whatever the model landed on IS the room
  the reader sits in, and the next clip then starts from the plate.

  `build-home10.mjs` now cross-dissolves each clip's last 0.4s into its own
  destination plate and holds it 0.25s. Every clip therefore ends on exactly the
  frame the next one begins on. Measured after: park frame within **2/255** of
  its plate (compression noise), section 0→1 seam down from 68/255 to 12/255.

  So do NOT burn credits re-rolling a clip because its landing is soft — the
  build pins it for free. Re-roll only for what happens *during* the walk:
  overshoot, invented doors, a camera that goes somewhere wrong.

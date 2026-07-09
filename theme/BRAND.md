# Coldwell Banker Brand Reference

Source of truth for all Coldwell Banker brand requirements applied to homes-sanangelo.com (DBA: Coldwell Banker Legacy, San Angelo). **Consult before any brand-affecting change** (colors, fonts, logos, copy, disclaimers).

Reference templates: https://www.canva.com/brand/brand-templates/EAG6qQDrZvw · https://www.canva.com/brand/brand-templates/EAHC7LvleCk · https://www.canva.com/brand/brand-templates/EAHC7DoIPH0

---

## 1. Color Palette (Refined)

### Primary Colors

| Name | HEX | RGB | PMS | Use |
|---|---|---|---|---|
| **CB Blue** | `#012169` | 1 / 33 / 105 | 280C | Signature brand color — logo, primary CTAs, headers |
| **Smoky Gray** | `#58718D` | 88 / 113 / 141 | 5425C | Secondary surfaces, dividers, secondary text |
| **Midnight** | `#0A1730` | 10 / 23 / 48 | 289C | Dark hero overlays, footer, navy depth |
| **Slate** | `#1B3C55` | 27 / 60 / 85 | 5405C | Mid-tone navy, secondary sections |
| **Mist** | `#BECAD7` | 190 / 202 / 215 | 5435C | Light backgrounds, hover states |
| **Tide** | `#B8CFEA` | 184 / 207 / 234 | 544C | Soft blue accents, button hover |
| **Glacier** | `#DAE1E8` | 218 / 225 / 232 | 5455C | Very light section backgrounds |
| **Icy Blue** | `#F0F5FB` | 240 / 245 / 251 | 656C | Off-white sections |
| **Black** | `#000000` | — | — | Body copy, bold headlines |
| **White** | `#FFFFFF` | — | — | Whitespace-first foundation |

### Secondary / Accent Colors

| Name | HEX | RGB | PMS | Use |
|---|---|---|---|---|
| **Bright Blue** | `#1F69FF` | 31 / 105 / 255 | 285C | Energetic accents, modern highlights |
| **Celestial** | `#418FDE` | 65 / 143 / 222 | 279C | Mid-blue links, secondary CTAs |

### Palette Guidance (verbatim from CB Brand Kit)

> "Coldwell Banker Blue is our signature color. White space is incorporated generously for a clean, contemporary look and easy readability. Black is our go-to for body copy and bold headlines. Our signature blue nods to our heritage. Deeper shades add richness and depth. Brighter accents introduce a modern, forward-looking edge."

**Rule of thumb:** CB Blue + lots of white + Midnight/Slate for depth. Use Bright Blue + Celestial sparingly as energy accents.

---

## 2. Typography

### Primary Font (Titles, Subtitles, Headings)

**Familjen Grotesk** — free Google Fonts alternative to the proprietary **Bauziet** (CB hero font).
- Sans-serif, contemporary appearance
- Large "ink trap" notches add style at display sizes
- Use for: page H1s, section titles, hero headlines, marketing headers
- Weights: 400, 500, 600, 700

### Subheader Font

**Josefin Sans** — free alternative to the proprietary Geometos.
- Geometric, vintage-inspired
- Best at larger sizes (subheadings, eyebrow labels)
- Weights: 300, 400, 500, 600

### Body Copy

**Roboto** — official CB body face.
- "Whisper to a shout" weight range
- Use for: paragraph body, navigation, listing cards, form labels, footer
- Weights: 300, 400, 500, 700

### Accent Serif

**EB Garamond** — for subheads, captions, initial caps, numbers, accents.
- Classic serif that softens tone and adds elegance
- Use sparingly for: pull quotes, agent intros, luxury-tier callouts
- Weights: 400, 500, 600

### Font CSS variable convention

```css
--font-heading:    'Familjen Grotesk', 'Bauziet', -apple-system, system-ui, sans-serif;
--font-subheader:  'Josefin Sans', 'Familjen Grotesk', sans-serif;
--font-body:       'Roboto', -apple-system, system-ui, 'Segoe UI', sans-serif;
--font-accent:     'EB Garamond', 'Garamond', Georgia, serif;
```

If/when official Bauziet/Geometos `.woff2` files are obtained from the CB franchise portal, drop them into `theme/assets/fonts/` and add `@font-face` declarations — the variable chain will pick them up automatically.

---

## 3. Logo Assets

All 10 official lockups are in [theme/assets/images/logos/](assets/images/logos/):

| File | Lockup type | When to use |
|---|---|---|
| `monogram.svg` | Just the monogram mark | Favicons, small avatars, social profiles |
| `monogram-horizontal.svg` | Monogram + horizontal text | Default header logo |
| `monogram-horizontal-stacked.svg` | Monogram + horizontal + DBA below | When showing the local DBA ("Legacy" / "San Angelo") |
| `monogram-vertical.svg` | Monogram above name | Portrait formats, stationery |
| `monogram-vertical-stacked.svg` | Monogram + DBA, vertical | Tall hero placements |
| `framed.svg` | Mark in frame | Watermarks, badges |
| `framed-horizontal.svg` | Framed horizontal lockup | Footer secondary mark |
| `framed-horizontal-stacked.svg` | Framed horizontal + DBA | Email signatures, biz cards |
| `framed-vertical.svg` | Framed vertical | Print sidebars |
| `framed-vertical-stacked.svg` | Framed vertical + DBA | Signage, posters |

**MANDATORY DBA usage:** Always feature the **Coldwell Banker® DBA logo** (with "Coldwell Banker" written beneath the monogram) on all marketing pieces. The "Coldwell Banker Legacy" DBA portion must stay visible.

---

## 4. Brand Voice

Three pillars:

**WARMTH** — Speak with authority but stay friendly and clear, avoiding jargon.
**EXPERTISE** — Show fresh thinking and forward momentum, inspiring curiosity.
**CONFIDENCE** — Uplift every voice and celebrate diversity, making everyone feel welcome.

Tone direction:
- "We've always understood the meaning of home. That sense of comfort, connection, and authenticity will always be at the heart of who we are."
- Refined, elevated, but warm
- Clarity, confidence, sophistication — never stuffy

---

## 5. "Live Well With Coldwell" Campaign

**Tagline:** Live Well With Coldwell℠ (always uses service mark SM on first use per page)

**DOs:**
- Always feature the **Coldwell Banker® DBA logo** alongside the "Live Well With Coldwell" tagline
- Use only approved lockups (horizontal or stacked) available on CB Desk
- Add `℠` service mark **on first instance** of "Live Well With Coldwell" on any page
- May add a local-market descriptive line ("At home in San Angelo.") — visually separate from tagline

**DON'Ts:**
- Do NOT remove "Banker" from the company name in materials outside Live Well references
- Do NOT alter the lockup font, color, or arrangement
- Do NOT treat this as a rebrand — we're still Coldwell Banker; this is a campaign lens
- Do NOT couple the tagline with other words on the same line/font — always visually separated

---

## 6. Required Disclaimers

These are NON-OPTIONAL whenever the Coldwell Banker® mark appears.

### Footer / general (every page on the site)

> ©2025 Coldwell Banker. All Rights Reserved. Coldwell Banker and the Coldwell Banker logos are trademarks of Coldwell Banker Real Estate LLC. The Coldwell Banker® System is comprised of company owned offices which are owned by a subsidiary of Anywhere Advisors LLC and franchised offices which are independently owned and operated. The Coldwell Banker System fully supports the principles of the Fair Housing Act and the Equal Opportunity Act.

Plus this short line:
> **Each office is independently owned and operated.**

### Materials distributed to consumers

> Not intended as a solicitation if your property is already listed by another broker.

### Stationery / signage

> Each office is independently owned and operated.

### Broadcast comms

> Each office is independently owned and operated. Coldwell Banker is a registered service mark owned by Coldwell Banker Real Estate LLC.

**Implementation note:** the long footer disclaimer should appear in the site footer with the copyright line. Add the solicitation disclaimer to home-valuation form pages and recently-sold pages (any page where we're inviting people to list).

---

## 7. Testimonial Tree Integration

CB-approved testimonial syndication. Office credentials provided 2026-06-14:

```
API Key:  556b19de-ddbc-4f37-a81a-2a9defd60a3d
Username: cbltexas
Password: NewStart2025!
```

⚠ **Treat password as secret** — do NOT commit to git. Store in wp_options via `update_option('cb_testimonialtree_password', ...)` and access via `get_option()`. Same for username and API key if we end up calling their REST API directly.

### Widget IDs

| Widget | ID | Use |
|---|---|---|
| **Rotator** | `71288` | Homepage testimonial section (auto-rotating carousel) |
| **List** | `71289` | Dedicated /testimonials/ page (full list) |

### Embed code (rotator, homepage)

```html
<div id="TestimonialTree_Widget_71288"></div>
<script type="text/javascript" src="https://application.testimonialtree.com/api/v1/widgets/script?widgetid=71288"></script>
```

### Embed code (list, testimonials page)

```html
<div id="TestimonialTree_Widget_71289"></div>
<script type="text/javascript" src="https://application.testimonialtree.com/api/v1/widgets/script?widgetid=71289"></script>
```

### Per-agent filtering (single-agent pages)

Append `&email=<agent.email>` to pull reviews for that one agent:

```html
<div id="TestimonialTree_Widget_71288"></div>
<script type="text/javascript" src="https://application.testimonialtree.com/api/v1/widgets/script?widgetid=71288&email=amanda.mayer@cbltexas.com"></script>
```

On single-agent pages, read `agent_email` post meta and feed it into the `email=` parameter.

---

## 8. Brand DBA — Local Identity

| Field | Value |
|---|---|
| Legal | Coldwell Banker Legacy |
| DBA Web Brand | Coldwell Banker Legacy — San Angelo |
| Office Address | 3017 Knickerbocker, San Angelo, TX 76904 |
| Phone | (325) 944-9559 |
| Email | info@homes-sanangelo.com |
| Web | https://homes-sanangelo.com |
| Coordinates | 31.4377, -100.4503 |

---

## 9. Quick checklist before publishing brand-affecting changes

- [ ] Colors match the palette in §1 (use CSS variables, not raw hex)
- [ ] Headings use `--font-heading` (Familjen Grotesk)
- [ ] Body uses `--font-body` (Roboto)
- [ ] Logo is one of the 10 official lockups, including DBA
- [ ] "REALTOR" always carries ® (use `&reg;`)
- [ ] "Live Well With Coldwell" carries ℠ on first instance
- [ ] Footer disclaimer present + "Each office is independently owned and operated."
- [ ] Voice: warmth + expertise + confidence — never stuffy
- [ ] No alterations to CB approved lockups

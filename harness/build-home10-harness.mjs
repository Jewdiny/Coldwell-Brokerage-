/* =========================================================================
   build-home10-harness.mjs -- generate harness/cb-home10-harness.html

     node harness/build-home10-harness.mjs

   Home 10 is Home 9's CONTENT with a different world behind it, so the eight
   panels are lifted verbatim out of the Home 9 harness rather than retyped.
   That keeps the two variants honestly comparable: if the copy changes in one,
   regenerate and it changes in both.

   The inner markup keeps its cb9-* classes on purpose. cb-home9.css styles all
   the components inside a panel (headings, cards, buttons) and its floating
   rules are nested under html.cb9-on, which this page never sets -- so loading
   it gives us the FLAT component styling for free with no floating layout
   attached. cb-home10.css then only has to own the shell: the fixed stage, the
   panel positioning, and the nav.
   ========================================================================= */
import { readFileSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';

const HARNESS = resolve(import.meta.dirname);
const SRC = join(HARNESS, 'cb-home9-harness.html');
const OUT = join(HARNESS, 'cb-home10-harness.html');

const src = readFileSync(SRC, 'utf8');

/** Pull out a balanced <section class="cb9-page" ...> ... </section> block. */
function extractSections(html) {
  const out = [];
  const open = /<section class="cb9-page"[^>]*>/g;
  let m;
  while ((m = open.exec(html))) {
    let i = m.index, depth = 0, k = i;
    const tag = /<\/?section\b/g;
    tag.lastIndex = i;
    let t;
    while ((t = tag.exec(html))) {
      if (html[t.index + 1] === '/') { depth--; } else { depth++; }
      if (depth === 0) { k = html.indexOf('>', t.index) + 1; break; }
    }
    out.push(html.slice(i, k));
    open.lastIndex = k;
  }
  return out;
}

// Section order, and therefore nav-dot order. Communities sits at 3 and the
// old "Value" section is gone -- its Property Watch signup became a pop-up.
// Keep in step with $cb10_nav in theme/template-parts/home10-filmed-scenes.php
// and with the panel order in home9-house-scenes.php: the dots index by
// position, so a mismatch mislabels every room rather than failing loudly.
// Declared HERE, above the count assertion that uses it -- const is in the
// temporal dead zone until its declaration runs, so a later declaration throws.
const LABELS = ['Arrival', 'Welcome', 'Listings', 'Communities',
                'Legacy', 'Front door', 'Connect'];

const sections = extractSections(src);
if (sections.length !== LABELS.length) {
  console.error(`expected ${LABELS.length} panels, found ${sections.length} -- the Home 9 harness markup moved`);
  process.exit(1);
}

/**
 * Lift the shortcode STAND-IN styles too.
 *
 * The panels contain markup produced by build-harness.php's do_shortcode()
 * stub -- `.h-listing` cards for [cb_listings], `.h-quote` for
 * [cb_testimonials] -- and those classes are styled only in the Home 9
 * harness's own inline <style>. Lifting the panels without them left the
 * listing photos completely unstyled: no aspect-ratio box, no object-fit, so
 * each one rendered at its natural size and a single roof filled the whole
 * panel.
 *
 * Only the `.h-*` stand-in rules are taken. The rest of that style block is
 * Home 9 harness chrome (body background, badge, header) which would fight
 * cb-home10.css.
 */
function extractStandInCss(html) {
  const styles = [...html.matchAll(/<style[^>]*>([\s\S]*?)<\/style>/g)].map((m) => m[1]).join('\n');
  const rules = [...styles.matchAll(/([^{}]+)\{([^{}]*)\}/g)]
    .filter((m) => /(^|,|\s)\.h-(listing|quote)/.test(m[1]))
    .map((m) => `${m[1].trim()} {${m[2]}}`);
  if (!rules.length) {
    console.error('no .h-listing/.h-quote rules found -- the harness stand-in styles moved');
    process.exit(1);
  }
  return rules.join('\n');
}

const standInCss = extractStandInCss(src);



// The OUTER wrapper drops its cb9-page class and takes cb10-page instead. It
// cannot keep both: cb-home9.css's flat fallback pins .cb9-page with
// `position: relative !important; width: auto !important`, and !important beats
// anything cb-home10.css can say about positioning the panel. Everything INSIDE
// keeps its cb9-* classes, which is where the component styling we want lives --
// only the positioning wrapper changes hands.
//
// img.cb9-page__plate is REMOVED, not left to CSS to hide. Home 9 hangs those
// Home 2 photographs framed on its room walls; Home 10 has its own photography
// in every frame and never shows them. Left in the markup they are still
// fetched -- eight images on a page already carrying ~16MB of video -- and they
// carry CC-BY attribution for pictures nobody sees.
//
// theme/template-parts/home10-filmed-scenes.php does exactly the same two
// rewrites for the WordPress page. Keep them in step: if the transform changes
// in one, change it in the other, or the harness stops being a preview of what
// actually ships.
const panels = sections.map((s, i) =>
  s.replace('<section class="cb9-page"', `<section class="cb10-page" data-cb10-page="${i}"`)
   .replace(/<img class="cb9-page__plate"[^>]*>/g, '')
).join('\n\n');

const dots = LABELS.map((l, i) =>
  `      <button class="cb10-nav__dot${i === 0 ? ' is-active' : ''}" data-cb10-to="${i}" type="button" aria-label="${l}"></button>`
).join('\n');

const html = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CB Home 10 -- The House, filmed</title>

<!-- cb-home9.css supplies the component styling INSIDE each panel. Its floating
     layout is all nested under html.cb9-on, which this page never sets, so what
     we inherit is only the flat component look. -->
<link rel="stylesheet" href="../theme/assets/css/cb-home9.css">
<link rel="stylesheet" href="../theme/assets/css/cb-home10.css">
<style>
  html, body { margin: 0; padding: 0; }
  body { background: #0a1730; color: #f4f1ea;
         font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
  .h-badge { position: fixed; left: 12px; bottom: 12px; z-index: 9;
             font: 600 11px/1 system-ui, sans-serif; letter-spacing: .08em;
             text-transform: uppercase; color: #f0ebe0;
             background: rgba(10,23,48,.8); border: 1px solid rgba(240,235,224,.25);
             padding: 7px 10px; border-radius: 5px; }
  .h-mark { position: fixed; left: 18px; top: 16px; z-index: 9;
            font: 700 15px/1 system-ui, sans-serif; letter-spacing: .12em;
            text-transform: uppercase; color: #fff; opacity: .92; }

/* ---- lifted from the Home 9 harness: styling for the shortcode stand-ins --
   These classes come out of build-harness.php's do_shortcode() stub and are
   styled nowhere else. Regenerated with the panels, so they cannot drift. */
${standInCss}
</style>
</head>
<body>

<div class="h-mark">Coldwell Banker</div>
<div class="h-badge">Home 10 harness &mdash; filmed</div>

<!-- The world. home10.js fills this with one <video> per section. -->
<div class="cb10-stage" id="cb10-stage" aria-hidden="true"></div>

<!-- The only thing making the document tall; height is set from JS. -->
<div id="cb10-spacer"></div>

<nav class="cb10-nav" aria-label="Rooms">
${dots}
</nav>

<div class="cb10-pages">
${panels}
</div>

<script src="../theme/assets/js/cb-home10/home10.js"></script>
<script>
(function () {
  var cap = /[?&]cb_capture=(1|true)/.test(location.search);
  var ok = cap;
  try { if (!ok) { ok = window.matchMedia('(prefers-reduced-motion: no-preference)').matches; } }
  catch (e) { ok = false; }
  if (!ok) {
    if (window.console) { console.info('[cb10 harness] gated off -- flat fallback (reduced motion).'); }
    return;
  }
  if (!window.CBHome10) { if (window.console) { console.error('[cb10 harness] engine did not load.'); } return; }
  window.CBHome10.init({
    stage: '#cb10-stage',
    basePath: '../theme/assets/'
  });
}());
</script>
</body>
</html>
`;

writeFileSync(OUT, html, 'utf8');
console.log(`wrote ${OUT} (${html.length} bytes, ${sections.length} panels lifted from Home 9)`);

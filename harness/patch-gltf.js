/* =========================================================================
   patch-gltf.js -- regenerate theme/assets/js/vendor/GLTFLoader.js

     node harness/patch-gltf.js            (downloads r160 from unpkg)
     node harness/patch-gltf.js <dir>      (uses GLTFLoader.js + BufferGeometryUtils.js
                                            already sitting in <dir>)

   WHY THIS EXISTS
   ---------------
   The theme has no bundler: three is a UMD global (vendor/three.min.js, r160)
   and every module is a classic <script>. three removed the prebuilt
   examples/js UMD loaders in r148, so GLTFLoader now ships ESM-only and cannot
   be dropped in as-is. This mechanically rewrites it into a classic script.

   The rewrite is four textual edits and NO changes to upstream logic:
     1. `import { ... } from 'three'`  ->  destructured off the THREE global.
     2. `import { toTrianglesDrawMode } from '../utils/BufferGeometryUtils.js'`
        -> that one function lifted in verbatim (GLTFLoader is its only consumer,
        and it is self-contained, so vendoring all ~32kB to reach it is waste).
     3. `export { GLTFLoader }`  ->  `THREE.GLTFLoader = GLTFLoader`.
     4. TrianglesDrawMode added to the destructure -- the lifted function needs
        it and GLTFLoader itself does not import it.

   Every step asserts before it edits. If upstream moves the import block, the
   export, or the helper, this exits non-zero and says which one moved, rather
   than emitting a file that is subtly wrong. Bump VERSION to take a new three.
   ========================================================================= */
'use strict';

const fs = require('fs');
const path = require('path');

const VERSION = '0.160.0';
const OUT = path.join(__dirname, '..', 'theme', 'assets', 'js', 'vendor', 'GLTFLoader.js');
const SRC = {
  'GLTFLoader.js': `https://unpkg.com/three@${VERSION}/examples/jsm/loaders/GLTFLoader.js`,
  'BufferGeometryUtils.js': `https://unpkg.com/three@${VERSION}/examples/jsm/utils/BufferGeometryUtils.js`
};

function die(msg) { console.error('patch-gltf: ' + msg); process.exit(1); }

async function source(name, localDir) {
  if (localDir) {
    const p = path.join(localDir, name);
    if (!fs.existsSync(p)) { die('missing ' + p); }
    return fs.readFileSync(p, 'utf8');
  }
  const res = await fetch(SRC[name]);
  if (!res.ok) { die(`download failed for ${name}: HTTP ${res.status}`); }
  return res.text();
}

/** Slice out a top-level `function name(...) { ... }` by brace matching. */
function extractFn(src, name) {
  const start = src.indexOf('function ' + name);
  if (start < 0) { die(`${name} not found -- upstream moved it`); }
  let depth = 0, end = -1;
  for (let j = src.indexOf('{', start); j < src.length; j++) {
    if (src[j] === '{') { depth++; }
    else if (src[j] === '}') { depth--; if (depth === 0) { end = j + 1; break; } }
  }
  if (end < 0) { die(`could not brace-match ${name}`); }
  return src.slice(start, end);
}

const indent = (s) => s.split('\n').map((l) => (l ? '  ' + l : l)).join('\n');

(async function main() {
  const localDir = process.argv[2] || null;
  let loader = await source('GLTFLoader.js', localDir);
  const bgu = await source('BufferGeometryUtils.js', localDir);

  // 1. capture + strip the `three` import
  const importRe = /^import\s*\{([\s\S]*?)\}\s*from\s*'three';\s*$/m;
  const m = loader.match(importRe);
  if (!m) { die("`import { ... } from 'three'` not found -- upstream moved it"); }
  const symbols = m[1].split(',').map((s) => s.trim()).filter(Boolean);
  loader = loader.replace(importRe, '');

  // 4. the lifted helper needs a constant GLTFLoader itself does not import
  if (!symbols.includes('TrianglesDrawMode')) { symbols.push('TrianglesDrawMode'); }

  // 2. strip the BufferGeometryUtils import
  const bguRe = /^import\s*\{\s*toTrianglesDrawMode\s*\}\s*from\s*'[^']*BufferGeometryUtils\.js';\s*$/m;
  if (!bguRe.test(loader)) { die('BufferGeometryUtils import not found -- upstream moved it'); }
  loader = loader.replace(bguRe, '');

  // 3. strip the ESM export
  const exportRe = /^export\s*\{\s*GLTFLoader\s*\};\s*$/m;
  if (!exportRe.test(loader)) { die('`export { GLTFLoader }` not found -- upstream moved it'); }
  loader = loader.replace(exportRe, '');

  const toTri = extractFn(bgu, 'toTrianglesDrawMode');
  for (const id of ['TrianglesDrawMode', 'TriangleFanDrawMode', 'TriangleStripDrawMode']) {
    if (!symbols.includes(id)) { die('lifted helper needs an unprovided symbol: ' + id); }
  }
  if (/\b(Vector3|BufferAttribute|Float32BufferAttribute)\b/.test(toTri)) {
    die('toTrianglesDrawMode gained a dependency -- widen the symbol check');
  }

  const destructure = [];
  for (let k = 0; k < symbols.length; k += 6) {
    destructure.push('    ' + symbols.slice(k, k + 6).join(', ') + (k + 6 < symbols.length ? ',' : ''));
  }

  const header = `/* =========================================================================
   GLTFLoader.js -- three.js r${VERSION.split('.')[1]} GLTFLoader, vendored as a CLASSIC SCRIPT.

   GENERATED FILE -- DO NOT HAND-EDIT. Regenerate with:
       node harness/patch-gltf.js

   WHY THIS FILE EXISTS
   --------------------
   The theme loads three as a UMD global (vendor/three.min.js, r160) -- there is
   no bundler and no import map anywhere in this project. three removed the
   prebuilt examples/js UMD loaders back in r148, so upstream now ships
   GLTFLoader as ESM only: it does \`import { ... } from 'three'\`, which a plain
   <script> tag cannot resolve. This is that module, mechanically rewritten to
   read the same symbols off window.THREE instead.

   PROVENANCE -- three@${VERSION}
     examples/jsm/loaders/GLTFLoader.js          (body, verbatim)
     examples/jsm/utils/BufferGeometryUtils.js   (toTrianglesDrawMode only)

   THE PATCH, in full -- there are no other edits to upstream logic:
     1. \`import { ... } from 'three'\`  ->  destructured from the THREE global.
     2. \`import { toTrianglesDrawMode } from '../utils/BufferGeometryUtils.js'\`
        -> that one function lifted in verbatim. GLTFLoader is the only consumer
        and it is self-contained, so vendoring all ~32kB of BufferGeometryUtils
        to reach it would be dead weight.
     3. \`export { GLTFLoader }\`  ->  \`THREE.GLTFLoader = GLTFLoader\`.
     4. TrianglesDrawMode added to the destructure: toTrianglesDrawMode needs it
        and GLTFLoader itself does not import it.

   Attaches window.THREE.GLTFLoader. No-ops if THREE is absent, so a load-order
   mistake degrades instead of throwing -- home9.js already treats a missing
   loader as "no armchair mesh" and keeps its procedural box fallback.
   ========================================================================= */
(function (THREE) {
  'use strict';

  if (!THREE) {
    if (window.console) { console.warn('[GLTFLoader] window.THREE missing; loader not installed.'); }
    return;
  }

  var {
${destructure.join('\n')}
  } = THREE;

  // ---- lifted from examples/jsm/utils/BufferGeometryUtils.js (r160) ---------
${indent(toTri)}

  // ---- examples/jsm/loaders/GLTFLoader.js (r160), body verbatim -------------
`;

  const body = indent(loader.replace(/^\s*\n/, ''));
  const footer = `
  THREE.GLTFLoader = GLTFLoader;

}(window.THREE));
`;

  fs.writeFileSync(OUT, header + body + footer, 'utf8');
  console.log(`patch-gltf: wrote ${path.relative(path.join(__dirname, '..'), OUT)} ` +
              `(${fs.statSync(OUT).size} bytes, ${symbols.length} symbols destructured)`);
})();
